<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

	/**
	 * user register
	 * @param RegisterRequest $request
	 * @return UserResource
	 */
	public function register(RegisterRequest $request)
	{
		//check user exists
		$user = User::where('cellphone', $request->cellphone)->first();
		if ($user) {
			//if user exists
			return response()->json([
                'cellphone' => 'The selected cellphone is invalid..'
            ], 400);
		}
		$user = new User;
		$user->name = $request->name;
		$user->lastname = $request->lastname;
		$user->cellphone = $request->cellphone;
		$user->password = $request->password;
		$user->save();
		return response()->json(UserResource::make($user));
	}

	/**
	 * User get profile
	 * @param Request $request
	 * @return UserResource
	 */
	public function user(Request $request)
	{
		return response()->json(UserResource::make($request->user()));
	}

	/**
	 * User get profile
	 * @param LoginRequest $request
	 * @return token
	 */
	public function login(LoginRequest $request)
    {
        $user = User::where('cellphone', $request->cellphone)->first();
		if (!$user) {
			//if user not exists 
			return response()->json([
                'cellphone' => 'The selected cellphone is invalid..'
            ], 400);
		}
        auth()->login($user);
        if(!($token = $user->createToken("API TOKEN")->plainTextToken)) {
            return response()->json([
                'code' => 'The selected code is invalid..'
            ], 400);
        }
        return response()->json([
            'token' => $token
        ]);
    }
}
