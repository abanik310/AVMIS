<?php

namespace App\Providers;


use App\Http\Repositories\data\DataInterface;
use App\Http\Repositories\data\DataRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    
    public function register(): void
    {

        //
        $this->app->bind(DataInterface::class, DataRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
