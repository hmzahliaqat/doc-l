<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Log;
use App\Models\SuperAdminSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Laravel\Cashier\Subscription;
use Spatie\Permission\Models\Role;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SuperAdminController extends Controller
{
   /**
    * Get Stripe client instance
    *
    * @return StripeClient
    */
   private function getStripeClient()
   {
       $stripeSecret = config('cashier.secret');
       return new StripeClient($stripeSecret);
   }

   /**
    * Get subscription statistics including total subscribers and revenue
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function getSubscriptionStats()
   {
       try {
           // Get active subscriptions count
           $activeSubscriptionsCount = Subscription::where('stripe_status', 'active')->count();

           // Get total revenue from Stripe
           $stripe = $this->getStripeClient();

           // Get all successful payments (invoices)
           $invoices = $stripe->invoices->all([
               'status' => 'paid',
               'limit' => 100, // Adjust as needed
           ]);

           // Calculate total revenue
           $totalRevenue = 0;
           foreach ($invoices->data as $invoice) {
               $totalRevenue += $invoice->amount_paid;
           }

           // Convert from cents to dollars
           $totalRevenue = $totalRevenue / 100;

           // Get subscription plans with subscriber counts
           $subscriptionPlans = [];

           // Get all active plans from the SubscriptionPlan model
           $plans = SubscriptionPlan::where('active', true)->get();

           foreach ($plans as $plan) {
               // Count active subscriptions for this plan
               $subscriberCount = Subscription::where('stripe_status', 'active')
                   ->where('stripe_price', $plan->stripe_price_id)
                   ->count();

               $subscriptionPlans[] = [
                   'name' => $plan->name,
                   'subscribers' => $subscriberCount
               ];
           }

           return response()->json([
               'success' => true,
               'data' => [
                   'active_subscribers' => $activeSubscriptionsCount,
                   'total_revenue' => $totalRevenue,
                   'subscription_plans' => $subscriptionPlans
               ]
           ]);
       } catch (ApiErrorException $e) {
           return response()->json([
               'success' => false,
               'message' => 'Error fetching subscription data from Stripe: ' . $e->getMessage()
           ], 500);
       } catch (\Exception $e) {
           return response()->json([
               'success' => false,
               'message' => 'Error calculating subscription statistics: ' . $e->getMessage()
           ], 500);
       }
   }

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

   /**
    * Get the SuperAdmin settings
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function getSettings()
   {
       // Get the first settings record or create a new one if none exists
       $settings = SuperAdminSetting::first() ?? new SuperAdminSetting();

       // If app_logo exists, generate a full URL
       if ($settings->app_logo) {
           $settings->app_logo_url = url(Storage::url($settings->app_logo));
       }

       return response()->json($settings);
   }

   /**
    * Update the SuperAdmin settings
    *
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */
   public function updateSettings(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'app_name' => 'nullable|string|max:255',
           'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
           'video_url' => 'nullable|url|max:255',
           'stripe_app_key' => 'nullable|string|max:255',
           'stripe_secret_key' => 'nullable|string|max:255',
       ]);

       if ($validator->fails()) {
           return response()->json([
               'message' => 'Validation failed',
               'errors' => $validator->errors()
           ], 422);
       }

       // Get the first settings record or create a new one if none exists
       $settings = SuperAdminSetting::first();
       if (!$settings) {
           $settings = new SuperAdminSetting();
       }

       // Update text fields
       if ($request->has('app_name')) {
           $settings->app_name = $request->app_name;
       }

       if ($request->has('video_url')) {
           $settings->video_url = $request->video_url;
       }

       if ($request->has('stripe_app_key')) {
           $settings->stripe_app_key = $request->stripe_app_key;
       }

       if ($request->has('stripe_secret_key')) {
           $settings->stripe_secret_key = $request->stripe_secret_key;
       }

       // Handle logo upload
       if ($request->hasFile('app_logo')) {
           // Delete old logo if exists
           if ($settings->app_logo) {
               Storage::delete($settings->app_logo);
           }

           // Store the new logo
           $path = $request->file('app_logo')->storeAs('logos', time() . '_' . $request->file('app_logo')->getClientOriginalName(), 'public');
           $settings->app_logo = $path;
       }

       $settings->save();

       // Generate full URL for app_logo
       if ($settings->app_logo) {
           $settings->app_logo_url = url(Storage::url($settings->app_logo));
       }

       return response()->json([
           'message' => 'Settings updated successfully',
           'settings' => $settings
       ]);
   }

   /**
    * Delete the SuperAdmin settings
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function deleteSettings()
   {
       $settings = SuperAdminSetting::first();

       if (!$settings) {
           return response()->json([
               'message' => 'No settings found to delete'
           ], 404);
       }

       // Delete logo file if exists
       if ($settings->app_logo) {
           Storage::delete($settings->app_logo);
       }

       // Delete the settings record
       $settings->delete();

       return response()->json([
           'message' => 'Settings deleted successfully'
       ]);
   }

   /**
    * Get the SuperAdmin settings for guest access
    * Returns only app_name, app_logo, and video_url
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function getGuestSettings()
   {
       // Get the first settings record or create a new one if none exists
       $settings = SuperAdminSetting::first() ?? new SuperAdminSetting();

       // If app_logo exists, generate a full URL
       if ($settings->app_logo) {
           $settings->app_logo_url = url(Storage::url($settings->app_logo));
       }

       // Return only the specified fields
       return response()->json([
           'app_name' => $settings->app_name,
           'app_logo' => $settings->app_logo,
           'app_logo_url' => $settings->app_logo_url ?? null,
           'video_url' => $settings->video_url,
       ]);
   }

}
