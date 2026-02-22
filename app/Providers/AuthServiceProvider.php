<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Address;
use App\Models\Order;
use App\Models\Invoice;
use App\Policies\ProductPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\OfferPolicy;
use App\Policies\AddressPolicy;
use App\Policies\OrderPolicy;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Conversation::class => ConversationPolicy::class,
        Offer::class => OfferPolicy::class,
        Address::class => AddressPolicy::class,
        Order::class => OrderPolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
