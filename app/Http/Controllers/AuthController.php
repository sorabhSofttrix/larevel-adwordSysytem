<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\User;
use Validator;

class AuthController extends Controller
{
   /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    
    public function register(Request $request)
    {
        $validatedData = Validator::make($request->all(), 
            [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required',
            ],
        );

        if ($validatedData->fails()) {
            return response()->json(
                    $this->responseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'about' => isset($request['about']) ? $request->about : '',
                'tag_line' => isset($request['tag_line']) ? $request->tag_line : '',
                'password' => Hash::make($request->password),
            ]);
            if (isset($request['avatar'])) {
                $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
            }
            if(isset($request['role'])) {
                $role = Role::find($request['role']);
                if($role) {
                    $user->assignRole($role);
                }
            }
            return response()->json(
                    $this->responseObject(true, 'User registred successfully.', 200, '')
                , 200);
        }
    }

    public function update_user_profile(Request $request) {
        if(isset($request['id'])) {
            $user = User::find($request->id);
            if($user) {
                if (isset($request['avatar'])) {
                    if($user->hasMedia('avatars')){
                        $user->getFirstMedia('avatars')->delete();
                    }
                    $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
                }
                if(isset($request['name'])) {
                    $user->name = $request->name; 
                }
                if(isset($request['about'])) {
                    $user->about = $request->about; 
                }
                if(isset($request['tag_line'])) {
                    $user->tag_line = $request->tag_line; 
                }
                $user->save();
                $user = User::find($request->id);
                return response()->json(
                    $this->responseObject(true, $this->userObject($user), 200, '')
                    , 200
                );
            } else {
                return response()->json(
                    $this->responseObject(false, '', 404, 'User not found')
                    , 404
                );
            }
        } else {
            return response()->json(
                $this->responseObject(false, '', 404, 'User id not found')
                , 404
            );
        }
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(
                $this->responseObject(false, '', 401, 'unauthorized (please re-check your email/password)')
                , 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(
            $this->responseObject(true, $this->userObject(auth()->user()), 200, '')
            , 200
        );
    }

    /**
     * Get the User by id.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser(Request $request)
    {
        $user = User::find($request->id);
        if($user) {
            return response()->json(
                $this->responseObject(true, $this->userObject($user), 200, '')
                , 200
            );
        } else {
            return response()->json(
                $this->responseObject(false, '', 404, 'User not found')
                , 404
            );
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(
                $this->responseObject(true, 'Successfully logged out', 200, '')
            , 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $data = array(
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth()->factory()->getTTL() * 60,
                    'user' => $this->userObject(auth()->user()),
                );
        return response()->json($this->responseObject(true, $data , 200, ''), 200);
    }

    /**
     * Get the user for api response in array structure.
     *
     * @param  user Object
     *
     * @return array
     */
    protected function userObject($user){
        return array(
            "id"=> $user->id,
            "name"=> $user->name,
            "email"=> $user->email,
            "about"=> $user->about,
            "tag_line"=> $user->tag_line,
            "email_verified_at"=> $user->email_verified_at,
            "created_at"=> $user->created_at,
            "updated_at"=> $user->updated_at,
            "role"=> $user->getRoleNames()->first(),
            "avatar"=> str_replace("localhost","localhost:8000",$user->getFirstMediaUrl('avatars')),
        );
    }

    /**
     * Get the reponse for api response in array structure.
     *
     * @param  status, data, responseCode, error
     *
     * @return array
     */
    public function responseObject($status, $data, $responseCode, $error){
        return array(
            'status' => $status,
            'data' => $data,
            'responseCode' => $responseCode,
            'error' => $error
        );
    }
}
