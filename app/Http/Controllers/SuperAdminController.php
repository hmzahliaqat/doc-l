<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

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


}
