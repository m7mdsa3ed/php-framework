<?php

namespace App\Controllers;

use App\Models\User;
use App\Vendor\Request\Request;
use App\Vendor\Validator;

class Users extends Controller {
  
  public function all() {
    return json(User::get()->paginate(10));
  }

  public function show($id) {
    return json(User::find($id));
  }

  public function update($id) {

    // Init the request
    $request = new Request;

    if (!Auth::check()) return json(['error'=>'Unauthorized'], 401);
    

    $validate = [];
    if ( $request->has('username') ) { $validate['username'] = ['required', 'unique:User'];}
    if ( $request->has('email') ) { $validate['email'] = ['required', 'unique:User'];}    

    $validator = new Validator($request->all(), $validate);

    if (!$validator->passed()) return json(['errors'=> $validator->errors()], 400);

    
    if ( $request->hasFile('avatar')) {

      // Delete old one
      $old_avatar_without_full_path = 
        ltrim(str_replace(getDirname(), '', User::find(Auth::user()->id)->first()->avatar), '/\\');
      if ( file_exists($old_avatar_without_full_path) ) unlink($old_avatar_without_full_path);

      // Ulpoad new one
      $avatar = $request->file('avatar')->upload();

    } else {
      // The same exist avatar
      $avatar = User::find(Auth::user()->id)->first()->avatar;
    }

    if (User::update($id, [
      'username'    => ($request->username) ?? Auth::user()->username,
      'email'       => ($request->email) ?? Auth::user()->email,
      'name'        => ($request->name) ?? Auth::user()->name,
      'bio'         => ($request->bio) ?? Auth::user()->bio,
      'bio'         => ($request->position) ?? Auth::user()->position,
      'avatar'      => $avatar,
      'updated_at'  => date("Y-m-d H:i:s")
    ]) )  return json(User::find($id));

  }

  public function delete($id) {
    if (Auth::check()) {
      return (User::delete($id))
        ? json(['message' => 'User Delete'])
        : json(['error' => 'Could Not Delete'], 400);
    }
  }

}