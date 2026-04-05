<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Repositories
use App\Repositories\UserRepository;
use App\Repositories\AreaRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\GovernorateRepository;
use App\Repositories\ReturnPolicyRepository;
use App\Repositories\ReturnInvoiceRepository;
// Models
use App\Models\User;
use App\Models\Area;
use App\Models\District;
use App\Models\Governorate;
use App\Models\ReturnPolicy;
use App\Models\ReturnInvoice;

// Services
use App\Services\UserService;
use App\Services\AreaService;
use App\Services\DistrictService;
use App\Services\GovernorateService;
use App\Services\ReturnPolicyService;
use App\Services\ReturnInvoiceService;
use App\Services\ReturnRequestValidator;
use App\Services\ReturnRefundCalculator;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepository::class, fn($app) => new UserRepository($app->make(User::class)));
        $this->app->bind(AreaRepository::class, fn($app) => new AreaRepository($app->make(Area::class)));
        $this->app->bind(DistrictRepository::class, fn($app) => new DistrictRepository($app->make(District::class)));
        $this->app->bind(GovernorateRepository::class, fn($app) => new GovernorateRepository($app->make(Governorate::class)));
        $this->app->bind(ReturnPolicyRepository::class, fn($app) => new ReturnPolicyRepository($app->make(ReturnPolicy::class)));
        $this->app->bind(ReturnInvoiceRepository::class, fn($app) => new ReturnInvoiceRepository($app->make(ReturnInvoice::class)));

        // Bind Services to their Repositories
        $this->app->bind(UserService::class, fn($app) => new UserService($app->make(UserRepository::class)));
        $this->app->bind(AreaService::class, fn($app) => new AreaService($app->make(AreaRepository::class)));
        $this->app->bind(DistrictService::class, fn($app) => new DistrictService($app->make(DistrictRepository::class)));
        $this->app->bind(GovernorateService::class, fn($app) => new GovernorateService($app->make(GovernorateRepository::class)));
        $this->app->bind(ReturnPolicyService::class, fn($app) => new ReturnPolicyService(
            $app->make(ReturnPolicyRepository::class),
        ));
        $this->app->bind(ReturnInvoiceService::class, fn($app) => new ReturnInvoiceService(
            $app->make(ReturnRequestValidator::class),
            $app->make(ReturnRefundCalculator::class),
            $app->make(ReturnInvoiceRepository::class),
        ));
    }
}
