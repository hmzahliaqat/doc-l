<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SuperAdminSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    /**
     * Get Stripe client instance
     *
     * @return StripeClient
     */
    protected function getStripeClient()
    {
        $settings = SuperAdminSetting::first();

        if (!$settings || !$settings->stripe_secret_key) {
            throw new \Exception('Stripe secret key not found in settings');
        }

        return new StripeClient($settings->stripe_secret_key);
    }

    /**
     * Get Stripe publishable key
     *
     * @return string
     */
    public function getStripeKey()
    {
        $settings = SuperAdminSetting::first();

        if (!$settings || !$settings->stripe_app_key) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe publishable key not found in settings'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'key' => $settings->stripe_app_key
        ]);
    }

    /**
     * Display a listing of the user's subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active and past subscriptions
        $subscriptions = $user->subscriptions()->orderBy('created_at', 'desc')->get();

        // Get current subscription plan details if exists
        $currentSubscription = $user->subscription();
        $currentPlan = null;

        if ($currentSubscription) {
            $planId = $currentSubscription->stripe_price;
            $plan = SubscriptionPlan::where('stripe_price_id', $planId)->first();

            if ($plan) {
                $currentPlan = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'interval' => $plan->interval,
                    'features' => $plan->features,
                    'status' => $currentSubscription->stripe_status,
                    'ends_at' => $currentSubscription->ends_at,
                    'trial_ends_at' => $currentSubscription->trial_ends_at,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'current_plan' => $currentPlan,
                'has_payment_method' => $user->hasDefaultPaymentMethod(),
                'payment_method' => $user->hasDefaultPaymentMethod() ? $user->defaultPaymentMethod() : null,
            ]
        ]);
    }

    /**
     * Subscribe the user to a plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // Check if plan is active
            if (!$plan->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription plan is not available'
                ], 400);
            }

            // If user already has a subscription, we'll swap it
            if ($user->subscribed()) {
                return $this->swap($request);
            }

            // Create or update the customer in Stripe
            $user->createOrGetStripeCustomer();

            // Update the payment method
            $user->updateDefaultPaymentMethod($request->payment_method);

            // Create the subscription
            try {
                $subscription = $user->newSubscription('default', $plan->stripe_price_id)
                    ->create($request->payment_method);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription created successfully',
                    'data' => $subscription
                ]);
            } catch (IncompletePayment $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Additional payment confirmation is required to complete this subscription',
                    'payment_intent' => $exception->payment->id,
                    'payment_intent_client_secret' => $exception->payment->clientSecret(),
                    'payment_intent_status' => $exception->payment->status,
                    'requires_action' => true,
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('Error creating subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change the user's subscription plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function swap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // Check if plan is active
            if (!$plan->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription plan is not available'
                ], 400);
            }

            // Check if user has a subscription
            if (!$user->subscribed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have an active subscription'
                ], 400);
            }

            // Swap the subscription
            $subscription = $user->subscription('default')->swap($plan->stripe_price_id);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan changed successfully',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            Log::error('Error changing subscription plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error changing subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel the user's subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user has a subscription
            if (!$user->subscribed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have an active subscription'
                ], 400);
            }

            // Cancel the subscription at the end of the billing period
            $subscription = $user->subscription('default')->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully. You will have access until the end of your billing period.',
                'data' => [
                    'ends_at' => $subscription->ends_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error canceling subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error canceling subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user has a canceled subscription that is still within its grace period
            if (!$user->subscription('default') || !$user->subscription('default')->onGracePeriod()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No canceled subscription found that can be resumed'
                ], 400);
            }

            // Resume the subscription
            $subscription = $user->subscription('default')->resume();

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            Log::error('Error resuming subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error resuming subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the user's payment method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Create or get the customer
            $user->createOrGetStripeCustomer();

            // Update the payment method
            $user->updateDefaultPaymentMethod($request->payment_method);

            // Sync the payment method to subscriptions
            $user->syncStripeCustomerDetails();

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
                'data' => $user->defaultPaymentMethod()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating payment method: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available subscription plans.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function plans()
    {
        $plans = SubscriptionPlan::where('active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Get available subscription plans for guest users (no authentication required).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function guestPlans()
    {
        $plans = SubscriptionPlan::where('active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Check if the user has subscribed to any plan and return user creation date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSubscriptionStatus(Request $request)
    {
        $user = $request->user();

        // Get original created_at timestamp (not formatted by the accessor)
        $createdAt = $user->getOriginal('created_at');

        return response()->json([
            'success' => true,
            'data' => [
                'is_subscribed' => $user->subscribed(),
                'created_at' => $createdAt
            ]
        ]);
    }
}
