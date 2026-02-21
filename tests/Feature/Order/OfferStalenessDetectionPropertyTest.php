<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\OfferTarget;
use App\Models\Product;
use App\Models\User;
use App\Services\OfferSelector;
use App\Services\PreviewValidator;
use App\Services\PricingCalculator;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Offer Staleness Detection
 * 
 * **Validates: Requirements 9.9, 9.10, 9.11, 9.12, 9.13, 9.14**
 * 
 * Property 38-42: For any confirmation request, the system should detect and reject
 * with HTTP 409 when:
 * - Best offer has changed (different offer selected, new eligible offer appeared)
 * - Offer has expired (current time > end_at)
 * - Offer has become inactive (status != 'active')
 * - Offer has not yet started (current time < start_at)
 * - Private offer targeting has changed (customer no longer in offer_targets)
 */
class OfferStalenessDetectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 38: Best offer change detection
     * 
     * **Validates: Requirements 9.9, 9.10**
     * 
     * For any confirmation request, if the best offer for any product has changed
     * (different offer selected, new eligible offer appeared), the system should
     * reject with HTTP 409 and keep the preview_token in cache.
     */
    #[Test]
    public function best_offer_change_is_detected(): void
    {
        // Feature: order-creation-api, Property 38: Best offer change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create initial offer
            $offer1 = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDays(1),
                'end_at' => now()->addDays(1)
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer1->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10.00,
                'discount_fixed' => null,
                'bonus_product_id' => null,
                'bonus_qty' => null
            ]);
            
            // Create preview data with first offer
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => $offer1->id
                    ]
                ]
            ];
            
            // Create a better offer after preview
            $offer2 = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDays(1),
                'end_at' => now()->addDays(1)
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer2->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 20  // Better offer
            ]);
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect best offer change
            $this->assertFalse(
                $result['valid'],
                "Should detect best offer change when a better offer appears (iteration {$i})"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array when best offer changed (iteration {$i})"
            );
            
            // Verify the change details
            $offerChange = collect($result['changes'])->firstWhere('type', 'best_offer_changed');
            
            $this->assertNotNull(
                $offerChange,
                "Should have a best_offer_changed entry in changes (iteration {$i})"
            );
            
            $this->assertEquals(
                $product->id,
                $offerChange['product_id'],
                "Offer change should reference correct product (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer1->id,
                $offerChange['previous_offer_id'],
                "Should include previous offer ID (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer2->id,
                $offerChange['current_offer_id'],
                "Should include current (better) offer ID (iteration {$i})"
            );
            
            $this->assertEquals(
                'new_better_offer',
                $offerChange['change_reason'],
                "Change reason should be 'new_better_offer' (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 39: Offer expiration detection
     * 
     * **Validates: Requirements 9.11**
     * 
     * For any confirmation request, if any selected offer has expired
     * (current time > end_at), the system should reject with HTTP 409
     * and keep the preview_token in cache.
     */
    #[Test]
    public function offer_expiration_is_detected(): void
    {
        // Feature: order-creation-api, Property 39: Offer expiration detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create offer that will expire
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDays(2),
                'end_at' => now()->addHours(1)  // Will expire soon
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10
            ]);
            
            // Create preview data with offer
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => $offer->id
                    ]
                ]
            ];
            
            // Expire the offer
            $offer->end_at = now()->subHours(1);
            $offer->save();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect offer expiration
            $this->assertFalse(
                $result['valid'],
                "Should detect offer expiration (iteration {$i})"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array when offer expired (iteration {$i})"
            );
            
            // Verify the change details
            $offerChange = collect($result['changes'])->firstWhere('type', 'best_offer_changed');
            
            $this->assertNotNull(
                $offerChange,
                "Should have a best_offer_changed entry when offer expired (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer->id,
                $offerChange['previous_offer_id'],
                "Should include expired offer ID (iteration {$i})"
            );
            
            $this->assertNull(
                $offerChange['current_offer_id'],
                "Current offer should be null when previous offer expired (iteration {$i})"
            );
            
            $this->assertEquals(
                'expired',
                $offerChange['change_reason'],
                "Change reason should be 'expired' (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 40: Offer inactivation detection
     * 
     * **Validates: Requirements 9.12**
     * 
     * For any confirmation request, if any selected offer has become inactive
     * (status != 'active'), the system should reject with HTTP 409 and keep
     * the preview_token in cache.
     */
    #[Test]
    public function offer_inactivation_is_detected(): void
    {
        // Feature: order-creation-api, Property 40: Offer inactivation detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create active offer
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDays(1),
                'end_at' => now()->addDays(1)
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10
            ]);
            
            // Create preview data with active offer
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => $offer->id
                    ]
                ]
            ];
            
        // Deactivate the offer (use 'inactive' as valid status value)
        $offer->status = 'inactive';
        $offer->save();
        
        // Act: Revalidate preview
        $result = $previewValidator->revalidate($previewData, $customer);
        
        // Assert: Should detect offer inactivation
        $this->assertFalse(
            $result['valid'],
            "Should detect offer inactivation (iteration {$i})"
        );
        
        $this->assertNotEmpty(
            $result['changes'],
            "Should have changes array when offer became inactive (iteration {$i})"
        );
            
            // Verify the change details
            $offerChange = collect($result['changes'])->firstWhere('type', 'best_offer_changed');
            
            $this->assertNotNull(
                $offerChange,
                "Should have a best_offer_changed entry when offer became inactive (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer->id,
                $offerChange['previous_offer_id'],
                "Should include inactive offer ID (iteration {$i})"
            );
            
            $this->assertNull(
                $offerChange['current_offer_id'],
                "Current offer should be null when previous offer became inactive (iteration {$i})"
            );
            
            $this->assertEquals(
                'became_inactive',
                $offerChange['change_reason'],
                "Change reason should be 'became_inactive' (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 41: Offer not started detection
     * 
     * **Validates: Requirements 9.13**
     * 
     * For any confirmation request, if any selected offer has not yet started
     * (current time < start_at), the system should reject with HTTP 409 and
     * keep the preview_token in cache.
     */
    #[Test]
    public function offer_not_started_is_detected(): void
    {
        // Feature: order-creation-api, Property 41: Offer not started detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create offer that starts in the future
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDays(1),  // Was active
                'end_at' => now()->addDays(2)
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10
            ]);
            
            // Create preview data with offer
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => $offer->id
                    ]
                ]
            ];
            
            // Move start_at to future (offer not yet started)
            $offer->start_at = now()->addDays(1);
            $offer->save();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect offer not started
            $this->assertFalse(
                $result['valid'],
                "Should detect offer not started (iteration {$i})"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array when offer not started (iteration {$i})"
            );
            
            // Verify the change details
            $offerChange = collect($result['changes'])->firstWhere('type', 'best_offer_changed');
            
            $this->assertNotNull(
                $offerChange,
                "Should have a best_offer_changed entry when offer not started (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer->id,
                $offerChange['previous_offer_id'],
                "Should include not-started offer ID (iteration {$i})"
            );
            
            $this->assertNull(
                $offerChange['current_offer_id'],
                "Current offer should be null when previous offer not started (iteration {$i})"
            );
            
            $this->assertEquals(
                'not_started',
                $offerChange['change_reason'],
                "Change reason should be 'not_started' (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 42: Private offer targeting change detection
     * 
     * **Validates: Requirements 9.14**
     * 
     * For any confirmation request with a private offer, if the customer is no
     * longer in the offer_targets table, the system should reject with HTTP 409
     * and keep the preview_token in cache.
     */
    #[Test]
    public function private_offer_targeting_change_is_detected(): void
    {
        // Feature: order-creation-api, Property 42: Private offer targeting change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create private offer
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'private',
                'status' => 'active',
                'start_at' => now()->subDays(1),
                'end_at' => now()->addDays(1)
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10
            ]);
            
            // Target customer for this offer
            OfferTarget::factory()->create([
                'offer_id' => $offer->id,
                'target_type' => 'customer',
                'target_id' => $customer->id
            ]);
            
            // Create preview data with private offer
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => $offer->id
                    ]
                ]
            ];
            
            // Remove customer from targeting
            OfferTarget::where('offer_id', $offer->id)
                ->where('target_type', 'customer')
                ->where('target_id', $customer->id)
                ->delete();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect targeting change
            $this->assertFalse(
                $result['valid'],
                "Should detect private offer targeting change (iteration {$i})"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array when targeting changed (iteration {$i})"
            );
            
            // Verify the change details
            $offerChange = collect($result['changes'])->firstWhere('type', 'best_offer_changed');
            
            $this->assertNotNull(
                $offerChange,
                "Should have a best_offer_changed entry when targeting changed (iteration {$i})"
            );
            
            $this->assertEquals(
                $offer->id,
                $offerChange['previous_offer_id'],
                "Should include previous private offer ID (iteration {$i})"
            );
            
            $this->assertNull(
                $offerChange['current_offer_id'],
                "Current offer should be null when targeting changed (iteration {$i})"
            );
            
            $this->assertEquals(
                'targeting_changed',
                $offerChange['change_reason'],
                "Change reason should be 'targeting_changed' (iteration {$i})"
            );
        }
    }
}
