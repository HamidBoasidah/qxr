<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\OfferSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: OfferItem Existence Guarantee
 * 
 * **Validates: Requirements 5.1, 5.2**
 * 
 * Property 16: For any offer selected by OfferSelector, findOfferItem(offer_id, product_id) 
 * MUST return a non-null OfferItem.
 * 
 * Rationale: PricingCalculator relies on offer_item (min_qty, bonus_product_id). 
 * This property ensures data integrity and prevents null reference errors during pricing calculation.
 */
class OfferItemExistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: OfferItem existence guarantee for selected offers
     * 
     * **Validates: Requirements 5.1, 5.2**
     * 
     * This test verifies that whenever OfferSelector returns an offer for a product,
     * the corresponding OfferItem can be retrieved using findOfferItem(offer_id, product_id).
     * Runs 100 iterations to test various combinations of offers and products.
     */
    #[Test]
    public function selected_offer_always_has_corresponding_offer_item(): void
    {
        // Feature: order-creation-api, Property 16: OfferItem existence guarantee
        
        $offerSelector = app(OfferSelector::class);
        $orderRepository = app(OrderRepository::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with random values
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Random product price between 1 and 100
            $productPrice = fake()->randomFloat(2, 1, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $productPrice,
                'is_active' => true
            ]);

            // Random quantity that ensures multiplier > 0
            $minQty = fake()->numberBetween(10, 100);
            $qty = fake()->numberBetween($minQty, $minQty * 10);

            // Create random number of offers (1-5) with different reward types
            $numOffers = fake()->numberBetween(1, 5);
            
            for ($j = 0; $j < $numOffers; $j++) {
                $rewardType = fake()->randomElement(['discount_percent', 'discount_fixed', 'bonus_qty']);
                
                $offer = Offer::factory()->create([
                    'company_user_id' => $company->id,
                    'scope' => 'public',
                    'status' => 'active',
                    'title' => "Offer {$j} - " . $rewardType
                ]);
                
                // Create OfferItem based on reward type
                switch ($rewardType) {
                    case 'discount_percent':
                        $percentValue = fake()->randomFloat(2, 5, 30);
                        OfferItem::factory()->percentageDiscount($percentValue)->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty
                        ]);
                        break;
                    
                    case 'discount_fixed':
                        $fixedValue = fake()->randomFloat(2, 10, 200);
                        OfferItem::factory()->fixedDiscount($fixedValue)->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty
                        ]);
                        break;
                    
                    case 'bonus_qty':
                        $bonusQty = fake()->numberBetween(5, 50);
                        OfferItem::factory()->bonusQty($bonusQty, $product->id)->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty
                        ]);
                        break;
                }
            }

            // Act: Select best offer
            $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);

            // Assert: If an offer was selected, verify OfferItem exists
            if ($selectedOffer !== null) {
                $offerItem = $orderRepository->findOfferItem($selectedOffer->id, $product->id);
                
                $this->assertNotNull(
                    $offerItem,
                    "OfferItem MUST exist for selected offer. " .
                    "Offer ID: {$selectedOffer->id}, Product ID: {$product->id}, " .
                    "Iteration: {$i}, Qty: {$qty}, Min Qty: {$minQty}"
                );
                
                // Additional verification: OfferItem should have required fields
                $this->assertEquals(
                    $selectedOffer->id,
                    $offerItem->offer_id,
                    "OfferItem offer_id should match selected offer ID (iteration {$i})"
                );
                
                $this->assertEquals(
                    $product->id,
                    $offerItem->product_id,
                    "OfferItem product_id should match product ID (iteration {$i})"
                );
                
                $this->assertGreaterThan(
                    0,
                    $offerItem->min_qty,
                    "OfferItem min_qty must be greater than 0 (iteration {$i})"
                );
                
                $this->assertNotNull(
                    $offerItem->reward_type,
                    "OfferItem reward_type must not be null (iteration {$i})"
                );
            }
        }
    }

    /**
     * Property Test: OfferItem existence for private offers
     * 
     * **Validates: Requirements 5.1, 5.2, 5.3**
     * 
     * Verifies that OfferItem exists for private offers selected for targeted customers.
     */
    #[Test]
    public function selected_private_offer_has_corresponding_offer_item(): void
    {
        // Feature: order-creation-api, Property 16: OfferItem existence guarantee
        
        $offerSelector = app(OfferSelector::class);
        $orderRepository = app(OrderRepository::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);

            $minQty = 100;
            $qty = fake()->numberBetween($minQty, $minQty * 5);

            // Create a private offer
            $privateOffer = Offer::factory()->private()->create([
                'company_user_id' => $company->id,
                'status' => 'active',
                'title' => 'Private VIP Offer'
            ]);
            
            // Create OfferItem for the private offer
            OfferItem::factory()->fixedDiscount(fake()->randomFloat(2, 50, 300))->create([
                'offer_id' => $privateOffer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);
            
            // Target the customer
            $privateOffer->targets()->create([
                'target_type' => 'customer',
                'target_id' => $customer->id
            ]);

            // Act: Select best offer
            $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);

            // Assert: Verify OfferItem exists for the selected private offer
            if ($selectedOffer !== null && $selectedOffer->id === $privateOffer->id) {
                $offerItem = $orderRepository->findOfferItem($selectedOffer->id, $product->id);
                
                $this->assertNotNull(
                    $offerItem,
                    "OfferItem MUST exist for selected private offer. " .
                    "Offer ID: {$selectedOffer->id}, Product ID: {$product->id}, " .
                    "Iteration: {$i}"
                );
                
                $this->assertEquals(
                    $privateOffer->id,
                    $offerItem->offer_id,
                    "OfferItem should belong to the private offer (iteration {$i})"
                );
            }
        }
    }

    /**
     * Property Test: OfferItem existence with multiple products
     * 
     * **Validates: Requirements 5.1, 5.2**
     * 
     * Verifies that when an offer applies to multiple products, each product
     * has its own OfferItem that can be retrieved.
     */
    #[Test]
    public function multi_product_offer_has_offer_item_for_each_product(): void
    {
        // Feature: order-creation-api, Property 16: OfferItem existence guarantee
        
        $offerSelector = app(OfferSelector::class);
        $orderRepository = app(OrderRepository::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create multiple products
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Create 2-5 products
            $numProducts = fake()->numberBetween(2, 5);
            $products = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $products[] = Product::factory()->create([
                    'company_user_id' => $company->id,
                    'base_price' => fake()->randomFloat(2, 10, 100),
                    'is_active' => true
                ]);
            }

            // Create an offer that applies to all products
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => 'Multi-Product Offer'
            ]);
            
            $minQty = 50;
            
            // Create OfferItem for each product
            foreach ($products as $product) {
                OfferItem::factory()->percentageDiscount(10)->create([
                    'offer_id' => $offer->id,
                    'product_id' => $product->id,
                    'min_qty' => $minQty
                ]);
            }

            // Act & Assert: For each product, verify OfferItem exists if offer is selected
            foreach ($products as $product) {
                $qty = fake()->numberBetween($minQty, $minQty * 3);
                $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);
                
                if ($selectedOffer !== null && $selectedOffer->id === $offer->id) {
                    $offerItem = $orderRepository->findOfferItem($selectedOffer->id, $product->id);
                    
                    $this->assertNotNull(
                        $offerItem,
                        "OfferItem MUST exist for each product in multi-product offer. " .
                        "Offer ID: {$selectedOffer->id}, Product ID: {$product->id}, " .
                        "Iteration: {$i}"
                    );
                    
                    $this->assertEquals(
                        $product->id,
                        $offerItem->product_id,
                        "OfferItem should be specific to the product (iteration {$i})"
                    );
                }
            }
        }
    }
}
