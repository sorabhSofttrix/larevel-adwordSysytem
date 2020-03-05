<?php

namespace App\Http\Controllers;

use App\Project;
use App\User;
use App\Profile;
use App\Client;
use App\AllComment;
use App\AdwordsAccount;

use Illuminate\Http\Request;
use Validator;
use Exception;

class ProjectController extends Controller
{
    /**
     * Create a new projectController with jwtAuth instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }
    // project_name , contract_start_date, hourly_rate, weekly_limit, questionnaire, sales_person
    // profile, client, add_by
    /**
     *  Funtion to add new project record to db
     * 
     * 
    */
    public function add(Request $request) {
        $validationRules = [
            'sales_person' => 'exists:users,id',
            'profile' => 'exists:profiles,id',
            'client' => 'exists:clients,id',
            'questionnaire' => 'mimes:txt,doc,docx,odt,ods,xls,xlsx'
        ];
        $validatedData = Validator::make($request->all(),$validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            if($user) {
                $project = array(
                    'contract_start_date' => $request->contract_start_date,
                    'hourly_rate' => $request->hourly_rate,
                    'weekly_limit' => $request->weekly_limit,
                    'sales_person' => $user->id,
                    'client' => $request->client,
                    'profile' => $request->profile,
                    'add_by' => $user->id,
                );
                $addedProject = Project::create($project);
                if($addedProject) {
                    $addedProject->project_name = generateProjectName($addedProject->id);
                    if (isset($request['questionnaire'])) {
                        $addedProject->addMediaFromRequest('questionnaire')->toMediaCollection('questionnaire');
                        $addedProject->questionnaire = str_replace("http://localhost","",$addedProject->getFirstMediaUrl('questionnaire'));
                    }

                    if(!isset($request['client']) || empty($request->client)) {
                        if(isset($request->client_name) && !empty($request->client_name)) {
                            $client = Client::create(
                                array(
                                    'client_name' => $request->client_name,
                                    'email' => $request->email,
                                    'skype' => $request->skype,
                                    'phone' => $request->phone,
                                    'add_by' => $user->id
                                )
                            );
                            $addedProject->client = $client->id;
                        }
                    }

                    $addedProject->save();

                    // if intial comment
                    if(isset($request['comment']) && !empty($request->comment)) {
                        $comment = array(
                            'entity_type' => 'project',
                            'entity_id' => $addedProject->id,
                            'comment' => $request->comment,
                            'add_by' => $user->id,
                        );
                        AllComment::create($comment);
                    }

                    // add google accounts
                    if(isset($request->google_accounts)) {
                        $accounts_g = json_decode($request->google_accounts);
                        if(count($accounts_g)) {
                            $account_resp = $this->addAdwordAccounts($accounts_g, $addedProject->id);
                            if(!$account_resp['status']) {
                                $proj = $this->getProjects($addedProject->id, false);
                                return response()->json(
                                    getResponseObject(false, $proj[0], 200, $account_resp['error'])
                                    , 200);
                            }
                        }
                    }
                    return $this->getProjects($addedProject->id);
                } else {
                    return response()->json(
                        getResponseObject(false, '', 400, 'Something went wrong please try again later.')
                        , 400);
                }
            } else {
                return response()->json(
                    getResponseObject(false, '', 401, 'unauthorized')
                    , 401);
            }
        }
    }

