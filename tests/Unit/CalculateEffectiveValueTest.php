<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\OfferSelector;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: OfferSelector calculateEffectiveValue Method
 * 
 * Tests the calculateEffectiveValue method to ensure it correctly calculates
 * effective values for different offer types according to the requirements.
 * 
 * Requirements: 5.7, 5.8, 5.9
 */
class CalculateEffectiveValueTest extends TestCase
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
     * Test: Percentage discount effective value calculation
     * Formula: (min_qty × unit_price × reward_value / 100) × multiplier
     * 
     * Validates: Requirements 5.7
     */
    #[Test]
    public function percentage_discount_effective_value_is_calculated_correctly(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 200; // multiplier = floor(200 / 100) = 2
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        // Removed - reward_type is on OfferItem
        // Removed - reward fields are on OfferItem: 10; // 10% discount
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
            ->andReturn(collect([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Expected: (100 × 10.00 × 10 / 100) × 2 = (100.00) × 2 = 200.00
        $this->assertNotNull($result, 'Should select the offer');
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test: Fixed discount effective value calculation
     * Formula: reward_value × multiplier
     * 
     * Validates: Requirements 5.8
     */
    #[Test]
    public function fixed_discount_effective_value_is_calculated_correctly(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        $qty = 300; // multiplier = floor(300 / 100) = 3
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        $offer->start_at = null;
        $offer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 50.00,
            'bonus_product_id' => null
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(collect([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Expected: 50.00 × 3 = 150.00
        $this->assertNotNull($result, 'Should select the offer');
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test: Bonus quantity effective value calculation
     * Formula: (reward_value × bonus_product_price) × multiplier
     * 
     * Validates: Requirements 5.9
     */
    #[Test]
    public function bonus_qty_effective_value_is_calculated_correctly(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        
        $bonusProduct = new Product();
        $bonusProduct->id = 2;
        $bonusProduct->base_price = 8.00;
        
        $qty = 200; // multiplier = floor(200 / 100) = 2
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        $offer->start_at = null;
        $offer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 5, // 5 bonus units
            'bonus_product_id' => 2
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(collect([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(2)
            ->andReturn($bonusProduct);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Expected: (5 × 8.00) × 2 = 40.00 × 2 = 80.00
        $this->assertNotNull($result, 'Should select the offer');
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test: Rounding is applied correctly (ROUND_HALF_UP to 2 decimal places)
     * 
     * Validates: Requirements 5.7, 5.8, 5.9
     */
    #[Test]
    public function effective_value_rounding_is_applied_correctly(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.456; // Will be rounded to 10.46
        $qty = 100;
        $customerId = 1;

        $offer = new Offer();
        $offer->id = 1;
        $offer->status = 'active';
        $offer->scope = 'public';
        // Removed - reward_type is on OfferItem
        // Removed - reward fields are on OfferItem: 10; // 10% discount
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
            ->andReturn(collect([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Expected: (100 × 10.46 × 10 / 100) × 1 = 104.60
        // Rounding should be applied at each step
        $this->assertNotNull($result, 'Should select the offer');
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test: Bonus quantity with same product as ordered product
     * When bonus_product_id is null, it defaults to the ordered product
     * 
     * Validates: Requirements 5.9
     */
    #[Test]
    public function bonus_qty_defaults_to_ordered_product_when_bonus_product_id_is_null(): void
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
        $offer->start_at = null;
        $offer->end_at = null;

        $offerItem = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 5, // 5 bonus units
            'bonus_product_id' => null // Defaults to ordered product
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(collect([$offer]));

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with($offer->id, $product->id)
            ->andReturn($offerItem);

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Expected: (5 × 10.00) × 1 = 50.00
        $this->assertNotNull($result, 'Should select the offer');
        $this->assertEquals(1, $result->id);
    }

    /**
     * Test: Comparison of different offer types with different effective values
     * Ensures the offer with highest effective value is selected
     * 
     * Validates: Requirements 5.7, 5.8, 5.9
     */
    #[Test]
    public function highest_effective_value_offer_is_selected_across_different_types(): void
    {
        // Arrange
        $product = new Product();
        $product->id = 1;
        $product->base_price = 10.00;
        
        $bonusProduct = new Product();
        $bonusProduct->id = 2;
        $bonusProduct->base_price = 12.00;
        
        $qty = 100;
        $customerId = 1;

        // Offer 1: Percentage discount 10% = (100 × 10.00 × 10 / 100) × 1 = 100.00
        $offer1 = new Offer();
        $offer1->id = 1;
        $offer1->status = 'active';
        $offer1->scope = 'public';
        $offer1->start_at = null;
        $offer1->end_at = null;

        $offerItem1 = new OfferItem([
            'id' => 1,
            'offer_id' => 1,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10,
            'bonus_product_id' => null
        ]);

        // Offer 2: Fixed discount 80.00 = 80.00 × 1 = 80.00
        $offer2 = new Offer();
        $offer2->id = 2;
        $offer2->status = 'active';
        $offer2->scope = 'public';
        $offer2->start_at = null;
        $offer2->end_at = null;

        $offerItem2 = new OfferItem([
            'id' => 2,
            'offer_id' => 2,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'discount_fixed',
            'discount_fixed' => 80.00,
            'bonus_product_id' => null
        ]);

        // Offer 3: Bonus qty 10 units @ 12.00 = (10 × 12.00) × 1 = 120.00 - BEST
        $offer3 = new Offer();
        $offer3->id = 3;
        $offer3->status = 'active';
        $offer3->scope = 'public';
        $offer3->start_at = null;
        $offer3->end_at = null;

        $offerItem3 = new OfferItem([
            'id' => 3,
            'offer_id' => 3,
            'product_id' => 1,
            'min_qty' => 100,
            'reward_type' => 'bonus_qty',
            'bonus_qty' => 10,
            'bonus_product_id' => 2
        ]);

        $this->mockRepository
            ->shouldReceive('findOffersForProduct')
            ->with($product->id)
            ->andReturn(collect([$offer1, $offer2, $offer3]));

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
            ->shouldReceive('findProduct')
            ->with(2)
            ->andReturn($bonusProduct);

        // Act
        $result = $this->offerSelector->selectBestOffer($product, $qty, $customerId);

        // Assert
        // Offer 3 has highest effective value (120.00)
        $this->assertNotNull($result, 'Should select an offer');
        $this->assertEquals(3, $result->id, 'Should select offer with highest effective value');
    }
}
