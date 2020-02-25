<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\AccountSync;
use App\AdwordsAccount;
use App\PerformanceReport;
use App\Alert;
use App\User;
use App\AccountIssue;

use Illuminate\Support\Facades\Mail;
use App\Mail\AlertMail;
use App\Mail\PendingAlertMail;

use Validator;
use Excel;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\Auth\FetchAuthTokenInterface;

use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomer;
use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomerService;

use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;

use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;


use Exception;
use Google\AdsApi\AdWords\v201809\cm\ApplicationException;

class AccountSyncController extends Controller
{
    const PAGE_LIMIT = 500;
    const MAIN_ACCOUNT = '628-683-0853';
	/**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function cleanID($id) {
        return preg_replace('/[^a-z0-9]/i','', $id);
    }

    public function findId($arr, $gAccId){
        return array_filter($arr, function ($var) use ($gAccId) {
            return ($var['g_acc_id'] == $gAccId);
        });
    }



    public function cronCompare(
        FetchAuthTokenInterface $oAuth2Credential,
        AdWordsServices $adWordsServices,
        AdWordsSessionBuilder $adWordsSessionBuilder
    ) {
        $clientCustomerId = self::MAIN_ACCOUNT;
        $session =
            $adWordsSessionBuilder->fromFile(realpath(base_path('adsapi_php.ini')))
                ->withOAuth2Credential($oAuth2Credential)
                ->withClientCustomerId($clientCustomerId)
                ->build();
        $allAccounts = AdwordsAccount::select('adwords_accounts.*','director.email as director_email','manager.email as manager_email')
            ->leftJoin('users as manager','adwords_accounts.account_manager','manager.id')
            ->leftJoin('users as director','adwords_accounts.account_director','director.id')
            ->where('acc_status','=','active')
            ->where('have_issue','=','false')
            ->orderByRaw("FIELD(acc_priority, $priority)")
            ->get();
        /* report selector */
        $reportSelector = new Selector();
        $reportSelector->setFields(
            ['AccountDescriptiveName', 'ExternalCustomerId', 
            'CostPerConversion', 'Conversions', 'ConversionValue', 
            'Cost', 'AverageCpc', 'Impressions', 'Clicks','Ctr' ]
        );

