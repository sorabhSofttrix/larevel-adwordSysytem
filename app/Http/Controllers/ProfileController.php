<?php

namespace App\Http\Controllers;

use App\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Validator;

class ProfileController extends Controller
{
    /**
     * Create a new profileController with jwtAuth instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

    /**
     *  Funtion to add new profile record to db
     * 
     * 
    */
    public function add(Request $request) {
        $validationRules = [
            'profile_name' => 'required|unique:profiles',
            'profile_id' => 'unique:profiles',
        ];
        $validatedData = Validator::make($request->all(),$validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            $profile = array(
                'profile_name' => $request->profile_name,
                'profile_id' => $request->profile_id,
                'username' => $request->username,
                'email' => $request->email,
                'password' => ($request->password) ? Hash::make($request->password) : null,
                'add_by' => $user->id,
            );
            $addedProfile = Profile::create($profile);
            return response()->json(
                getResponseObject(true, $addedProfile, 200, '')
                , 200);
        }
    }

    /**
     *  Funtion to edit existing profile record to db
     * 
     * 
    */
    public function update(Request $request) {
        $validationRules = [ 'id' => 'required' ];
        $validatedData = Validator::make($request->all(),$validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            $profile = Profile::find($request->id);
            if($profile) {
                /*  if changing profile name */
                if(isset($request['profile_name']) && !empty($request->profile_name) 
                    && $request->profile_name !== $profile->profile_name) {
                        $profile->profile_name = $request->profile_name;
                }

                /*  if changing profile id */
                if(isset($request['profile_id']) && !empty($request->profile_id) 
                    && $request->profile_id !== $profile->profile_id) {

                        $profile->profile_id = $request->profile_id;
                }

                /*  if changing profile username */
                if(isset($request['username']) && !empty($request->username) 
                    && $request->username !== $profile->username) {

                        $profile->username = $request->username;
                }

                /*  if changing profile email */
                if(isset($request['email']) && !empty($request->email) 
                    && $request->email !== $profile->email) {

                        $profile->email = $request->email;
                }

                /*  if changing profile password */
                if(isset($request['password']) && !empty($request->password) 
                    && Hash::make($request->password) !== $profile->password) {
                        
                        $profile->password = Hash::make($request->password);
                }

                $profile->save();
                return response()->json(
                    getResponseObject(true, $profile, 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'profile not found')
                    , 404);
            }
        }
    }

    /**
     *  Funtion to set profile as deleted form db
     * 
     * 
    */
    public function delete(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $profile = Profile::find($request->id);
            if($profile) {
                $profile->is_active = false;
                $profile->save();
                return response()->json(
                    getResponseObject(true, 'Profile deleted', 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'profile not found')
                    , 404);
            }
        } else {
            return response()->json(
                    getResponseObject(false, '', 404, 'profile not found')
                    , 404);
        }
    }

    /**
     *  Funtion to get profiles list or single profile record form db
     * 
     * 
    */
    public function get(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $profile = Profile::where('is_active',true)->where('id', $request->id)->get();
            if($profile) {
                return response()->json(
                    getResponseObject(true, $profile, 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'profile not found')
                    , 404);
            }
        } else {
            $profiles = Profile::where('is_active',true)->get();
            return response()->json(
                getResponseObject(true, $profiles, 200, '')
                , 200);
        }
    }
}
