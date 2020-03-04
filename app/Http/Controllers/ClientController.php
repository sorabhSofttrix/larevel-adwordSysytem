<?php

namespace App\Http\Controllers;

use App\Client;
use Illuminate\Http\Request;

use Validator;

class ClientController extends Controller
{
    /**
     * Create a new clientController with jwtAuth instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

    /**
     *  Funtion to add new client record to db
     * 
     * 
    */
    public function add(Request $request) {
        $validationRules = [
            'client_name' => 'required',
        ];
        $validatedData = Validator::make($request->all(),$validationRules);
        if($validatedData->fails()) {
            return response()->json(
                getResponseObject(false, array(), 400, $validatedData->errors()->first())
                , 400);
        } else {
            $user = auth()->user();
            $client = array(
                'client_name' => $request->client_name,
                'email' => $request->email,
                'skype' => $request->skype,
                'phone' => $request->phone,
                'add_by' => $user->id,
            );
            $addedClient = Client::create($client);
            return response()->json(
                getResponseObject(true, $addedClient, 200, '')
                , 200);
        }
    }

    /**
     *  Funtion to edit existing client record to db
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
            $client = Client::find($request->id);
            if($client) {
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
                return response()->json(
                    getResponseObject(true, $client, 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'Cleint not found')
                    , 404);
            }
        }
    }

    /**
     *  Funtion to set client as deleted form db
     * 
     * 
    */
    public function delete(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $client = Client::find($request->id);
            if($client) {
                $client->is_active = false;
                $client->save();
                return response()->json(
                    getResponseObject(true, 'Client deleted', 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'Client not found')
                    , 404);
            }
        } else {
            return response()->json(
                getResponseObject(false, '', 404, 'Client not found')
                , 404);
        }
    }

    /**
     *  Funtion to get client list or single client record form db
     * 
     * 
    */
    public function get(Request $request) {
        if( isset($request['id']) && !empty($request->id) ) {
            $client = Client::where('is_active',true)->where('id', $request->id)->get();
            if($client) {
                return response()->json(
                    getResponseObject(true, $client, 200, '')
                    , 200);
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'client not found')
                    , 404);
            }
        } else {
            $clients = Client::where('is_active',true)->get();
            return response()->json(
                getResponseObject(true, $clients, 200, '')
                , 200);
        }
    }
}
