<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\ItemCreationRequest;
use App\Observers\ItemCreationRequestObserver;

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
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        ItemCreationRequest::observe(ItemCreationRequestObserver::class);
    }
}
