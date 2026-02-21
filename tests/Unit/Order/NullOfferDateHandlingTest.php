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
 * Unit Test: Null Offer Date Handling
 * 
 * **Validates: Requirements 5.2**
 * 
 * Tests that offers with null start_at and/or null end_at
 * are correctly treated as:
 * - null start_at = already started (active now)
 * - null end_at = never expires (active forever)
 */
class NullOfferDateHandlingTest extends TestCase
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
     * Test offer with start_at = null (already started)
     * 
     * Expected: Offer should be selected as eligible
     */
    #[Test]
    public function it_treats_null_start_at_as_already_started(): void
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
            'start_at' => null,              // Already started
            'end_at' => now()->addDay(),     // Expires tomorrow
            'title' => 'Null Start Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Act: Select best offer for this product
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Offer with null start_at should be selected
        $this->assertNotNull($selectedOffer, 'Offer with null start_at should be eligible');
        $this->assertEquals($offer->id, $selectedOffer->id);
    }

    /**
     * Test offer with end_at = null (never expires)
     * 
     * Expected: Offer should be selected as eligible
     */
    #[Test]
    public function it_treats_null_end_at_as_never_expires(): void
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
            'start_at' => now()->subDay(),   // Started yesterday
            'end_at' => null,                // Never expires
            'title' => 'Open-Ended Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 5.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Offer with null end_at should be selected
        $this->assertNotNull($selectedOffer, 'Offer with null end_at should be eligible');
        $this->assertEquals($offer->id, $selectedOffer->id);
    }

    /**
     * Test offer with both start_at and end_at = null
     * 
     * Expected: Offer should be selected (always active)
     */
    #[Test]
    public function it_treats_both_null_dates_as_always_active(): void
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
            'start_at' => null,  // Always started
            'end_at' => null,    // Never expires
            'title' => 'Permanent Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 2
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 10, $customer->id);

        // Assert: Offer with both null dates should be selected
        $this->assertNotNull($selectedOffer, 'Offer with both null dates should be eligible');
        $this->assertEquals($offer->id, $selectedOffer->id);
    }

    /**
     * Test that future start_at (not yet started) is correctly excluded
     * 
     * Validates that null start_at behaves differently from future start_at
     */
    #[Test]
    public function it_excludes_offer_with_future_start_date(): void
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
            'start_at' => now()->addDay(),   // Starts tomorrow (not yet active)
            'end_at' => now()->addWeek(),
            'title' => 'Future Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Offer not yet started should NOT be selected
        $this->assertNull($selectedOffer, 'Offer with future start_at should not be eligible');
    }

    /**
     * Test that expired offer (past end_at) is correctly excluded
     * 
     * Validates that null end_at behaves differently from past end_at
     */
    #[Test]
    public function it_excludes_offer_with_past_end_date(): void
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
            'start_at' => now()->subWeek(),
            'end_at' => now()->subDay(),     // Expired yesterday
            'title' => 'Expired Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 5.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Expired offer should NOT be selected
        $this->assertNull($selectedOffer, 'Offer with past end_at should not be eligible');
    }

    /**
     * Test preference: null end_at loses tie-breaker to specific end_at
     * 
     * When multiple offers have same effective value, prefer one with
     * earlier end_at. null means infinite future, so it should lose.
     */
    #[Test]
    public function it_applies_tie_breaker_with_null_end_at(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        // Offer 1: Has specific end date (earlier expiration)
        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(5), // Expires in 5 days
            'title' => 'Limited Time Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer1->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 10.00  // Same value
        ]);

        // Offer 2: null end_at (never expires)
        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,  // Never expires
            'title' => 'Permanent Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer2->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 10.00  // Same value
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should prefer offer1 (earlier end_at) over offer2 (null end_at)
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offer1->id, $selectedOffer->id, 
            'Should prefer offer with earlier end_at over null end_at in tie-breaker');
    }
}