    /**
     *  Funtion to update a project record in db
     * 
     * 
    */
    public function update(Request $request) {
        $validationRules = [
            'id' => 'required',
            'sales_person' => 'exists:users,id',
            'profile' => 'exists:profiles,id',
            'client' => 'exists:clients,id',
            'questionnaire' => 'mimes:txt,doc,docx,odt,ods,xls,xlsx'
        ];

        $validatedData = Validator::make($request->all(),$validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            if($user) {
                $update_project = Project::find($request->id);
                if($update_project) {
                    // if changing contract_start_date
                    if (isset($request['contract_start_date']) && $update_project->contract_start_date != $request->contract_start_date) {
                        $update_project->contract_start_date = $request->contract_start_date;
                    }


                    // if changing hourly_rate
                    if (isset($request['hourly_rate']) && $update_project->hourly_rate != $request->hourly_rate) {
                        $update_project->hourly_rate = $request->hourly_rate;
                    }

                    // if changing weekly_limit
                    if (isset($request['weekly_limit']) && $update_project->weekly_limit != $request->weekly_limit) {
                        $update_project->weekly_limit = $request->weekly_limit;
                    }

                    // if changing sales_person
                    if (isset($request['sales_person']) && $update_project->sales_person != $request->sales_person) {
                        $update_project->sales_person = $request->sales_person;
                    }

                    // if changing profile
                    if (isset($request['profile']) && $update_project->profile != $request->profile) {
                        $update_project->profile = $request->profile;
                    }

                    // if changing questionnaire
                    if (isset($request['questionnaire'])) {
                        if($update_project->hasMedia('questionnaire')){
                            $update_project->media('questionnaire')->delete();
                        }
                        $update_project->addMediaFromRequest('questionnaire')
                                       ->toMediaCollection('questionnaire');
                        $update_project->save();
                        $update_project->questionnaire = str_replace("http://localhost","",$update_project->getFirstMediaUrl('questionnaire'));
                    }

                    // if updating client
                    if($update_project->client) {
                        $client = Client::find($update_project->client); 
                        $cleintUpdateDetails = [];
                        /*  if changing client name */
                        if(isset($request['client_name']) && !empty($request->client_name) 
                            && $request->client_name !== $client->client_name) {

                                $client->client_name = $request->client_name;
                        }

                        /*  if changing client email */
                        if(isset($request['email']) && !empty($request->email) 
                            && $request->email !== $client->email) {

                                $client->email = $request->email;
                        }

                        /*  if changing client skype */
                        if(isset($request['skype']) && !empty($request->skype) 
                            && $request->skype !== $client->skype) {
                                
                                $client->skype = $request->skype;
                        }

                        /*  if changing client phone */
                        if(isset($request['phone']) && !empty($request->phone) 
                            && $request->phone !== $client->phone) {
                                
                                $client->phone = $request->phone;
                        }
                        $client->save();
                    }

                    $update_project->save();

                    return $this->getProjects($update_project->id);
                } else {
                    return response()->json(
                        getResponseObject(false, '', 404, 'Project not found')
                        , 404);
                }
            } else {
                return response()->json(
                    getResponseObject(false, '', 401, 'unauthorized')
                    , 401);
            }
        }
    }

    /**
     *  Funtion to delete project record from db
     * 
     * 
    */
    public function delete(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $project = Project::find($request->id);
            if($project) {
                $project->is_active = false;
                $project->save();
                return response()->json(
                    getResponseObject(true, 'Project deleted', 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'Project not found')
                    , 404);
            }
        } else {
            return response()->json(
                getResponseObject(false, '', 404, 'Project not found')
                , 404);
        }
    }

    /**
     *  Funtion to get project(s) record from db
     * 
     * 
    */
    public function get(Request $request) {
        return $this->getProjects($request['id']);
    }

    public function getProjects($data, $response=true) {
        $projectQuery = Project::select('projects.*',
                                        'profiles.profile_name',
                                        'client_name','clients.skype','clients.phone','clients.email',
                                        'sales_persons.name as sales_person_name',
                                        'added_by.name as add_by_name',
                                        )
                            ->leftJoin('profiles','projects.profile','profiles.id')
                            ->leftJoin('clients','projects.client','clients.id')
                            ->leftJoin('users as sales_persons','projects.sales_person','sales_persons.id')
                            ->leftJoin('users as added_by','projects.add_by','added_by.id')
                            ->where('projects.is_active',true);

        if( isset($data) && !empty($data) ) {
            $projectQuery->where('projects.id', $data);
        }
        $projects = $projectQuery->get();
        $projectsArray = $projects->toArray(); 
        if($projects) {
            foreach($projects as $key => $project) {
                $projectsArray[$key]['questionnaire'] = getPathWithUrl($projectsArray[$key]['questionnaire']);
                $projectsArray[$key]['comments'] = $project->comments();
                $projectsArray[$key]['accounts'] = $project->accounts();
            }
            if(!$response) {
                return $projectsArray;
            }
            return response()->json(
                getResponseObject(true, $projectsArray, 200, '')
                , 200);
        } else {
            if(!$response) {
                return array();
            }
            return response()->json(
                getResponseObject(false, '', 404, 'Project not found')
                , 404);
        }
    }

    public function addComment(Request $request) {
        $validationRules = [
            'id' => 'required|exists:projects,id',
            'comment' => 'required',
        ];
        $validatedData = Validator::make($request->all(), $validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $comment = array(
                'entity_type' => 'project',
                'entity_id' => $request->id,
                'comment' => $request->comment,
                'add_by' => auth()->user()->id,
            );
            AllComment::create($comment);
            return response()->json(
                getResponseObject(false, 'comment added', 200, '')
                , 200);
        }
    }

    public function addAdwordAccounts($data, $project_id) {
        $accounts = [];
        foreach($data as $key => $value){
            $accounts[] = array(
                'acc_name' => $value->acc_name,
                'g_acc_id' => $value->g_acc_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'project_id' => $project_id,
                'add_by' => auth()->user()->id,
            );
        }
        if($accounts) {
            try {
                AdwordsAccount::insert($accounts);
            } catch(Exception $excp) {
                return array('status'=> false, 'error' => 'issue while adding accounts please check');
            }
        }
        return array('status'=> true, 'error' => '');
    }
}
