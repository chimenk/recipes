<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Hash;

class AuthController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth:api')->only('logout');
    }

    public function register(Request $req)
    {
    	$this->validate($req, [
    		'name' => 'required|max:255',
    		'email' => 'required|email|unique:users',
    		'password' => 'required|between:6,25|confirmed'
    	]);

    	$user = new User($req->all());
        $user->password = bcrypt($req->password);
        $user->save();

        return response()->json(['registered' => true]);
    }

    public function login(Request $req)
    {
        $this->validate($req, [
            'email' => 'required|email|',
            'password' => 'required|between:6,25'
        ]);

        $user = User::where('email', $req->email)->first();

        if($user && Hash::check($req->password, $user->password))
        {
            $user->api_token = str_random(60);
            $user->save();

            return response()->json(['authenticated' => true, 'api_token' => $user->api_token, 'user_id' => $user->id]);
        }

        return response()->json(['email' => ['Provided email false']], 422);
    }

    public function logout(Request $req)
    {
        $user = $req->user();
        $user->api_token = null;
        $user->save();

        return response()->json(['logged_out' => true]);
    }
}
