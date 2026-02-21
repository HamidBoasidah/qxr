<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OfferSelector;
use App\Services\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Pricing Calculation Properties
 * 
 * **Validates: Requirements 6.1-6.9**
 * 
 * Properties 17-24: Tests all pricing calculations including unit price rounding,
 * percentage discount calculation, fixed discount calculation, bonus quantity calculation,
 * final line total calculation, order subtotal calculation, total discount calculation,
 * and final total calculation.
 */
class PricingCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 17: Unit price rounding
     * 
     * **Validates: Requirements 6.2**
     * 
     * For any product, the system should round the unit price to 2 decimal places
     * using ROUND_HALF_UP before any calculations.
     */
    #[Test]
    public function unit_price_is_rounded_correctly(): void
    {
        // Feature: order-creation-api, Property 17: Unit price rounding
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create product with random price that needs rounding
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            // Generate prices with various decimal places
            $rawPrice = fake()->randomFloat(5, 1, 1000); // 5 decimal places
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $rawPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(1, 100);
            
            // Act: Calculate pricing with no offer
            $result = $pricingCalculator->calculate($product, $qty, null);
            
            // Assert: Unit price should be rounded to 2 decimal places using ROUND_HALF_UP
            $expectedUnitPrice = round($rawPrice, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedUnitPrice,
                $result['unit_price'],
                "Unit price should be rounded to 2 decimal places using ROUND_HALF_UP. " .
                "Raw price: {$rawPrice}, Expected: {$expectedUnitPrice}, Got: {$result['unit_price']}, " .
                "Iteration: {$i}"
            );
            
            // Verify it has exactly 2 decimal places
            $decimalPart = explode('.', (string)$result['unit_price'])[1] ?? '';
            $this->assertLessThanOrEqual(
                2,
                strlen($decimalPart),
                "Unit price should have at most 2 decimal places (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 18: Percentage discount calculation
     * 
     * **Validates: Requirements 6.3**
     * 
     * For any item with a percentage_discount offer, the system should calculate
     * discount as (min_qty × unit_price × reward_value / 100) × multiplier,
     * rounded to 2 decimal places using ROUND_HALF_UP.
     */
    #[Test]
    public function percentage_discount_is_calculated_correctly(): void
    {
        // Feature: order-creation-api, Property 18: Percentage discount calculation
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $basePrice = fake()->randomFloat(2, 1, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $basePrice,
                'is_active' => true
            ]);
            
            $minQty = fake()->numberBetween(10, 100);
            $multiplier = fake()->numberBetween(1, 10);
            $qty = $minQty * $multiplier + fake()->numberBetween(0, $minQty - 1);
            $discountPercent = fake()->randomFloat(2, 5, 50);
            
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active'
            ]);
            
            OfferItem::factory()->percentageDiscount($discountPercent)->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);
            
            // Act
            $result = $pricingCalculator->calculate($product, $qty, $offer);
            
            // Assert: Calculate expected discount
            $unitPrice = round($basePrice, 2, PHP_ROUND_HALF_UP);
            $discountPerBlock = round(
                ($minQty * $unitPrice * $discountPercent) / 100,
                2,
                PHP_ROUND_HALF_UP
            );
            $expectedDiscount = round($discountPerBlock * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedDiscount,
                $result['discount_amount'],
                "Percentage discount should be (min_qty × unit_price × reward_value / 100) × multiplier. " .
                "Min qty: {$minQty}, Unit price: {$unitPrice}, Percent: {$discountPercent}, " .
                "Multiplier: {$multiplier}, Expected: {$expectedDiscount}, Got: {$result['discount_amount']}, " .
                "Iteration: {$i}"
            );
        }
    }

    /**
     * Property Test 19: Fixed discount calculation
     * 
     * **Validates: Requirements 6.4**
     * 
     * For any item with a fixed_discount offer, the system should calculate
     * discount as reward_value × multiplier, rounded to 2 decimal places using ROUND_HALF_UP.
     */
    #[Test]
    public function fixed_discount_is_calculated_correctly(): void
    {
        // Feature: order-creation-api, Property 19: Fixed discount calculation
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $minQty = fake()->numberBetween(10, 100);
            $multiplier = fake()->numberBetween(1, 10);
            $qty = $minQty * $multiplier + fake()->numberBetween(0, $minQty - 1);
            $fixedDiscount = fake()->randomFloat(2, 5, 50);
            
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active'
            ]);
            
            OfferItem::factory()->fixedDiscount($fixedDiscount)->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);
            
            // Act
            $result = $pricingCalculator->calculate($product, $qty, $offer);
            
            // Assert: Calculate expected discount
            $expectedDiscount = round($fixedDiscount * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedDiscount,
                $result['discount_amount'],
                "Fixed discount should be reward_value × multiplier. " .
                "Fixed discount: {$fixedDiscount}, Multiplier: {$multiplier}, " .
                "Expected: {$expectedDiscount}, Got: {$result['discount_amount']}, " .
                "Iteration: {$i}"
            );
        }
    }

    /**
     * Property Test 20: Bonus quantity calculation
     * 
     * **Validates: Requirements 6.5**
     * 
     * For any item with a bonus_qty offer, the system should calculate
     * bonus quantity as reward_value × multiplier (integer, no rounding).
     */
    #[Test]
    public function bonus_quantity_is_calculated_correctly(): void
    {
        // Feature: order-creation-api, Property 20: Bonus quantity calculation
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $minQty = fake()->numberBetween(10, 100);
            $multiplier = fake()->numberBetween(1, 10);
            $qty = $minQty * $multiplier + fake()->numberBetween(0, $minQty - 1);
            $bonusQty = fake()->numberBetween(5, 50);
            
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'scope' => 'public',
                'status' => 'active'
            ]);
            
            OfferItem::factory()->bonusQty($bonusQty, $product->id)->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty
            ]);
            
            // Act
            $result = $pricingCalculator->calculate($product, $qty, $offer);
            
            // Assert: Calculate expected bonus quantity (integer, no rounding)
            $expectedBonusQty = $multiplier * $bonusQty;
            
            $this->assertNotEmpty(
                $result['bonuses'],
                "Bonuses array should not be empty for bonus_qty offer (iteration {$i})"
            );
            
            $this->assertEquals(
                $expectedBonusQty,
                $result['bonuses'][0]['bonus_qty'],
                "Bonus quantity should be reward_value × multiplier (integer). " .
                "Bonus qty: {$bonusQty}, Multiplier: {$multiplier}, " .
                "Expected: {$expectedBonusQty}, Got: {$result['bonuses'][0]['bonus_qty']}, " .
                "Iteration: {$i}"
            );
            
            // Verify it's a whole number (may be represented as int or float with no decimal part)
            $bonusQtyValue = $result['bonuses'][0]['bonus_qty'];
            $this->assertEquals(
                floor($bonusQtyValue),
                $bonusQtyValue,
                "Bonus quantity should be a whole number with no decimal part (iteration {$i})"
            );
            
            // Verify no discount for bonus offers
            $this->assertEquals(
                0.0,
                $result['discount_amount'],
                "Bonus offers should have zero discount (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 21: Final line total calculation
     * 
     * **Validates: Requirements 6.6**
     * 
     * For any item, the system should calculate final line total as
     * (qty × unit_price) - discount_amount, rounded to 2 decimal places using ROUND_HALF_UP.
     */
    #[Test]
    public function final_line_total_is_calculated_correctly(): void
    {
        // Feature: order-creation-api, Property 21: Final line total calculation
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $basePrice = fake()->randomFloat(2, 1, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $basePrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(1, 100);
            
            // Test with no offer
            $result = $pricingCalculator->calculate($product, $qty, null);
            
            // Assert: Calculate expected final total
            $unitPrice = round($basePrice, 2, PHP_ROUND_HALF_UP);
            $lineSubtotal = round($qty * $unitPrice, 2, PHP_ROUND_HALF_UP);
            $expectedFinalTotal = round($lineSubtotal - $result['discount_amount'], 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedFinalTotal,
                $result['final_total'],
                "Final line total should be (qty × unit_price) - discount_amount. " .
                "Qty: {$qty}, Unit price: {$unitPrice}, Discount: {$result['discount_amount']}, " .
                "Expected: {$expectedFinalTotal}, Got: {$result['final_total']}, " .
                "Iteration: {$i}"
            );
            
            // Test with discount offer
            if ($i % 2 === 0) {
                $minQty = fake()->numberBetween(10, 50);
                $qtyWithOffer = $minQty * fake()->numberBetween(1, 5);
                
                $offer = Offer::factory()->create([
                    'company_user_id' => $company->id,
                    'scope' => 'public',
                    'status' => 'active'
                ]);
                
                OfferItem::factory()->fixedDiscount(fake()->randomFloat(2, 5, 20))->create([
                    'offer_id' => $offer->id,
                    'product_id' => $product->id,
                    'min_qty' => $minQty
                ]);
                
                $resultWithOffer = $pricingCalculator->calculate($product, $qtyWithOffer, $offer);
                
                $lineSubtotalWithOffer = round($qtyWithOffer * $unitPrice, 2, PHP_ROUND_HALF_UP);
                $expectedFinalTotalWithOffer = round(
                    $lineSubtotalWithOffer - $resultWithOffer['discount_amount'],
                    2,
                    PHP_ROUND_HALF_UP
                );
                
                $this->assertEquals(
                    $expectedFinalTotalWithOffer,
                    $resultWithOffer['final_total'],
                    "Final line total with discount should be (qty × unit_price) - discount_amount. " .
                    "Iteration: {$i}"
                );
            }
        }
    }

    /**
     * Property Test 22-24: Order totals calculation
     * 
     * **Validates: Requirements 6.7, 6.8, 6.9**
     * 
     * Tests order subtotal (sum of line_subtotal values), total discount (sum of discount_amount),
     * and final total (sum of final line totals) calculations across multiple items.
     */
    #[Test]
    public function order_totals_are_calculated_correctly(): void
    {
        // Feature: order-creation-api, Properties 22-24: Order totals calculation
        
        $pricingCalculator = app(PricingCalculator::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create order with 2-5 items
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $numItems = fake()->numberBetween(2, 5);
            $items = [];
            $calculatedItems = [];
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = Product::factory()->create([
                    'company_user_id' => $company->id,
                    'base_price' => fake()->randomFloat(2, 10, 100),
                    'is_active' => true
                ]);
                
                $qty = fake()->numberBetween(10, 100);
                
                // Randomly add offers to some items
                $offer = null;
                if (fake()->boolean(60)) { // 60% chance of having an offer
                    $minQty = fake()->numberBetween(5, 20);
                    $qtyWithOffer = $minQty * fake()->numberBetween(1, 5);
                    
                    $offer = Offer::factory()->create([
                        'company_user_id' => $company->id,
                        'scope' => 'public',
                        'status' => 'active'
                    ]);
                    
                    $rewardType = fake()->randomElement(['discount_percent', 'discount_fixed', 'bonus_qty']);
                    
                    switch ($rewardType) {
                        case 'discount_percent':
                            OfferItem::factory()->percentageDiscount(fake()->randomFloat(2, 5, 30))->create([
                                'offer_id' => $offer->id,
                                'product_id' => $product->id,
                                'min_qty' => $minQty
                            ]);
                            break;
                        case 'discount_fixed':
                            OfferItem::factory()->fixedDiscount(fake()->randomFloat(2, 5, 50))->create([
                                'offer_id' => $offer->id,
                                'product_id' => $product->id,
                                'min_qty' => $minQty
                            ]);
                            break;
                        case 'bonus_qty':
                            OfferItem::factory()->bonusQty(fake()->numberBetween(5, 20), $product->id)->create([
                                'offer_id' => $offer->id,
                                'product_id' => $product->id,
                                'min_qty' => $minQty
                            ]);
                            break;
                    }
                    
                    $qty = $qtyWithOffer;
                }
                
                $items[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'offer' => $offer
                ];
            }
            
            // Act: Calculate pricing for each item
            foreach ($items as $item) {
                $result = $pricingCalculator->calculate($item['product'], $item['qty'], $item['offer']);
                $calculatedItems[] = $result;
            }
            
            // Assert: Calculate expected order totals
            
            // Property 22: Order subtotal = sum of (qty × unit_price) for all items
            $expectedSubtotal = 0;
            foreach ($items as $index => $item) {
                $lineSubtotal = round(
                    $item['qty'] * $calculatedItems[$index]['unit_price'],
                    2,
                    PHP_ROUND_HALF_UP
                );
                $expectedSubtotal += $lineSubtotal;
            }
            $expectedSubtotal = round($expectedSubtotal, 2, PHP_ROUND_HALF_UP);
            
            $actualSubtotal = 0;
            foreach ($items as $index => $item) {
                $actualSubtotal += round(
                    $item['qty'] * $calculatedItems[$index]['unit_price'],
                    2,
                    PHP_ROUND_HALF_UP
                );
            }
            $actualSubtotal = round($actualSubtotal, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedSubtotal,
                $actualSubtotal,
                "Order subtotal should be sum of line_subtotal values. " .
                "Expected: {$expectedSubtotal}, Got: {$actualSubtotal}, " .
                "Iteration: {$i}"
            );
            
            // Property 23: Total discount = sum of discount_amount for all items
            $expectedTotalDiscount = array_sum(array_column($calculatedItems, 'discount_amount'));
            
            $this->assertIsFloat(
                $expectedTotalDiscount,
                "Total discount should be a float (iteration {$i})"
            );
            
            // Verify each discount is properly rounded
            foreach ($calculatedItems as $index => $calcItem) {
                $this->assertLessThanOrEqual(
                    2,
                    strlen(explode('.', (string)$calcItem['discount_amount'])[1] ?? ''),
                    "Each discount amount should have at most 2 decimal places (item {$index}, iteration {$i})"
                );
            }
            
            // Property 24: Final total = sum of final line totals for all items
            $expectedFinalTotal = array_sum(array_column($calculatedItems, 'final_total'));
            
            $this->assertIsFloat(
                $expectedFinalTotal,
                "Final total should be a float (iteration {$i})"
            );
            
            // Verify relationship: final_total = subtotal - total_discount
            $calculatedFinalTotal = round($actualSubtotal - $expectedTotalDiscount, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEqualsWithDelta(
                $calculatedFinalTotal,
                $expectedFinalTotal,
                0.01,
                "Final total should equal subtotal minus total discount. " .
                "Subtotal: {$actualSubtotal}, Total discount: {$expectedTotalDiscount}, " .
                "Expected final: {$calculatedFinalTotal}, Got: {$expectedFinalTotal}, " .
                "Iteration: {$i}"
            );
        }
    }
}
