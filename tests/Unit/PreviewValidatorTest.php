<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\OfferSelector;
use App\Services\PreviewValidator;
use App\Services\PricingCalculator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: PreviewValidator Service
 * 
 * Tests the PreviewValidator service class to ensure it correctly revalidates
 * preview data before confirmation by detecting price changes and best offer changes.
 * 
 * Validates: Requirements 9.1, 9.4, 9.5, 9.8, 9.9, 9.10, 9.11, 9.12, 9.13, 9.14
 */
class PreviewValidatorTest extends TestCase
{
    private OrderRepository $mockRepository;
    private OfferSelector $mockOfferSelector;
    private PricingCalculator $mockPricingCalculator;
    private PreviewValidator $previewValidator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(OrderRepository::class);
        $this->mockOfferSelector = Mockery::mock(OfferSelector::class);
        $this->mockPricingCalculator = Mockery::mock(PricingCalculator::class);
        
        $this->previewValidator = new PreviewValidator(
            $this->mockRepository,
            $this->mockOfferSelector,
            $this->mockPricingCalculator
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Valid preview with no changes returns valid result
     * 
     * Validates: Requirements 9.1, 9.15
     */
    #[Test]
    public function valid_preview_with_no_changes_returns_valid(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => null
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertTrue($result['valid'], 'Should return valid when no changes detected');
        $this->assertEmpty($result['changes'], 'Should have no changes');
    }

    /**
     * Test: Price change detection with tolerance
     * 
     * Validates: Requirements 9.8
     */
    #[Test]
    public function price_change_detected_when_exceeds_tolerance(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.50; // Changed from 10.00

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => null
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when price changed');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('price_changed', $result['changes'][0]['type']);
        $this->assertEquals(1, $result['changes'][0]['product_id']);
        $this->assertEquals('Aspirin 500mg', $result['changes'][0]['product_name']);
        $this->assertEquals(10.00, $result['changes'][0]['preview_price']);
        $this->assertEquals(10.50, $result['changes'][0]['current_price']);
    }

    /**
     * Test: Price change within tolerance is not detected
     * 
     * Validates: Requirements 9.8 (0.01 tolerance)
     */
    #[Test]
    public function price_change_within_tolerance_not_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.005; // Rounds to 10.01, within 0.01 tolerance

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => null
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertTrue($result['valid'], 'Should return valid when price change within tolerance');
        $this->assertEmpty($result['changes'], 'Should have no changes');
    }

    /**
     * Test: Best offer change detected when different offer selected
     * 
     * Validates: Requirements 9.9, 9.10
     */
    #[Test]
    public function best_offer_change_detected_when_different_offer_selected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $previousOffer = new Offer();
        $previousOffer->id = 1;
        $previousOffer->title = 'Old Offer';

        $currentOffer = new Offer();
        $currentOffer->id = 2;
        $currentOffer->title = 'New Better Offer';

        $previousOfferItem = new OfferItem();
        $previousOfferItem->reward_type = 'discount_fixed';