        /* report definition.*/ 
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($reportSelector);
        $reportDefinition->setReportName('Custom ACCOUNT_PERFORMANCE_REPORT');
        $reportDefinition->setDateRangeType(
            ReportDefinitionDateRangeType::LAST_7_DAYS
        );
        $reportDefinition->setReportType(
            ReportDefinitionReportType::ACCOUNT_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        /* loop through accounts for reports as well */
        /* report sessions */

        foreach($allAccounts as $account) {
            $worked = false;
            $resultTable;
            try{
                $reportSession = $adWordsSessionBuilder->withClientCustomerId($account->g_acc_id)->build();
                $reportDownloader = new ReportDownloader($reportSession);
                $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
                $reportDownloadResult = $reportDownloader->downloadReport(
                    $reportDefinition,
                    $reportSettingsOverride
                );
                $json = json_encode(
                    simplexml_load_string($reportDownloadResult->getAsString())
                );
                $resultTable = json_decode($json, true)['table'];
                $worked = true;
            } catch(Exception $excp) {
                if($excp instanceof ApplicationException) {
                    $excpError = $serialized_array = serialize($excp->getErrors());
                    AccountIssue::create(
                      array(
                        'acc_id' => $account->id,
                        'error' => $excpError,
                      )
                    );
                    $account->have_issue = true;
                    $account->save();
                }
            }
            if($worked) {
                $collectedRows = collect([]);
                if (array_key_exists('row', $resultTable)) {
                    $row = $resultTable['row'];
                    $row = count($row) > 1 ? $row : [$row];
                    $collectedRows =collect($row);
                }
                $fetchedData = $collectedRows[0]['@attributes'];
                $performanceData = array(
                    'acc_id' => $account->id,
                    'g_id' => $account->g_acc_id,
                    'report_type' => 'LAST_7_DAYS',
                    'cpa' => convertToFloat($fetchedData['costConv'] / 1000000),
                    'conversion' => $fetchedData['conversions'],
                    'totalConversion' => $fetchedData['totalConvValue'],
                    'cost' => ''. convertToFloat($fetchedData['cost'] / 1000000),
                    'cpc' => convertToFloat($fetchedData['avgCPC'] / 1000000),
                    'impressions' => $fetchedData['impressions'],
                    'click' => $fetchedData['clicks'],
                    'ctr' => convertToFloat(str_replace('%', '', $fetchedData['ctr'])),
                );
                $performanceRecord = PerformanceReport::create(
                    $performanceData
                );
                $alertData = [];
                if(convertToFloat($account->cpa) < convertToFloat($performanceData['cpa']) ) {
                    $alertData[] =  getAlertBody( convertToFloat($account->cpa), convertToFloat($performanceData['cpa']), 
                    convertToFloat( convertToFloat($performanceData['cpa']) - convertToFloat($account->cpa) ),
                        'High CPA', '', '');
                }

                if(convertToFloat($account->cpc) < convertToFloat($performanceData['cpc']) ) {
                    $alertData[] = getAlertBody( convertToFloat($account->cpc),
                    convertToFloat($performanceData['cpc']), 
                    convertToFloat( convertToFloat($performanceData['cpc']) - convertToFloat($account->cpc) ),
                    'High CPC', '', '');
                }

                if(convertToFloat($account->ctr) > convertToFloat($performanceData['ctr']) ) {
                    $alertData[] = getAlertBody( convertToFloat($account->ctr),
                    convertToFloat($performanceData['ctr']), 
                    convertToFloat(convertToFloat($performanceData['ctr'] - convertToFloat($account->ctr))),
                    'Low CTR', '', '');
                }

                if((int) $account->conversion > (int) $performanceData['conversion']) {
                    $alertData[] = getAlertBody(
                        (int)$account['conversion'], (int)$performanceData['conversion'], 
                        (int)$performanceData['conversion'] - (int)$account->conversion ,
                        'Low Conversions', '', '');
                }

                if((float) $account->totalConversion > (float)$performanceData['totalConversion'] ) {
                    $alertData[] = getAlertBody(
                        (float) $account->totalConversion, (float)$performanceData['totalConversion'], 
                        convertToFloat((float)$performanceData['totalConversion'] - (float) $account->totalConversion),
                        'Low ConversionsValue', '', '');
                }

                $costDiff = (float)$performanceData['cost'] - (float)$account->cost;
                if($costDiff > 10 || $costDiff < -10) {
                    $costTitle = ($costDiff > 10) ? 'High Cost' : ($costDiff < -10) ? 'Low Cost' : '';
                    $alertData[] = getAlertBody(
                        convertToFloat($account->cost), convertToFloat($performanceData['cost']), 
                        convertToFloat($costDiff),
                        $costTitle, '', '');
                }

                if((int) $account->impressions > (int) $performanceData['impressions']) {
                    $alertData[] = getAlertBody(
                        (int)$account['impressions'], (int)$performanceData['impressions'], 
                        (int)$performanceData['impressions'] - (int)$account->impressions ,
                        'Low Impressions', '', '');
                }

                if((int) $account->click > (int) $performanceData['click']) {
                    $alertData[] = getAlertBody(
                        (int)$account['click'], (int)$performanceData['click'], 
                        (int)$performanceData['click'] - (int)$account->click,
                        'Low Clicks', '', '');
                }
                if(count($alertData)) {
                    $account->cpa = $performanceData['cpa'];
                    $account->cpc = $performanceData['cpc'];
                    $account->conversion = $performanceData['conversion'];
                    $account->totalConversion = $performanceData['totalConversion'];
                    $account->cost = $performanceData['cost'];
                    $account->impressions = $performanceData['impressions'];
                    $account->click = $performanceData['click'];
                    $account->ctr = $performanceData['ctr'];
                    $account->save();
                    $alertData = array(
                        'acc_id' => $account->id,
                        'g_id' => $account->g_acc_id,
                        'report_type' => 'LAST_7_DAYS',
                        'alerts' => $alertData,
                    );
                    $alertRecord = Alert::Create($alertData);
                    Mail::to($account->manager_email)
                        ->cc($account->director_email)
                        ->queue(new AlertMail($alertData));
                }
            }
        }

        return response()->json(
            getResponseObject(true, 'Cron Completed', 200, '')
            , 200);
    }

