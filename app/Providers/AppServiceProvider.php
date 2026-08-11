<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
        //
         ItemCreationRequest::observe(ItemCreationRequestObserver::class);
    }
}
