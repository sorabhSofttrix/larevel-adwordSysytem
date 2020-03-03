<?php

namespace App\Http\Controllers;

use App\Project;
use App\User;
use App\Profile;
use App\Client;
use Illuminate\Http\Request;
use Validator;

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
            'project_name' => 'required',
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
                    'project_name' => $request->project_name,
                    'contract_start_date' => $request->contract_start_date,
                    'hourly_rate' => $request->hourly_rate,
                    'weekly_limit' => $request->weekly_limit,
                    'sales_person' => $user->id,
                    'profile' => $request->profile,
                    'client' => $request->client,
                    'add_by' => $user->id,
                );
                $addedProject = Project::create($project);
                if($addedProject) {
                    $project = $addedProject->toArray();
                    if (isset($request['questionnaire'])) {
                        $addedProject->addMediaFromRequest('questionnaire')->toMediaCollection('questionnaire');
                        $addedProject->questionnaire = str_replace("http://localhost","",$addedProject->getFirstMediaUrl('questionnaire'));
                        $addedProject->save();
                    }
                    $project['questionnaire'] = $addedProject->questionnaire;
                    return response()->json(
                        getResponseObject(true, $project , 200, '')
                        , 200);
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
     *  Funtion to get project(s) record to db
     * 
     * 
    */
    public function get(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $projects = Project::where('is_active',true)->where('id', $request->id)->get();
        } else {
            $projects = Project::where('is_active',true)->get();
        }
        if($projects) {
            $allUserIds = [];
            $allClientIds = [];
            $allprofileIds = [];
            foreach($projects as $project) {
                $project->questionnaire = getPathWithUrl($project->questionnaire);
                //collab all users
                ($project->sales_person) ? $allUserIds[] = $project->sales_person : '';
                ($project->add_by) ? $allUserIds[] = $project->add_by : '';

                //collab all profile
                ($project->profile) ? $allprofileIds[] = $project->profile : '';

                //collab all client
                ($project->client) ? $allClientIds[] = $project->client : '';
            }

            $projectData = array(
                'data' => $projects,
                'users' => User::select('id','name')->whereIn('id',$allUserIds)->get(),
                'profiles' => Profile::select('id','profile_name')->whereIn('id',$allprofileIds)->get(),
                'clients' => Client::select('id','client_name','email','skype','phone')->whereIn('id',$allClientIds)->get(),
            );
            return response()->json(
                getResponseObject(true, $projectData, 200, '')
                , 200);
        } else {
            return response()->json(
                getResponseObject(false, '', 404, 'Project not found')
                , 404);
        }
    }
}
