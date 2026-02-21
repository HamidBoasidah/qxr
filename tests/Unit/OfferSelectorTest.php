<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\OfferSelector;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: OfferSelector Service
 * 
 * Tests the OfferSelector service class to ensure it correctly selects
 * the best offer for a product based on eligibility and effective value.
 */
class OfferSelectorTest extends TestCase
{
    private OrderRepository $mockRepository;
    private OfferSelector $offerSelector;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(OrderRepository::class);
        $this->offerSelector = new OfferSelector($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: No offers available returns null
     */
    #[Test]
    public function no_offers_available_returns_null(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection());

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNull($result, 'Should return null when no offers available');
    }

    /**
     * Test: Inactive offer is not selected
     */
    #[Test]
    public function inactive_offer_is_not_selected(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        $inactiveOffer = new Offer([
            'id' => 1,
            'status' => 'inactive',
            'scope' => 'public',
            'reward_type' => 'fixed_discount',
            'reward_value' => 50.00,
            'start_at' => null,
            'end_at' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$inactiveOffer]));

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNull($result, 'Should return null when only inactive offers available');
    }

    /**
     * Test: Offer with zero multiplier is not selected
     */
    #[Test]
    public function offer_with_zero_multiplier_is_not_selected(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 50; // Less than min_qty
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        $offer->reward_type = 'fixed_discount';
        $offer->reward_value = 50.00;
        $offer->start_at = null;
        $offer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100, // qty < min_qty, so multiplier = 0
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNull($result, 'Should return null when multiplier is 0 (qty < min_qty)');
    }

    /**
     * Test: Single eligible offer is selected
     */
    #[Test]
    public function single_eligible_offer_is_selected(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        $offer->reward_type = 'fixed_discount';
        $offer->reward_value = 50.00;
        $offer->start_at = null;
        $offer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return the eligible offer');
        $this->assertEquals($offer->id, $result->id, 'Should return the correct offer');
    }

    /**
     * Test: Offer with highest effective value is selected
     */
    #[Test]
    public function offer_with_highest_effective_value_is_selected(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Fixed discount of 30.00 (effective value = 30.00)
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'fixed_discount';
        $offer1->reward_value = 30.00;
        $offer1->start_at = null;
        $offer1->end_at = null;

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 2: Fixed discount of 50.00 (effective value = 50.00) - BEST
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'fixed_discount';
        $offer2->reward_value = 50.00;
        $offer2->start_at = null;
        $offer2->end_at = null;

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        $this->assertEquals(2, $result->id, 'Should return offer with highest effective value');
    }

    /**
     * Test: Discount offer preferred over bonus offer when effective values are equal
     */
    #[Test]
    public function discount_offer_preferred_over_bonus_offer_with_equal_value(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Fixed discount of 50.00 (effective value = 50.00) - SHOULD WIN
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'fixed_discount';
        $offer1->reward_value = 50.00;
        $offer1->start_at = null;
        $offer1->end_at = null;

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 2: Bonus of 5 units (effective value = 5 * 10.00 = 50.00)
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'bonus_qty';
        $offer2->reward_value = 5;
        $offer2->start_at = null;
        $offer2->end_at = null;

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => 1
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        $this->assertEquals(1, $result->id, 'Should prefer discount offer over bonus offer');
        $this->assertEquals('fixed_discount', $result->reward_type);
    }

    /**
     * Test: Private offer requires customer targeting
     */
    #[Test]
    public function private_offer_requires_customer_targeting(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        $privateOffer = new Offer();
        $privateOffer->id = 1;
        $privateOffer->status = 'active';
        $privateOffer->scope = 'private';
        $privateOffer->reward_type = 'fixed_discount';
        $privateOffer->reward_value = 50.00;
        $privateOffer->start_at = null;
        $privateOffer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$privateOffer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($privateOffer->id, $product->id)
            ->andReturn($offerItem);

        // Customer is NOT targeted
        $this->mockRepository
            ->shouldReceive('isCustomerTargeted')
            ->with($privateOffer->id, $customerId)
            ->andReturn(false);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNull($result, 'Should return null when customer is not targeted for private offer');
    }

    /**
     * Test: Offer with earlier end_at is preferred when effective values and reward types are equal
     * 
     * Validates: Requirements 5.12
     */
    #[Test]
    public function offer_with_earlier_end_at_is_preferred(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Fixed discount of 50.00, expires in 7 days - SHOULD WIN (earlier)
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'fixed_discount';
        $offer1->reward_value = 50.00;
        $offer1->start_at = null;
        $offer1->end_at = now()->addDays(7);

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 2: Fixed discount of 50.00, expires in 30 days (later)
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'fixed_discount';
        $offer2->reward_value = 50.00;
        $offer2->start_at = null;
        $offer2->end_at = now()->addDays(30);

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        $this->assertEquals(1, $result->id, 'Should prefer offer with earlier end_at');
    }

    /**
     * Test: Offer with null end_at goes last (treated as infinite)
     * 
     * Validates: Requirements 5.12
     */
    #[Test]
    public function offer_with_null_end_at_goes_last(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Fixed discount of 50.00, expires in 7 days - SHOULD WIN
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'fixed_discount';
        $offer1->reward_value = 50.00;
        $offer1->start_at = null;
        $offer1->end_at = now()->addDays(7);

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 2: Fixed discount of 50.00, never expires (null = infinite)
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'fixed_discount';
        $offer2->reward_value = 50.00;
        $offer2->start_at = null;
        $offer2->end_at = null;

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        $this->assertEquals(1, $result->id, 'Should prefer offer with end_at over null (infinite)');
    }

    /**
     * Test: Offer with lowest offer_id is preferred when all other factors are equal
     * 
     * Validates: Requirements 5.13
     */
    #[Test]
    public function offer_with_lowest_offer_id_is_preferred(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Fixed discount of 50.00, same end_at - SHOULD WIN (lower ID)
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'fixed_discount';
        $offer1->reward_value = 50.00;
        $offer1->start_at = null;
        $offer1->end_at = now()->addDays(7);

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 2: Fixed discount of 50.00, same end_at (higher ID)
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'fixed_discount';
        $offer2->reward_value = 50.00;
        $offer2->start_at = null;
        $offer2->end_at = now()->addDays(7);

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        $this->assertEquals(1, $result->id, 'Should prefer offer with lowest offer_id');
    }

    /**
     * Test: Complete tie-breaking cascade (all three rules)
     * 
     * Validates: Requirements 5.11, 5.12, 5.13
     */
    #[Test]
    public function complete_tie_breaking_cascade(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 100;
        $customerId = 1;

        // Offer 1: Bonus offer (loses on rule 1)
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->reward_type = 'bonus_qty';
        $offer1->reward_value = 5;
        $offer1->start_at = null;
        $offer1->end_at = now()->addDays(7);

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => 1
        ]);

        // Offer 2: Discount offer, expires later (loses on rule 2)
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->reward_type = 'fixed_discount';
        $offer2->reward_value = 50.00;
        $offer2->start_at = null;
        $offer2->end_at = now()->addDays(30);

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 3: Discount offer, expires sooner, higher ID (loses on rule 3)
        $offer3 = new Offer();
        $offer3->id = 3;
        $offer3->status = 'active';
        $offer3->scope = 'public';
        $offer3->reward_type = 'fixed_discount';
        $offer3->reward_value = 50.00;
        $offer3->start_at = null;
        $offer3->end_at = now()->addDays(7);

        $offerItem3 = new OfferItem([
            'id' => 3,
            'offer_id' => 3,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        // Offer 4: Discount offer, expires sooner, lowest ID - SHOULD WIN
        $offer4 = new Offer();
        $offer4->id = 4;
        $offer4->status = 'active';
        $offer4->scope = 'public';
        $offer4->reward_type = 'percentage_discount';
        $offer4->reward_value = 5.0; // 5% of 100 * 10.00 = 50.00
        $offer4->start_at = null;
        $offer4->end_at = now()->addDays(7);

        $offerItem4 = new OfferItem([
            'id' => 4,
            'offer_id' => 4,
            'product_id' => 1,
            'min_qty' => 100,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(new Collection([$offer1, $offer2, $offer3, $offer4]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer1->id, $product->id)
            ->andReturn($offerItem1);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer2->id, $product->id)
            ->andReturn($offerItem2);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer3->id, $product->id)
            ->andReturn($offerItem3);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer4->id, $product->id)
            ->andReturn($offerItem4);

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        $this->assertNotNull($result, 'Should return an offer');
        // Should select offer 3 (discount, earlier end_at, lower ID than 4)
        $this->assertEquals(3, $result->id, 'Should apply complete tie-breaking cascade');
    }
}
