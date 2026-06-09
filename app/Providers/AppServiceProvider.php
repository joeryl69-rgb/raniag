<?php

namespace App\Providers;

use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Repositories\IncidentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IncidentRepositoryInterface::class, IncidentRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
