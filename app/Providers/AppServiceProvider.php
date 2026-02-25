<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Load settings from DB related to Google OAuth
            $googleSettings = \App\Models\Setting::where('group', 'google_oauth')->pluck('value', 'key');
            
            if ($googleSettings->isNotEmpty()) {
                if ($googleSettings->has('GOOGLE_CLIENT_ID')) {
                    config(['services.google.client_id' => $googleSettings['GOOGLE_CLIENT_ID']]);
                }
                
                if ($googleSettings->has('GOOGLE_CLIENT_SECRET')) {
                    config(['services.google.client_secret' => $googleSettings['GOOGLE_CLIENT_SECRET']]);
                }
            }
        } catch (\Exception $e) {
            // Database might not be set up yet or settings table is missing
        }
    }
}
