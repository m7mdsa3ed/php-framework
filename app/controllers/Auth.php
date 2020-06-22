<?php

namespace App\Controllers;

use App\Vendor\Request\Request;
use App\Models\User;
use App\Vendor\Validator;
use Firebase\JWT\JWT;

class Auth extends Controller {
  
  public function login() {
    
    $request = new Request;
    
    $signWith =  (filter_var($request->username, FILTER_VALIDATE_EMAIL)) ? 'email' : 'username';

    $data = User::where($signWith, $request->username)->get()->first();
    
    if ($data == null) return json(['error' => "Username or Email is invalid"], 400);
    
    if ( !password_verify($request->password, $data->password) ) 
      return json(['error' => "Password is invalid"], 400);
  
    // Remeber me
    $exp = ($request->has('remember') && $request->get('remember') == true)
    ? 60 * 24 * 30  // Month
    : 60 * 4 ;      // 4 Hours 

    // Creating Token      
    $secret_key = env('APP_KEY');
    $issuer     = $_SERVER['SERVER_NAME'];      // this can be the servername
    $audience   = $_SERVER['SERVER_NAME'];
    $issuedat   = time();                       // issued at
    $notbefore  = $issuedat;                    // not before in seconds
    $expire     = $issuedat + ( 60 * $exp );    // expire time in seconds
    $payload    = [
      "iss" => $issuer,
      "aud" => $audience,
      "iat" => $issuedat,
      "nbf" => $notbefore,
      "exp" => $expire,
      'data' => $data
    ];

    // Create JSON Web Token
    $jwt = JWT::encode($payload, $secret_key);

    // Send data
    return json([ 'jwt' => $jwt, 'exp' => $expire, 'user' => $data ], 200);
  }

  public function register() {

    $request = new Request;
    
    $validator = new Validator($request->all(), [
      'username'  => ['required', 'unique:User'],
      'email'     => ['required', 'unique:User'],
      'password'  => ['required', 'confirmed'],
    ]);

    if (!$validator->passed()) return json(['errors' => $validator->errors()], 400);

    $avatar = ($request->hasFile('avatar'))
     ? $request->file('avatar')->upload()
     : NULL;

    if ( User::create([
      'username'    => $request->username,
      'email'       => $request->email,
      'password'    => password_hash($request->password, PASSWORD_BCRYPT),
      'name'        => $request->name,
      'position'    => $request->position,
      'bio'         => $request->bio,
      'avatar'      => $avatar,
    ]) ) return json(['message' => 'User Created'], 201);

    return json(['error' => 'Couldn\'t create' ]);

  }

  public static function user() {

    // Check for authorixation
    if (self::check()) {
    
      $request = new Request;

      // Extract token 
      list(, $token) = explode(' ', $request->server->get('HTTP_AUTHORIZATION'));

      // Get the data from it
      $data = JWT::decode($token, env('APP_KEY'), ['HS256'])->data;

      return $data;
    } 
    return [];
  }

  public static function validateUser() {
    if (self::check()) {
      return json(self::user());
    }
    return json(['error' => 'Unauthorized'], 401);
  }

  public static function check() {

    // Get Authorixation header
    $authHeader = (new Request)->server->get('HTTP_AUTHORIZATION');
    
    // Check whether is set or not 
    if (isset($authHeader) && !empty($authHeader)) {

      // Exrtact Token
      list(, $token) = explode(' ', (new Request)->server->get('HTTP_AUTHORIZATION'));
      
      // Validate the token
      try {
        JWT::decode($token, env('APP_KEY'), ['HS256']);
        return true;
      } catch (\Exception $e) {}
    }
    
    return false;
  }

  public function getUser() {
    return json(self::user());
  }
}