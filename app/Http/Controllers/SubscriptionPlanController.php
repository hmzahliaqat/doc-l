<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SuperAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SubscriptionPlanController extends Controller
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
     * Display a listing of the subscription plans.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Store a newly created subscription plan in storage and Stripe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|string|in:day,week,month,year',
            'features' => 'nullable|array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stripe = $this->getStripeClient();

            // Create product in Stripe
            $product = $stripe->products->create([
                'name' => $request->name,
                'description' => $request->description,
                'active' => $request->has('active') ? $request->active : true,
            ]);

            // Create price in Stripe
            $price = $stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => (int)($request->price * 100), // Convert to cents
                'currency' => strtolower($request->currency),
                'recurring' => [
                    'interval' => $request->interval,
                ],
            ]);

            // Create subscription plan in database
            $plan = SubscriptionPlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'stripe_product_id' => $product->id,
                'stripe_price_id' => $price->id,
                'price' => $request->price,
                'currency' => strtoupper($request->currency),
                'interval' => $request->interval,
                'features' => $request->features,
                'active' => $request->has('active') ? $request->active : true,
                'sort_order' => $request->has('sort_order') ? $request->sort_order : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully',
                'data' => $plan
            ], 201);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription plan in Stripe: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error creating subscription plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified subscription plan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $plan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found'
            ], 404);
        }
    }

    /**
     * Update the specified subscription plan in storage and Stripe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'currency' => 'string|size:3',
            'interval' => 'string|in:day,week,month,year',
            'features' => 'nullable|array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $stripe = $this->getStripeClient();

            // Update product in Stripe if name or description changed
            if ($request->has('name') || $request->has('description') || $request->has('active')) {
                $productParams = [];

                if ($request->has('name')) {
                    $productParams['name'] = $request->name;
                }

                if ($request->has('description')) {
                    $productParams['description'] = $request->description;
                }

                if ($request->has('active')) {
                    $productParams['active'] = $request->active;
                }

                if (!empty($productParams)) {
                    $stripe->products->update($plan->stripe_product_id, $productParams);
                }
            }

            // If price related fields changed, create a new price in Stripe
            // Note: Stripe doesn't allow updating prices, so we create a new one
            if ($request->has('price') || $request->has('currency') || $request->has('interval')) {
                $price = $stripe->prices->create([
                    'product' => $plan->stripe_product_id,
                    'unit_amount' => $request->has('price') ? (int)($request->price * 100) : (int)($plan->price * 100),
                    'currency' => $request->has('currency') ? strtolower($request->currency) : strtolower($plan->currency),
                    'recurring' => [
                        'interval' => $request->has('interval') ? $request->interval : $plan->interval,
                    ],
                ]);

                // Update the price ID in the plan
                $plan->stripe_price_id = $price->id;
            }

            // Update the plan in the database
            if ($request->has('name')) {
                $plan->name = $request->name;
            }

            if ($request->has('description')) {
                $plan->description = $request->description;
            }

            if ($request->has('price')) {
                $plan->price = $request->price;
            }

            if ($request->has('currency')) {
                $plan->currency = strtoupper($request->currency);
            }

            if ($request->has('interval')) {
                $plan->interval = $request->interval;
            }

            if ($request->has('features')) {
                $plan->features = $request->features;
            }

            if ($request->has('active')) {
                $plan->active = $request->active;
            }

            if ($request->has('sort_order')) {
                $plan->sort_order = $request->sort_order;
            }

            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully',
                'data' => $plan
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating subscription plan in Stripe: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating subscription plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified subscription plan from storage and archive in Stripe.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $stripe = $this->getStripeClient();

            // Archive the product in Stripe (Stripe doesn't allow deleting products that have been used)
            $stripe->products->update($plan->stripe_product_id, [
                'active' => false
            ]);

            // Delete the plan from the database
            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully'
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subscription plan in Stripe: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error deleting subscription plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of the specified subscription plan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive($id)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $stripe = $this->getStripeClient();

            // Toggle the active status
            $plan->active = !$plan->active;
            $plan->save();

            // Update the product in Stripe
            $stripe->products->update($plan->stripe_product_id, [
                'active' => $plan->active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan status updated successfully',
                'data' => $plan
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating subscription plan in Stripe: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating subscription plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }
}
