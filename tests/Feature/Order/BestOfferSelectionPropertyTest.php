<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OfferSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Best Offer Selection Correctness
 * 
 * **Validates: Requirements 5.1-5.14**
 * 
 * Property 9: For any product with multiple eligible offers, the system should 
 * select the offer with the highest effective value, where effective value is 
 * calculated based on reward type (percentage_discount, fixed_discount, or bonus_qty).
 */
class BestOfferSelectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Best offer selection with multiple eligible offers
     * 
     * **Validates: Requirements 5.1-5.14**
     * 
     * This test generates random products with multiple offers and verifies
     * that the OfferSelector always selects the offer with the highest effective value.
     * Runs 100 iterations to test various combinations.
     */
    #[Test]
    public function best_offer_is_selected_based_on_highest_effective_value(): void
    {
        // Feature: order-creation-api, Property 9: Best offer selection correctness
        
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

            // Random quantity between 100 and 1000
            $qty = fake()->numberBetween(100, 1000);
            $minQty = 100;
            $multiplier = floor($qty / $minQty);

            // Create multiple offers with different reward types
            // Offer 1: Percentage discount
            $percentageValue = fake()->randomFloat(2, 5, 20);
            $offer1 = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => "{$percentageValue}% Discount"
            ]);
            
            OfferItem::factory()->percentageDiscount($percentageValue)->create([
                'offer_id' => $offer1->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Offer 2: Fixed discount
            $fixedValue = fake()->randomFloat(2, 10, 100);
            $offer2 = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => "Fixed {$fixedValue} Discount"
            ]);
            
            OfferItem::factory()->fixedDiscount($fixedValue)->create([
                'offer_id' => $offer2->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Offer 3: Bonus quantity
            $bonusQty = fake()->numberBetween(5, 20);
            $offer3 = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => "Get {$bonusQty} Free"
            ]);
            
            OfferItem::factory()->bonusQty($bonusQty, $product->id)->create([
                'offer_id' => $offer3->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Act: Select best offer
            $offerSelector = app(OfferSelector::class);
            $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);

            // Assert: Calculate expected effective values
            $roundedPrice = round($productPrice, 2, PHP_ROUND_HALF_UP);
            
            // Percentage discount effective value: (min_qty × unit_price × reward_value / 100) × multiplier
            $percentageDiscountPerBlock = round(($minQty * $roundedPrice * $percentageValue) / 100, 2, PHP_ROUND_HALF_UP);
            $effectiveValue1 = round($percentageDiscountPerBlock * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            // Fixed discount effective value: reward_value × multiplier
            $effectiveValue2 = round($fixedValue * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            // Bonus qty effective value: (reward_value × bonus_product_price) × multiplier
            $effectiveValue3 = round($bonusQty * $roundedPrice * $multiplier, 2, PHP_ROUND_HALF_UP);

            // Determine which offer should be selected
            $maxEffectiveValue = max($effectiveValue1, $effectiveValue2, $effectiveValue3);
            
            $expectedOfferId = null;
            if ($effectiveValue1 == $maxEffectiveValue) {
                $expectedOfferId = $offer1->id;
            } elseif ($effectiveValue2 == $maxEffectiveValue) {
                $expectedOfferId = $offer2->id;
            } else {
                $expectedOfferId = $offer3->id;
            }

            // Handle tie-breaking: prefer discount offers over bonus offers
            if ($effectiveValue1 == $effectiveValue2 && $effectiveValue1 >= $effectiveValue3) {
                // Both discount offers tie and beat bonus - percentage comes first by ID
                $expectedOfferId = min($offer1->id, $offer2->id);
            } elseif ($effectiveValue1 == $effectiveValue3 && $effectiveValue1 > $effectiveValue2) {
                // Percentage and bonus tie - prefer discount
                $expectedOfferId = $offer1->id;
            } elseif ($effectiveValue2 == $effectiveValue3 && $effectiveValue2 > $effectiveValue1) {
                // Fixed and bonus tie - prefer discount
                $expectedOfferId = $offer2->id;
            }

            $this->assertNotNull(
                $selectedOffer,
                "OfferSelector should select an offer when eligible offers exist (iteration {$i})"
            );

            $this->assertEquals(
                $expectedOfferId,
                $selectedOffer->id,
                "OfferSelector should select the offer with highest effective value. " .
                "Expected offer {$expectedOfferId}, got {$selectedOffer->id}. " .
                "Effective values: percentage={$effectiveValue1}, fixed={$effectiveValue2}, bonus={$effectiveValue3} " .
                "(iteration {$i}, qty={$qty}, price={$productPrice}, multiplier={$multiplier})"
            );
        }
    }

    /**
     * Property Test: No offer selected when all have zero multiplier
     * 
     * **Validates: Requirements 5.5, 5.14**
     * 
     * Verifies that when qty < min_qty for all offers (multiplier = 0),
     * no offer is selected.
     */
    #[Test]
    public function no_offer_selected_when_quantity_below_minimum(): void
    {
        // Feature: order-creation-api, Property 9: Best offer selection correctness
        
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
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Create offers with min_qty = 100
            $minQty = 100;
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active'
            ]);
            
            OfferItem::factory()->percentageDiscount(10)->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Order quantity less than min_qty (multiplier = 0)
            $qty = fake()->numberBetween(1, $minQty - 1);

            // Act
            $offerSelector = app(OfferSelector::class);
            $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);

            // Assert: No offer should be selected
            $this->assertNull(
                $selectedOffer,
                "OfferSelector should not select offer when qty ({$qty}) < min_qty ({$minQty}) (iteration {$i})"
            );
        }
    }

    /**
     * Property Test: Best offer selection with tie-breaking rules
     * 
     * **Validates: Requirements 5.11, 5.12, 5.13**
     * 
     * Tests that tie-breaking rules are applied correctly when multiple offers
     * have the same effective value.
     */
    #[Test]
    public function tie_breaking_prefers_discount_over_bonus(): void
    {
        // Feature: order-creation-api, Property 9: Best offer selection correctness
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create offers with equal effective values
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
            
            $productPrice = 10.00;
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $productPrice,
                'is_active' => true
            ]);

            $qty = 100;
            $minQty = 100;

            // Create discount offer with effective value = 100
            $discountOffer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => 'Discount Offer'
            ]);
            
            OfferItem::factory()->fixedDiscount(100)->create([
                'offer_id' => $discountOffer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Create bonus offer with effective value = 100
            // bonus_qty * product_price * multiplier = 10 * 10 * 1 = 100
            $bonusOffer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => 'Bonus Offer'
            ]);
            
            OfferItem::factory()->bonusQty(10, $product->id)->create([
                'offer_id' => $bonusOffer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);

            // Act
            $offerSelector = app(OfferSelector::class);
            $selectedOffer = $offerSelector->selectBestOffer($product, $qty, $customer->id);

            // Assert: Discount offer should be selected (tie-breaker rule)
            $this->assertNotNull($selectedOffer);
            $this->assertEquals(
                $discountOffer->id,
                $selectedOffer->id,
                "When effective values are equal, discount offers should be preferred over bonus offers (iteration {$i})"
            );
        }
    }

    /**
     * Property Test: Best offer selection with private offers
     * 
     * **Validates: Requirements 5.3, 5.10**
     * 
     * Verifies that private offers are only selected for targeted customers.
     */
    #[Test]
    public function private_offers_only_selected_for_targeted_customers(): void
    {
        // Feature: order-creation-api, Property 9: Best offer selection correctness
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $targetedCustomer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'targeted_' . uniqid() . '@example.com'
            ]);
            
            $nonTargetedCustomer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'non_targeted_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => 10.00,
                'is_active' => true
            ]);

            // Create a better private offer
            $privateOffer = Offer::factory()->private()->create([
                'company_user_id' => $company->id,
                'status' => 'active',
                'title' => 'Private VIP Offer'
            ]);
            
            OfferItem::factory()->fixedDiscount(200)->create([
                'offer_id' => $privateOffer->id,
                'product_id' => $product->id,
                'min_qty' => 100
            ]);
            
            // Target only specific customer
            $privateOffer->targets()->create([
                'target_type' => 'customer',
                'target_id' => $targetedCustomer->id
            ]);

            // Create a weaker public offer
            $publicOffer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active',
                'title' => 'Public Offer'
            ]);
            
            OfferItem::factory()->fixedDiscount(50)->create([
                'offer_id' => $publicOffer->id,
                'product_id' => $product->id,
                'min_qty' => 100
            ]);

            $qty = 100;

            // Act & Assert: Targeted customer gets private offer
            $offerSelector = app(OfferSelector::class);
            $selectedOfferForTargeted = $offerSelector->selectBestOffer($product, $qty, $targetedCustomer->id);
            
            $this->assertEquals(
                $privateOffer->id,
                $selectedOfferForTargeted->id,
                "Targeted customer should get the better private offer (iteration {$i})"
            );

            // Act & Assert: Non-targeted customer gets public offer
            $selectedOfferForNonTargeted = $offerSelector->selectBestOffer($product, $qty, $nonTargetedCustomer->id);
            
            $this->assertEquals(
                $publicOffer->id,
                $selectedOfferForNonTargeted->id,
                "Non-targeted customer should only get the public offer (iteration {$i})"
            );
        }
    }
}
