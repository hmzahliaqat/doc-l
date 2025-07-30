<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UpdateProfileController extends Controller
{

    public function profileInformation(Request $request)
    {

      $user =  User::where('email', $request->email)->firstOrFail();
      $user->update($request->all());

      return response()->json($user, 200);
    }




}
