<?php

namespace Tests\Unit\Order;

use App\Services\OfferSelector;
use App\Models\Product;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Zero Multiplier Handling
 * 
 * **Validates: Requirements 5.4, 5.5**
 * 
 * Tests that offers with multiplier = 0 (qty < min_qty)
 * are correctly excluded from selection.
 * 
 * Multiplier = floor(qty / min_qty)
 * If multiplier = 0, offer is not eligible.
 */
class ZeroMultiplierHandlingTest extends TestCase
{
    use RefreshDatabase;

    private OfferSelector $selector;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(OrderRepository::class);
        $this->selector = new OfferSelector($this->repository);
    }

    /**
     * Test that qty < min_qty results in no offer selection
     * 
     * Example: qty=5, min_qty=10 → multiplier = floor(5/10) = 0
     * Expected: Offer should not be selected
     */
    #[Test]
    public function it_excludes_offer_when_qty_less_than_min_qty(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Bulk Discount Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 10,  // Minimum 10 units required
            'reward_type' => 'discount_percent',
            'discount_percent' => 20.00
        ]);

        // Act: Try to select offer with qty=5 (less than min_qty=10)
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: No offer should be selected (multiplier = 0)
        $this->assertNull($selectedOffer, 
            'Offer should not be selected when qty (5) < min_qty (10)');
    }

    /**
     * Test boundary: qty = min_qty - 1 results in multiplier = 0
     */
    #[Test]
    public function it_excludes_offer_at_boundary_min_qty_minus_one(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 50.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Min 20 Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 20,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 10.00
        ]);

        // Act: qty = 19 (min_qty - 1)
        $selectedOffer = $this->selector->selectBestOffer($product, 19, $customer->id);

        // Assert: multiplier = floor(19/20) = 0, offer should not be selected
        $this->assertNull($selectedOffer, 
            'Offer should not be selected when qty = min_qty - 1');
    }

    /**
     * Test that qty = min_qty results in multiplier = 1 (offer IS selected)
     * 
     * This validates the boundary where multiplier transitions from 0 to 1
     */
    #[Test]
    public function it_selects_offer_when_qty_equals_min_qty(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Min 10 Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 10,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 2
        ]);

        // Act: qty = 10 (exactly min_qty)
        $selectedOffer = $this->selector->selectBestOffer($product, 10, $customer->id);

        // Assert: multiplier = floor(10/10) = 1, offer SHOULD be selected
        $this->assertNotNull($selectedOffer, 
            'Offer should be selected when qty = min_qty (multiplier = 1)');
        $this->assertEquals($offer->id, $selectedOffer->id);
    }

    /**
     * Test with very high min_qty and low order qty
     */
    #[Test]
    public function it_excludes_offer_with_very_high_min_qty(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 25.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Wholesale Only Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1000,  // Very high minimum
            'reward_type' => 'discount_percent',
            'discount_percent' => 30.00
        ]);

        // Act: Order only 100 units
        $selectedOffer = $this->selector->selectBestOffer($product, 100, $customer->id);

        // Assert: multiplier = floor(100/1000) = 0, no offer
        $this->assertNull($selectedOffer, 
            'Offer should not be selected when qty (100) << min_qty (1000)');
    }

    /**
     * Test with qty = 1 and min_qty = 2 (common e-commerce scenario)
     */
    #[Test]
    public function it_excludes_offer_for_single_item_when_min_qty_is_two(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // "Buy 2, Get 10% off" offer
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Buy 2 or More'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 2,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Act: Customer orders only 1 item
        $selectedOffer = $this->selector->selectBestOffer($product, 1, $customer->id);

        // Assert: multiplier = floor(1/2) = 0, no offer
        $this->assertNull($selectedOffer, 
            'Offer requiring min_qty=2 should not apply to single item purchase');
    }

    /**
     * Test multiple competing offers where one has multiplier = 0
     * 
     * Ensures zero-multiplier offers are filtered out before comparison
     */
    #[Test]
    public function it_filters_out_zero_multiplier_offers_before_comparison(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        // Offer 1: min_qty = 100 (multiplier = 0 for qty = 50)
        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Huge Bulk Discount'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer1->id,
            'product_id' => $product->id,
            'min_qty' => 100,  // Won't qualify
            'reward_type' => 'discount_percent',
            'discount_percent' => 50.00 // Better discount but can't use
        ]);

        // Offer 2: min_qty = 10 (multiplier = 5 for qty = 50)
        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Small Bulk Discount'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer2->id,
            'product_id' => $product->id,
            'min_qty' => 10,  // Will qualify
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00 // Smaller discount but usable
        ]);

        // Act: Order 50 units
        $selectedOffer = $this->selector->selectBestOffer($product, 50, $customer->id);

        // Assert: Should select offer2 (only eligible offer)
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offer2->id, $selectedOffer->id, 
            'Should select the only eligible offer (offer2) and skip zero-multiplier offer1');
    }

    /**
     * Test edge case: min_qty = 1 should always have multiplier >= 1 for any qty >= 1
     */
    #[Test]
    public function it_always_selects_offer_with_min_qty_one(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Always Active Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,  // Applies to any quantity
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 1.00
        ]);

        // Act: Test with various quantities
        foreach ([1, 2, 5, 10, 100] as $qty) {
            $selectedOffer = $this->selector->selectBestOffer($product, $qty, $customer->id);
            
            // Assert: Should always select the offer
            $this->assertNotNull($selectedOffer, 
                "Offer with min_qty=1 should be selected for qty={$qty}");
            $this->assertEquals($offer->id, $selectedOffer->id);
        }
    }
}
