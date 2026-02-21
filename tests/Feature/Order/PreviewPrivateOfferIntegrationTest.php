<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\OfferTarget;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Preview with Private Offer
 * 
 * **Validates: Requirements 5.3**
 * 
 * This test verifies the complete end-to-end preview flow with private offers
 * and customer targeting validation.
 */
class PreviewPrivateOfferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Preview with private offer for targeted customer
     * 
     * **Validates: Requirements 5.3**
     * 
     * Verifies that:
     * 1. Private offer is applied when customer is in offer_targets
     * 2. Pricing calculations are correct with private offer
     * 3. Preview data includes private offer details
     */
    #[Test]
    public function preview_applies_private_offer_for_targeted_customer(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $targetedCustomer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        // Create private offer
        $privateOffer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'VIP 20% Off',
            'scope' => 'private',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $privateOffer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 20.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Target the customer
        OfferTarget::factory()->create([
            'offer_id' => $privateOffer->id,
            'customer_user_id' => $targetedCustomer->id
        ]);

        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test private offer',
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        // Act: Submit preview request as targeted customer
        $response = $this->actingAs($targetedCustomer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert: HTTP response
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $responseData = $response->json('data');

        // Assert: Private offer applied
        $item = $responseData['items'][0];
        $this->assertEquals($privateOffer->id, $item['selected_offer_id']);
        $this->assertEquals('VIP 20% Off', $item['offer_title']);
        
        // Calculation: 20% off 100 items at $10.00
        // - unit_price = 10.00
        // - line_subtotal = 100 × 10.00 = 1000.00
        // - discount = (100 × 10.00 × 20 / 100) × 1 = 200.00
        // - final_total = 1000.00 - 200.00 = 800.00
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(200.00, $item['discount_amount']);
        $this->assertEquals(800.00, $item['final_total']);

        // Assert: Totals
        $this->assertEquals(1000.00, $responseData['subtotal']);
        $this->assertEquals(200.00, $responseData['total_discount']);
        $this->assertEquals(800.00, $responseData['final_total']);

        // Assert: Preview stored in cache
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($targetedCustomer->id, $cachedPreview['customer_user_id']);
    }

    /**
     * Test: Preview does not apply private offer for non-targeted customer
     * 
     * **Validates: Requirements 5.3**
     * 
     * Verifies that:
     * 1. Private offer is NOT applied when customer is not in offer_targets
     * 2. No discount is applied
     */
    #[Test]
    public function preview_does_not_apply_private_offer_for_non_targeted_customer(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $targetedCustomer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $nonTargetedCustomer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        // Create private offer
        $privateOffer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'VIP 20% Off',
            'scope' => 'private',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $privateOffer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 20.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Target only the first customer
        OfferTarget::factory()->create([
            'offer_id' => $privateOffer->id,
            'customer_user_id' => $targetedCustomer->id
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        // Act: Submit preview request as non-targeted customer
        $response = $this->actingAs($nonTargetedCustomer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert: HTTP response
        $response->assertStatus(200);

        $responseData = $response->json('data');

        // Assert: Private offer NOT applied
        $item = $responseData['items'][0];
        $this->assertNull($item['selected_offer_id']);
        $this->assertNull($item['offer_title']);
        
        // No discount applied
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(1000.00, $item['final_total']);

        // Assert: Totals
        $this->assertEquals(1000.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(1000.00, $responseData['final_total']);
    }

    /**
     * Test: Preview prefers private offer over public offer when both eligible
     * 
     * **Validates: Requirements 5.3, 5.10**
     * 
     * Verifies that:
     * 1. When both private and public offers are eligible
     * 2. The offer with highest effective value is selected
     */
    #[Test]
    public function preview_selects_best_offer_between_private_and_public(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        // Create public offer (10% off)
        $publicOffer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'Public 10% Off',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $publicOffer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Create private offer (20% off - better)
        $privateOffer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'VIP 20% Off',
            'scope' => 'private',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $privateOffer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 20.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Target the customer for private offer
        OfferTarget::factory()->create([
            'offer_id' => $privateOffer->id,
            'customer_user_id' => $customer->id
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        // Act: Submit preview request
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert: HTTP response
        $response->assertStatus(200);

        $responseData = $response->json('data');

        // Assert: Private offer selected (better effective value)
        $item = $responseData['items'][0];
        $this->assertEquals($privateOffer->id, $item['selected_offer_id']);
        $this->assertEquals('VIP 20% Off', $item['offer_title']);
        
        // 20% discount applied
        $this->assertEquals(200.00, $item['discount_amount']);
        $this->assertEquals(800.00, $item['final_total']);
    }
}
