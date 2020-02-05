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
        $validatedData = Validator::make($request->all(), ['email' => 'required|unique:users']);

        if ($validatedData->fails()) {
            return response()->json([
                    'status' => false,
                    'data' => [],
                    'responseCode' => 400,
                    'error' => $validatedData->errors()->first(),
                ], 400);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
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
            return response()->json([
                    'status' => true,
                    'data' => $user,
                    'responseCode' => 200,
                    'error' => ''
            ], 200);
        }
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'data' => '',
                'responseCode' => 401,
                'error' => 'unauthorized (please re-check your email/password)'
            ], 401);
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
        $userData = auth()->user();
        $userData->getRoleNames();
        return response()->json(
            [
                'status' => true,
                'data' => $userData,
                'responseCode' => 200,
                'error' => ''
            ], 200
        );
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
            [
                'status' => true,
                'data' => 'Successfully logged out',
                'responseCode' => 200,
                'error' => ''
            ], 200);
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
        return response()->json([
            'status' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => auth()->user(),
            ],
            'responseCode' => 200,
            'error' => ''
        ]);
    }
}
