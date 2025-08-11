<?php

namespace App\Providers;

use App\Models\SuperAdminSetting;
use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set Stripe API key from SuperAdminSetting model
        try {
            $settings = SuperAdminSetting::first();

            if ($settings && !empty($settings->stripe_secret_key)) {
                // Set the Stripe API key for the Stripe PHP SDK
                Stripe::setApiKey($settings->stripe_secret_key);

                // Set the Stripe API key for Laravel Cashier
                config(['cashier.secret' => $settings->stripe_secret_key]);
                config(['services.stripe.secret' => $settings->stripe_secret_key]);

                // Set the Stripe API key for the current request
                if (request()) {
                    request()->headers->set('Stripe-Secret', $settings->stripe_secret_key);
                }

                // Log success
                \Illuminate\Support\Facades\Log::info('Stripe API key set from SuperAdminSetting');
            } else {
                // Log warning
                \Illuminate\Support\Facades\Log::warning('Stripe API key not found in SuperAdminSetting');
            }
        } catch (\Exception $e) {
            // Log error
            \Illuminate\Support\Facades\Log::error('Error setting Stripe API key: ' . $e->getMessage());
        }
    }
}
