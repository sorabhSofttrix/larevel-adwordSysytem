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
                'parent_id' => 'required',
                'add_by' => 'required',
                'role' => 'required',
                'avatar' => 'mimes:jpeg,jpg,png,gif'
            ],
        );

        if ($validatedData->fails()) {
            return response()->json(
                    getResponseObject(false, array(), 400, $validatedData->errors()->first())
                    , 400);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'parent_id' => $request->parent_id,
                'add_by' => $request->add_by,
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
                    getResponseObject(true, 'User registred successfully.', 200, '')
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
                    getResponseObject(true, $this->userObject($user), 200, '')
                    , 200
                );
            } else {
                return response()->json(
                    getResponseObject(false, '', 404, 'User not found')
                    , 404
                );
            }
        } else {
            return response()->json(
                getResponseObject(false, '', 404, 'User id not found')
                , 404
            );
        }
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(
                getResponseObject(false, '', 401, 'unauthorized (please re-check your email/password)')
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
            getResponseObject(true, $this->userObject(auth()->user()), 200, '')
            , 200
        );
    }

    /**
     * Get whole team with sorted with roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersTeamByRoles(Request $request)
    {
        $members = array('admins' => array(), 'directors' => array(), 'managers' => array());
        $mainUser = User::find($request->id);
        if($mainUser) {
            switch ($mainUser->Roles()->pluck('id')->first()) {
                case 1:
                    $admins = $mainUser->children()->get();
                    foreach ($admins as $key => $value) {
                        $adminF = $this->teamUserObject($value);
                        foreach ($value->children as $key => $directors) {
                            $directorF = $this->teamUserObject($directors);
                            foreach ($directors->children as $key => $managers) {
                                $managerF = $this->teamUserObject($managers);
                                $members['managers'][] = $managerF;
                            }
                            $members['directors'][] = $directorF;
                        }
                        $members['admins'][] = $adminF;
                    }
                    break;
                case 2:
                    $directors = $mainUser->children()->get();
                    foreach ($directors as $key => $value) {
                        $directorF = $this->teamUserObject($value);
                        foreach ($value->children as $key => $managers) {
                            $managerF = $this->teamUserObject($managers);
                            $members['managers'][] = $managerF;
                        }
                        $members['directors'][] = $directorF;
                    }
                        $members['admins'][] = $this->teamUserObject($mainUser);
                    break;
                case 3:
                    $managers = $mainUser->children()->get();
                    foreach ($managers as $key => $value) {
                        $managerF = $this->teamUserObject($value);
                        $members['managers'][] = $managerF;
                    }
                    $members['directors'][] = $this->teamUserObject($mainUser);
                    $members['admins'][] = ($mainUser->parent_id) ? 
                                        $this->teamUserObject(User::find($mainUser->parent_id)) : null;
                    break;
                case 4:
                    $members['managers'][] = $this->teamUserObject($mainUser);
                    $members['directors'][] = ($mainUser->parent_id) ? 
                                        $this->teamUserObject(User::find($mainUser->parent_id)) : null;
                    $members['admins'][] = ($members['directors'][0]['parent_id']) ? 
                                        $this->teamUserObject(User::find($members['directors'][0]['parent_id'])) : null;
                    break;
            }
            $finalResult = $this->teamUserObject($mainUser);
            $finalResult['members'] = $members;
            return response()->json(
                    getResponseObject(true, $finalResult , 200, '')
                    , 200
                );
        } else {
            return response()->json(
                getResponseObject(true, '', 404, 'User not found.')
                , 404
            );
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersTeam(Request $request)
    {
        $mainUser = User::find($request->id);
        if($mainUser) {
            $users = $mainUser->children()->get();
            $fomattedUser = [];
            foreach ($users as $key => $value) {
                $user = $this->teamUserObject($value);
                foreach ($value->children as $key => $children) {
                    $child = $this->teamUserObject($children);
                    if($user['role_id'] != 4) {
                        foreach ($children->children as $key => $granChildren) {
                            $grandChild = $this->teamUserObject($granChildren);
                            $child['children'][] = $grandChild;
                        }
                    }
                    $user['children'][] = $child;
                }
                $fomattedUser[] = $user;
            }
            $finalResult = $this->teamUserObject($mainUser);
            $finalResult['parent'] = ($mainUser->parent_id) ? 
                                        $this->teamUserObject(User::find($mainUser->parent_id)) : [];
            $finalResult['children'] = $fomattedUser;
            return response()->json(
                getResponseObject(true, $finalResult, 200, '')
                , 200
            );
        } else {
            return response()->json(
                getResponseObject(true, '', 404, 'User not found.')
                , 404
            );
        }
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
                getResponseObject(true, $this->userObject($user), 200, '')
                , 200
            );
        } else {
            return response()->json(
                getResponseObject(false, '', 404, 'User not found')
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
                getResponseObject(true, 'Successfully logged out', 200, '')
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
        $user = auth()->user();
        $crtuser = $this->userObject($user);
        $crtuser['dashboard'] = $user->dashboardData();
        $data = array(
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth()->factory()->getTTL() * 60,
                    'user' => $crtuser,
                );
        return response()->json(getResponseObject(true, $data , 200, ''), 200);
    }

    
    /**
     * Get the user for api response in array structure.
     *
     * @param  user Object
     *
     * @return array
     */
    protected function teamUserObject($user){
        if($user) {
            $roles = $user->Roles();
            return array(
                "id"=> $user->id,
                "name"=> $user->name,
                "email"=> $user->email,
                "about"=> $user->about,
                "tag_line"=> $user->tag_line,
                "parent_id"=> $user->parent_id,
                "add_by"=> $user->add_by,
                "role"=> $roles->pluck('name')->first(),
                "role_id"=> $roles->pluck('id')->first(),
                "avatar"=> str_replace("localhost","localhost:8000",$user->getFirstMediaUrl('avatars')),
                "children" => [],
                "parent" => null,
                "dashboard" => $user->dashboardData(),
            );
        } else {
            return array();
        }
    }

    /**
     * Get the user for api response in array structure.
     *
     * @param  user Object
     *
     * @return array
     */
    protected function userObject($user){
        if($user) {
            $roles = $user->Roles();
            return array(
                "id"=> $user->id,
                "name"=> $user->name,
                "email"=> $user->email,
                "about"=> $user->about,
                "tag_line"=> $user->tag_line,
                "parent_id"=> $user->parent_id,
                "add_by"=> $user->add_by,
                "email_verified_at"=> $user->email_verified_at,
                "created_at"=> $user->created_at,
                "updated_at"=> $user->updated_at,
                "role"=> $roles->pluck('name')->first(),
                "role_id"=> $roles->pluck('id')->first(),
                "avatar"=> str_replace("localhost","localhost:8000",$user->getFirstMediaUrl('avatars')),
                "children" => [],
                "parent" => null,
            );
        } else {
            return array();
        }
    }
}
