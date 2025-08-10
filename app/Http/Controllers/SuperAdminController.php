<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{

   public function getStats()
   {

     $documents =  Document::get()->count();
     $companies = User::whereDoesntHave('roles', function ($query) {
           $query->where('name', 'super-admin');
     })->get()->count();

     $logs = Log::with('user', 'employee', 'document')->get();

     return response()->json(['documents' => $documents, 'companies' => $companies, 'logs' => $logs]);


   }


   public function companiesDetails()
   {
       $company_details = User::whereDoesntHave('roles', function ($query) {
           $query->where('name', 'super-admin');
       })->with(['employees', 'documents'])->get();

       return response()->json($company_details);

   }

   /**
    * Get all superadmin users
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function getSuperadmins()
   {
       $superadmins = User::role('super-admin')->get();

       return response()->json($superadmins);
   }

   /**
    * Create a new superadmin user
    *
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */
   public function createSuperadmin(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'name' => ['required', 'string', 'max:255'],
           'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
           'password' => ['required', 'confirmed', Rules\Password::defaults()],
       ]);

       if ($validator->fails()) {
           return response()->json([
               'message' => 'Validation failed',
               'errors' => $validator->errors()
           ], 422);
       }

       // Create user with email_verified_at set to now()
       $user = User::create([
           'name' => $request->name,
           'email' => $request->email,
           'password' => Hash::make($request->password),
           'email_verified_at' => now(),
       ]);

       // Assign super-admin role
       $user->assignRole('super-admin');

       return response()->json([
           'message' => 'Superadmin created successfully',
           'user' => $user
       ], 201);
   }

   /**
    * Delete a superadmin user
    *
    * @param int $id
    * @return \Illuminate\Http\JsonResponse
    */
   public function deleteSuperadmin($id)
   {
       // Find the user
       $user = User::find($id);

       // Check if user exists
       if (!$user) {
           return response()->json([
               'message' => 'User not found'
           ], 404);
       }

       // Check if user is a superadmin
       if (!$user->hasRole('super-admin')) {
           return response()->json([
               'message' => 'User is not a superadmin'
           ], 400);
       }

       // Count total superadmins to prevent deleting the last one
       $superadminCount = User::role('super-admin')->count();
       if ($superadminCount <= 1) {
           return response()->json([
               'message' => 'Cannot delete the last superadmin'
           ], 400);
       }

       // Delete the user
       $user->delete();

       return response()->json([
           'message' => 'Superadmin deleted successfully'
       ]);
   }

}