        $currentOfferItem = new OfferItem();
        $currentOfferItem->reward_type = 'discount_percent';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn($currentOffer);

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($previousOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($previousOfferItem);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(2, 1)
            ->andReturn($currentOfferItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when best offer changed');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertEquals(1, $result['changes'][0]['product_id']);
        $this->assertEquals('Aspirin 500mg', $result['changes'][0]['product_name']);
        $this->assertEquals(1, $result['changes'][0]['previous_offer_id']);
        $this->assertEquals('Old Offer', $result['changes'][0]['previous_offer_title']);
        $this->assertEquals('discount_fixed', $result['changes'][0]['previous_reward_type']);
        $this->assertEquals(2, $result['changes'][0]['current_offer_id']);
        $this->assertEquals('New Better Offer', $result['changes'][0]['current_offer_title']);
        $this->assertEquals('discount_percent', $result['changes'][0]['current_reward_type']);
        $this->assertEquals('new_better_offer', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: Offer expiration detected
     * 
     * Validates: Requirements 9.11
     */
    #[Test]
    public function offer_expiration_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $expiredOffer = new Offer();
        $expiredOffer->id = 1;
        $expiredOffer->title = 'Expired Offer';
        $expiredOffer->status = 'active';
        $expiredOffer->scope = 'public';
        $expiredOffer->start_at = null;
        $expiredOffer->end_at = now()->subDay(); // Expired yesterday

        $offerItem = new OfferItem();
        $offerItem->reward_type = 'discount_fixed';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null); // No offer selected now

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($expiredOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($offerItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when offer expired');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertEquals('expired', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: Offer inactivation detected
     * 
     * Validates: Requirements 9.12
     */
    #[Test]
    public function offer_inactivation_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $inactiveOffer = new Offer();
        $inactiveOffer->id = 1;
        $inactiveOffer->title = 'Inactive Offer';
        $inactiveOffer->status = 'inactive'; // Became inactive
        $inactiveOffer->scope = 'public';
        $inactiveOffer->start_at = null;
        $inactiveOffer->end_at = null;

        $offerItem = new OfferItem();
        $offerItem->reward_type = 'discount_fixed';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null); // No offer selected now

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($inactiveOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($offerItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when offer became inactive');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertEquals('became_inactive', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: Offer not started detected
     * 
     * Validates: Requirements 9.13
     */
    #[Test]
    public function offer_not_started_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $futureOffer = new Offer();
        $futureOffer->id = 1;
        $futureOffer->title = 'Future Offer';
        $futureOffer->status = 'active';
        $futureOffer->scope = 'public';
        $futureOffer->start_at = now()->addDay(); // Starts tomorrow
        $futureOffer->end_at = null;

        $offerItem = new OfferItem();
        $offerItem->reward_type = 'discount_fixed';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null); // No offer selected now

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($futureOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($offerItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when offer not yet started');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertEquals('not_started', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: Private offer targeting change detected
     * 
     * Validates: Requirements 9.14
     */
    #[Test]
    public function private_offer_targeting_change_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $privateOffer = new Offer();
        $privateOffer->id = 1;
        $privateOffer->title = 'Private Offer';
        $privateOffer->status = 'active';
        $privateOffer->scope = 'private';
        $privateOffer->start_at = null;
        $privateOffer->end_at = null;

        $offerItem = new OfferItem();
        $offerItem->reward_type = 'discount_fixed';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn(null); // No offer selected now

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($privateOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($offerItem);

        $this->mockRepository
            ->shouldReceive('isCustomerTargeted')
            ->with(1, 1)
            ->andReturn(false); // Customer no longer targeted

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when targeting changed');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertEquals('targeting_changed', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: New offer appeared (no offer before, offer now)
     * 
     * Validates: Requirements 9.10
     */
    #[Test]
    public function new_offer_appeared_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 10.00;

        $newOffer = new Offer();
        $newOffer->id = 2;
        $newOffer->title = 'New Offer';

        $offerItem = new OfferItem();
        $offerItem->reward_type = 'discount_percent';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => null // No offer before
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn($newOffer); // New offer selected now

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(2, 1)
            ->andReturn($offerItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when new offer appeared');
        $this->assertCount(1, $result['changes'], 'Should have one change');
        $this->assertEquals('best_offer_changed', $result['changes'][0]['type']);
        $this->assertNull($result['changes'][0]['previous_offer_id']);
        $this->assertNull($result['changes'][0]['previous_offer_title']);
        $this->assertNull($result['changes'][0]['previous_reward_type']);
        $this->assertEquals(2, $result['changes'][0]['current_offer_id']);
        $this->assertEquals('New Offer', $result['changes'][0]['current_offer_title']);
        $this->assertEquals('discount_percent', $result['changes'][0]['current_reward_type']);
        $this->assertEquals('new_better_offer', $result['changes'][0]['change_reason']);
    }

    /**
     * Test: Multiple changes detected (price and offer)
     * 
     * Validates: Requirements 9.8, 9.9
     */
    #[Test]
    public function multiple_changes_detected(): void
    {
        // Arrange
        $customer = new User();
        $customer->id = 1;

        $product = new Product();
        $product->id = 1;
        $product->name = 'Aspirin 500mg';
        $product->base_price = 11.00; // Price changed

        $newOffer = new Offer();
        $newOffer->id = 2;
        $newOffer->title = 'New Offer';

        $previousOffer = new Offer();
        $previousOffer->id = 1;
        $previousOffer->title = 'Old Offer';

        $previousOfferItem = new OfferItem();
        $previousOfferItem->reward_type = 'discount_fixed';

        $currentOfferItem = new OfferItem();
        $currentOfferItem->reward_type = 'discount_percent';

        $previewData = [
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                    'unit_price' => 10.00,
                    'selected_offer_id' => 1
                ]
            ]
        ];

        $this->mockRepository
            ->shouldReceive('findProduct')
            ->with(1)
            ->andReturn($product);

        $this->mockOfferSelector
            ->shouldReceive('selectBestOffer')
            ->with($product, 100, 1)
            ->andReturn($newOffer);

        $this->mockRepository
            ->shouldReceive('findOffer')
            ->with(1)
            ->andReturn($previousOffer);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(1, 1)
            ->andReturn($previousOfferItem);

        $this->mockRepository
            ->shouldReceive('findOfferItem')
            ->with(2, 1)
            ->andReturn($currentOfferItem);

        // Act
        $result = $this->previewValidator->revalidate($previewData, $customer);

        // Assert
        $this->assertFalse($result['valid'], 'Should return invalid when multiple changes detected');
        $this->assertCount(2, $result['changes'], 'Should have two changes');
        $this->assertEquals('price_changed', $result['changes'][0]['type']);
        $this->assertEquals('best_offer_changed', $result['changes'][1]['type']);
    }
}
