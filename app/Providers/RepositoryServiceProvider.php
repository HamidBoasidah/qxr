<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Repositories
use App\Repositories\UserRepository;
use App\Repositories\AreaRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\GovernorateRepository;
// Models
use App\Models\User;
use App\Models\Area;
use App\Models\District;
use App\Models\Governorate;

// Services
use App\Services\UserService;
use App\Services\AreaService;
use App\Services\DistrictService;
use App\Services\GovernorateService;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepository::class, fn($app) => new UserRepository($app->make(User::class)));
        $this->app->bind(AreaRepository::class, fn($app) => new AreaRepository($app->make(Area::class)));
        $this->app->bind(DistrictRepository::class, fn($app) => new DistrictRepository($app->make(District::class)));
        $this->app->bind(GovernorateRepository::class, fn($app) => new GovernorateRepository($app->make(Governorate::class)));
        

        // Bind Services to their Repositories
        $this->app->bind(UserService::class, fn($app) => new UserService($app->make(UserRepository::class)));
        $this->app->bind(AreaService::class, fn($app) => new AreaService($app->make(AreaRepository::class)));
        $this->app->bind(DistrictService::class, fn($app) => new DistrictService($app->make(DistrictRepository::class)));
        $this->app->bind(GovernorateService::class, fn($app) => new GovernorateService($app->make(GovernorateRepository::class)));
    }
}
