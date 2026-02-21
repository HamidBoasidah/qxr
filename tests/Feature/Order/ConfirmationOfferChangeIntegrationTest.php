<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Confirmation with Offer Change
 * 
 * **Validates: Requirements 9.9, 9.10, 9.18**
 * 
 * This test verifies the complete end-to-end flow when offers change
 * between preview and confirmation, resulting in HTTP 409 response.
 */
class ConfirmationOfferChangeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Confirmation fails when offer expires
     * 
     * **Validates: Requirements 9.9, 9.10, 9.18**
     * 
     * Verifies that:
     * 1. Preview is created with active offer
     * 2. Offer expires before confirmation
     * 3. Confirmation returns HTTP 409
     * 4. Response includes offer change details with 'expired' reason
     * 5. Preview token is kept in cache
     * 6. No order is persisted
     */
    #[Test]
    public function confirmation_fails_when_offer_expires(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '10% Off Expiring Soon',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addMinutes(5)  // Will expire soon
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // DEBUG: Verify offer and product are correctly set up
        $this->assertEquals($company->id, $product->company_user_id);
        $this->assertEquals($company->id, $offer->company_user_id);
        $this->assertDatabaseHas('offer_items', [
            'offer_id' => $offer->id,
            'product_id' => $product->id
        ]);

        // Step 1: Create preview with active offer
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse->assertStatus(200);
        $previewToken = $previewResponse->json('data.preview_token');

        // Verify offer was applied in preview
        $previewItem = $previewResponse->json('data.items.0');
        $this->assertEquals($offer->id, $previewItem['selected_offer_id']);
        $this->assertEquals(100.00, $previewItem['discount_amount']);

        // Step 2: Expire the offer
        $offer->update(['end_at' => now()->subMinute()]);

        // Step 3: Attempt to confirm order
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Preview is no longer valid. Please re-preview your order.'
            ]);

        // Assert: Response includes offer change details
        $details = $confirmResponse->json('details');
        $this->assertNotEmpty($details);
        
        $offerChange = collect($details)->firstWhere('type', 'best_offer_changed');
        $this->assertNotNull($offerChange);
        $this->assertEquals($product->id, $offerChange['product_id']);
        $this->assertEquals($offer->id, $offerChange['previous_offer_id']);
        $this->assertEquals('10% Off Expiring Soon', $offerChange['previous_offer_title']);
        $this->assertEquals('discount_percent', $offerChange['previous_reward_type']);
        $this->assertNull($offerChange['current_offer_id']);
        $this->assertNull($offerChange['current_offer_title']);
        $this->assertNull($offerChange['current_reward_type']);
        $this->assertEquals('expired', $offerChange['change_reason']);

        // Assert: Preview token is kept in cache
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation fails when offer becomes inactive
     * 
     * **Validates: Requirements 9.9, 9.10, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_offer_becomes_inactive(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '15% Off',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50,
            'reward_type' => 'discount_percent',
            'discount_percent' => 15.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 50]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Deactivate the offer
        $offer->update(['status' => 'inactive']);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        $details = $confirmResponse->json('details');
        $offerChange = collect($details)->firstWhere('type', 'best_offer_changed');
        $this->assertNotNull($offerChange);
        $this->assertEquals('became_inactive', $offerChange['change_reason']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation fails when new better offer appears
     * 
     * **Validates: Requirements 9.9, 9.10, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_new_better_offer_appears(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        // Original offer: 10% off
        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '10% Off',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
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

        // Step 1: Create preview with 10% offer
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Verify 10% offer was selected
        $previewItem = $previewResponse->json('data.items.0');
        $this->assertEquals($offer1->id, $previewItem['selected_offer_id']);

        // Step 2: Create new better offer (20% off)
        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '20% Off Flash Sale',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer2->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 20.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        $details = $confirmResponse->json('details');
        $offerChange = collect($details)->firstWhere('type', 'best_offer_changed');
        $this->assertNotNull($offerChange);
        $this->assertEquals($offer1->id, $offerChange['previous_offer_id']);
        $this->assertEquals('10% Off', $offerChange['previous_offer_title']);
        $this->assertEquals($offer2->id, $offerChange['current_offer_id']);
        $this->assertEquals('20% Off Flash Sale', $offerChange['current_offer_title']);
        $this->assertEquals('new_better_offer', $offerChange['change_reason']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation fails when offer is removed and no replacement
     * 
     * **Validates: Requirements 9.9, 9.10, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_offer_removed_no_replacement(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '$50 Off',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_fixed',
            'discount_percent' => null,
            'discount_fixed' => 50.00,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Remove the offer (make inactive)
        $offer->update(['status' => 'inactive']);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        $details = $confirmResponse->json('details');
        $offerChange = collect($details)->firstWhere('type', 'best_offer_changed');
        $this->assertNotNull($offerChange);
        $this->assertEquals($offer->id, $offerChange['previous_offer_id']);
        $this->assertNull($offerChange['current_offer_id']);
        $this->assertEquals('became_inactive', $offerChange['change_reason']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation fails when offer appears where none existed
     * 
     * **Validates: Requirements 9.9, 9.10, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_offer_appears_where_none_existed(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Create preview with no offers
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Verify no offer in preview
        $previewItem = $previewResponse->json('data.items.0');
        $this->assertNull($previewItem['selected_offer_id']);

        // Step 2: Create new offer
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'New 15% Off',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 15.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        $details = $confirmResponse->json('details');
        $offerChange = collect($details)->firstWhere('type', 'best_offer_changed');
        $this->assertNotNull($offerChange);
        $this->assertNull($offerChange['previous_offer_id']);
        $this->assertEquals($offer->id, $offerChange['current_offer_id']);
        $this->assertEquals('New 15% Off', $offerChange['current_offer_title']);
        $this->assertEquals('new_better_offer', $offerChange['change_reason']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }
}
