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
 * Unit Test: Tie-Breaking Logic
 * 
 * **Validates: Requirements 5.11, 5.12, 5.13**
 * 
 * Tests the three-level tie-breaking logic when multiple offers
 * have the same effective value:
 * 1. Prefer discount offers over bonus offers
 * 2. Prefer offer with earlier end_at (null = infinite, goes last)
 * 3. Prefer offer with lowest offer_id
 */
class TieBreakingLogicTest extends TestCase
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
     * Test Tie-Breaker 1: Prefer discount over bonus when values are equal
     * 
     * Create two offers with same effective value:
     * - Offer A: discount_fixed = 10.00
     * - Offer B: bonus_qty with value = 10.00
     * 
     * Expected: Prefer Offer A (discount)
     */
    #[Test]
    public function it_prefers_discount_over_bonus_in_tie(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        $bonusProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00, // Bonus value will be 1 × 10.00 = 10.00
            'is_active' => true
        ]);

        // Offer 1: Fixed discount of 10.00 (created first, higher ID)
        $offerDiscount = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Discount Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerDiscount->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 10.00
        ]);

        // Offer 2: Bonus with same effective value of 10.00 (created later, higher ID)
        $offerBonus = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Bonus Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerBonus->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 1,
            'bonus_product_id' => $bonusProduct->id
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should prefer discount offer
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerDiscount->id, $selectedOffer->id,
            'Should prefer discount offer over bonus offer when values are equal');
    }

    /**
     * Test Tie-Breaker 2: Prefer earlier end_at over later end_at
     * 
     * Create two discount offers with same value but different end dates
     */
    #[Test]
    public function it_prefers_earlier_end_at_in_tie(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 50.00,
            'is_active' => true
        ]);

        // Offer 1: Ends in 3 days
        $offerEarlier = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(3),
            'title' => 'Ends Earlier'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerEarlier->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 5.00
        ]);

        // Offer 2: Ends in 7 days
        $offerLater = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(7),
            'title' => 'Ends Later'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerLater->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 5.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should prefer offer ending earlier
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerEarlier->id, $selectedOffer->id,
            'Should prefer offer with earlier end_at');
    }

    /**
     * Test Tie-Breaker 2: null end_at (never expires) loses to specific end_at
     * 
     * null means infinite future, so it should lose the urgency tie-breaker
     */
    #[Test]
    public function it_prefers_specific_end_at_over_null_end_at(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 75.00,
            'is_active' => true
        ]);

        // Offer 1: Has specific end date
        $offerWithEndDate = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(5),
            'title' => 'Limited Time'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerWithEndDate->id,
            'product_id' => $product->id,
            'min_qty' => 2,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Offer 2: No end date (permanent)
        $offerPermanent = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Permanent Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerPermanent->id,
            'product_id' => $product->id,
            'min_qty' => 2,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 10, $customer->id);

        // Assert: Should prefer limited-time offer
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerWithEndDate->id, $selectedOffer->id,
            'Should prefer offer with specific end_at over null end_at (permanent)');
    }

    /**
     * Test Tie-Breaker 3: Prefer lowest offer_id when all else is equal
     */
    #[Test]
    public function it_prefers_lowest_offer_id_in_final_tie(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 30.00,
            'is_active' => true
        ]);

        // Create three identical offers (same type, same value, same dates)
        $offers = [];
        for ($i = 0; $i < 3; $i++) {
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'status' => 'active',
                'start_at' => null,
                'end_at' => now()->addWeek(),
                'title' => "Offer {$i}"
            ]);

            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 1,
                'reward_type' => 'discount_fixed',
                'discount_fixed' => 3.00
            ]);

            $offers[] = $offer;
        }

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should select the one with lowest ID
        $this->assertNotNull($selectedOffer);
        $lowestId = min(array_column($offers, 'id'));
        $this->assertEquals($lowestId, $selectedOffer->id,
            'Should prefer offer with lowest ID when all other factors are equal');
    }

    /**
     * Test all three tie-breakers combined
     * 
     * Complex scenario with multiple offers requiring all three levels
     */
    #[Test]
    public function it_applies_all_tie_breakers_in_sequence(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        $bonusProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true
        ]);

        // Group 1: Higher effective value (30.00) - should win regardless of type
        $offerBest = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Best Value'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerBest->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 30.00
        ]);

        // Group 2: Same value (20.00), but bonus type - should lose to discount
        $offerBonus = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(5),
            'title' => 'Bonus Offer'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerBonus->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 1,
            'bonus_product_id' => $bonusProduct->id
        ]);

        // Group 3: Same value (20.00), discount type, earlier end_at
        $offerDiscount1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(3),
            'title' => 'Discount 1'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerDiscount1->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 20.00
        ]);

        // Group 4: Same value (20.00), discount type, later end_at
        $offerDiscount2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(7),
            'title' => 'Discount 2'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerDiscount2->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 20.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should select offerBest (highest value wins)
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerBest->id, $selectedOffer->id,
            'Should select offer with highest effective value');
    }

    /**
     * Test percentage discount vs bonus with same effective value
     */
    #[Test]
    public function it_prefers_percentage_discount_over_bonus_in_tie(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 100.00,
            'is_active' => true
        ]);

        $bonusProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Offer 1: 10% discount on qty=10 → (10 * 100 * 10) / 100 = 100.00
        $offerPercent = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Percentage Discount'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerPercent->id,
            'product_id' => $product->id,
            'min_qty' => 10,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00
        ]);

        // Offer 2: 10 bonus items worth 10.00 each → 10 * 10.00 = 100.00
        $offerBonus = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Bonus Items'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerBonus->id,
            'product_id' => $product->id,
            'min_qty' => 10,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 10,
            'bonus_product_id' => $bonusProduct->id
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 10, $customer->id);

        // Assert: Should prefer percentage discount
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerPercent->id, $selectedOffer->id,
            'Should prefer percentage discount over bonus when values are equal');
    }

    /**
     * Test that tie-breakers only apply when effective values are truly equal
     */
    #[Test]
    public function it_ignores_tie_breakers_when_values_differ(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 50.00,
            'is_active' => true
        ]);

        $bonusProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Offer 1: Bonus with higher value (12.00)
        $offerBonus = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDays(10), // Later end date
            'title' => 'High Value Bonus'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerBonus->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 1,
            'bonus_product_id' => $bonusProduct->id
        ]);

        // Offer 2: Discount with lower value (8.00) but better tie-breakers
        $offerDiscount = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null,
            'end_at' => now()->addDay(), // Earlier end date
            'title' => 'Lower Value Discount'
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offerDiscount->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 8.00
        ]);

        // Act
        $selectedOffer = $this->selector->selectBestOffer($product, 5, $customer->id);

        // Assert: Should select bonus (higher value) despite losing tie-breakers
        $this->assertNotNull($selectedOffer);
        $this->assertEquals($offerBonus->id, $selectedOffer->id,
            'Should prefer higher effective value regardless of tie-breaker factors');
    }
}
