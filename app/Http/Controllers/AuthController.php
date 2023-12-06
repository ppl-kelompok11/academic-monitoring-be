<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        $user = DB::table('users')
            ->select('users.*')
            ->where('email', $credentials['email'])
            ->orWhere('username', $credentials['email'])
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // dd($user);
        // get ref table
        $user->name = DB::table($user->ref_table)->select('name')->where('id', $user->ref_id)->first()->name;

        // unset email
        if (isset($credentials['email'])) {
            unset($credentials['username']);
        }

        if (isset($user->username)) {
            unset($credentials['email']);
            $credentials['username'] = $user->username;
        }


        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        return response()->json([
            'success' => true,
            'message' => 'Login success',
            'token' => $token,
            'profile' => $user
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
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
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function changePassword()
    {
        $credentials = request(['old_password', 'new_password']);
        $validator = Validator::make($credentials, [
            'old_password' => 'required',
            'new_password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = DB::table('users')
            ->select('users.*')
            ->where('id', auth()->user()->id)
            ->first();

        if (!password_verify($credentials['old_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Old password is wrong',
            ], 422);
        }

        DB::table('users')->where('id', auth()->user()->id)->update([
            'password' => bcrypt($credentials['new_password'])
        ]);



        return response()->json([
            'success' => true,
            'message' => 'Password changed'
        ]);
    }
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:users,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            DB::table('users')->where('id', $request->id)->update([
                'password' => bcrypt('123456')
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Password successfully reset'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Internal server error"
            ], 500);
        }
    }
}