    /***** Google Ad account sync by Api******/

    
    public function syncFromGoogle(
        FetchAuthTokenInterface $oAuth2Credential,
        AdWordsServices $adWordsServices,
        AdWordsSessionBuilder $adWordsSessionBuilder
    ) {
        $loaclAccounts = AdwordsAccount::select('g_acc_id')->where('g_acc_id','<>','')->get()->toArray();
        $lastAccountId = null;
        if(count($loaclAccounts)) {
            $lastAccountId = $loaclAccounts[count($loaclAccounts)-1]['g_acc_id'];
        }
        $clientCustomerId = self::MAIN_ACCOUNT;
        $session =
            $adWordsSessionBuilder->fromFile(realpath(base_path('adsapi_php.ini')))
                ->withOAuth2Credential($oAuth2Credential)
                ->withClientCustomerId($clientCustomerId)
                ->build();
        
        $managedCustomerService = $adWordsServices->get($session, ManagedCustomerService::class);
        
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['CustomerId', 'Name', 'CanManageClients']);
        $selector->setFields(['CustomerId', 'Name', 'CanManageClients']);
        $selector->setOrdering([new OrderBy('CustomerId', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));

        // Use a predicate .
        // if($lastAccountId) {
        //     $selector->setPredicates(
        //         [ new Predicate('CustomerId', PredicateOperator::GREATER_THAN, [$lastAccountId]) ]
        //     );
        // }

        // Maps from customer IDs to accounts and links.
        $customerIdsToAccounts = [];
        $customerIdsToChildLinks = [];
        $customerIdsToParentLinks = [];
        $totalNumEntries = 0;
        do {
            // Make the get request.
            $page = $managedCustomerService->get($selector);
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                if ($page->getLinks() !== null) {
                    foreach ($page->getLinks() as $link) {
                        $managerCustomerId = strval($link->getManagerCustomerId());
                        $customerIdsToChildLinks[$managerCustomerId][] = $link;
                        $clientCustomerId = strval($link->getClientCustomerId());
                        $customerIdsToParentLinks[$clientCustomerId] = $link;
                    }
                }
                foreach ($page->getEntries() as $account) {
                    $customerIdsToAccounts[strval($account->getCustomerId())] = $account;
                }
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        if (count($customerIdsToAccounts) >= 1) {
            $user = auth()->user();
            $records = [];

            /* report selector */
            $reportSelector = new Selector();
            $reportSelector->setFields(
                ['AccountDescriptiveName', 'ExternalCustomerId', 
                'CostPerConversion', 'Conversions', 'ConversionValue', 
                'Cost', 'AverageCpc', 'Impressions', 'Clicks','Ctr' ]
            );

            /* report definition.*/ 
            $reportDefinition = new ReportDefinition();
            $reportDefinition->setSelector($reportSelector);
            $reportDefinition->setReportName('Custom ACCOUNT_PERFORMANCE_REPORT');
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_7_DAYS
            );
            $reportDefinition->setReportType(
                ReportDefinitionReportType::ACCOUNT_PERFORMANCE_REPORT
            );
            $reportDefinition->setDownloadFormat(DownloadFormat::XML);

            /* loop through accounts for reports as well */
            /* report sessions */
            foreach($customerIdsToAccounts as $account_key => $g_account) {
                if(count($this->findId($loaclAccounts, $g_account->getCustomerId()) ) == 0 && !$g_account->getCanManageClients()) {
                    $reportSession = $adWordsSessionBuilder->withClientCustomerId($g_account->getCustomerId())->build();
                    $reportDownloader = new ReportDownloader($reportSession);
                    $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
                    $reportDownloadResult = $reportDownloader->downloadReport(
                        $reportDefinition,
                        $reportSettingsOverride
                    );
                    $json = json_encode(
                        simplexml_load_string($reportDownloadResult->getAsString())
                    );
                    $resultTable = json_decode($json, true)['table'];
                    $collectedRows = collect([]);
                    if (array_key_exists('row', $resultTable)) {
                        $row = $resultTable['row'];
                        $row = count($row) > 1 ? $row : [$row];
                        $collectedRows =collect($row);
                    }
                    $requiredElements = array(
                        'cpa'=> 0, 'conversion'=> 0, 'totalConversion'=> 0,
                        'cost'=> 0, 'cpc'=> 0, 'impressions'=> 0,  'click'=> 0, 'ctr'=>0
                    );
                    foreach($collectedRows as $row) {
                        $requiredElements = array(
                            'cpa'=> 0, 'conversion'=> 0, 'totalConversion'=> 0,
                            'cost'=> 0, 'cpc'=> 0, 'impressions'=> 0,  'click'=> 0, 'ctr'=>0
                        );
                        $reportData = $row['@attributes'];
                        
                        $requiredElements['cpa'] = convertToFloat( $requiredElements['cpa']) + ( convertToFloat( $reportData['costConv'] / 1000000)); 
                        $requiredElements['conversion'] = (int) $requiredElements['conversion'] + (int) $reportData['conversions']; 
                        $requiredElements['totalConversion'] = (float) $requiredElements['totalConversion'] + (float) $reportData['totalConvValue']; 
                        
                        $requiredElements['cost'] = convertToFloat( ($requiredElements['cost']) + convertToFloat( $reportData['cost'] / 1000000) ); 
                        $requiredElements['cpc'] = convertToFloat( $requiredElements['cpc'] ) + ( convertToFloat( $reportData['avgCPC'] / 1000000) );
                        $requiredElements['impressions'] = (int) $requiredElements['impressions'] + (int) $reportData['impressions']; 
                        $requiredElements['click'] = (int) $requiredElements['click'] + (int) $reportData['clicks']; 
                        $requiredElements['ctr'] = convertToFloat( $requiredElements['ctr'] ) + convertToFloat( $reportData['ctr']);
                    }
                    $acc_array = array(
                                    'acc_name' => $g_account->getName(), 
                                    'g_acc_id' => $g_account->getCustomerId(),
                                    'cron_time' => '24',
                                    'acc_priority' => 'normal',
                                    'add_by' => $user->id,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                    'created_at' => date('Y-m-d H:i:s')
                                );
                    $records[] = array_merge($acc_array, $requiredElements);
                    $requiredElements = array(
                        'cpa'=> 0, 'conversion'=> 0, 'totalConversion'=> 0,
                        'cost'=> 0, 'cpc'=> 0, 'impressions'=> 0,  'click'=> 0, 'ctr'=>0
                    );
                }
            }
            if($records) {
                $ada = AdwordsAccount::insert($records);
                return response()->json(
                    getResponseObject(true, 'sync successfull', 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '' , 400, 'no new records to update')
                    , 400);
            }
        } else {
            return response()->json(
                getResponseObject(false, '' , 400, 'no records found')
                , 400);
        }
    }

    public function sendPendingMails() {
        $alert = Alert::select("alerts.*",'account_manager','account_director', 
                                            'manager.email as manager_email', 
                                            'director.email as director_email',
                                            'admin.email as admin_email',)
            ->leftJoin('adwords_accounts as account','alerts.acc_id','account.id')
            ->leftJoin('users as manager','account.account_manager','manager.id')
            ->leftJoin('users as director','account.account_director','director.id')
            ->leftJoin('users as admin','director.parent_id','admin.id')
            ->where('alerts.status','open')
            ->get();
        foreach($alert as $al) {
            Mail::to($al->manager_email)
                ->cc($al->director_email)
                ->bcc($al->admin_email)
                ->queue(new PendingAlertMail($al));
        }
        return response()->json(
            getResponseObject(false, 'emails sent.'   , 200, '')
            , 200);
    }

}
