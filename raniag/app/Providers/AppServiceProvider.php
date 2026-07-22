<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Incident;
use App\Policies\AssignmentPolicy;
use App\Policies\IncidentPolicy;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Repositories\IncidentRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IncidentRepositoryInterface::class, IncidentRepository::class);
    }

    public function boot(): void
    {
        // Register policies for authorization
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
    }
}
