<?php

namespace Tests\Unit;

use App\DTOs\OrderDTO;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\Product;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDTOTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test OrderDTO::fromModel transforms order correctly
     */
    public function test_from_model_transforms_order_correctly(): void
    {
        // Arrange: Create test data
        $order = Order::factory()->create([
            'order_no' => 'ORD-20260219103045-A3F2',
            'status' => 'pending',
            'submitted_at' => now(),
            'notes_customer' => 'Test notes',
        ]);

        $product1 = Product::factory()->create(['name' => 'Product A']);
        $product2 = Product::factory()->create(['name' => 'Product B']);

        // Create order items without using factory to avoid afterMaking hooks
        $orderItem1 = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'qty' => 100,
            'unit_price_snapshot' => 10.50,
            'discount_amount_snapshot' => 50.00,
            'final_line_total_snapshot' => 1000.00,
            'selected_offer_id' => null,
        ]);
        $orderItem1->save();

        $orderItem2 = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'qty' => 50,
            'unit_price_snapshot' => 5.25,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 262.50,
            'selected_offer_id' => null,
        ]);
        $orderItem2->save();

        // Load relationships
        $order->load(['items.product', 'items.bonuses.bonusProduct']);

        // Act: Transform to DTO
        $result = OrderDTO::fromModel($order);

        // Assert: Check structure
        $this->assertIsArray($result);
        $this->assertEquals($order->id, $result['id']);
        $this->assertEquals('ORD-20260219103045-A3F2', $result['order_no']);
        $this->assertEquals('pending', $result['status']);
        $this->assertNotNull($result['submitted_at']);
        $this->assertEquals('Test notes', $result['notes']);

        // Assert: Check items
        $this->assertCount(2, $result['items']);
        
        $this->assertEquals($orderItem1->id, $result['items'][0]['id']);
        $this->assertEquals($product1->id, $result['items'][0]['product_id']);
        $this->assertEquals('Product A', $result['items'][0]['product_name']);
        $this->assertEquals(100, $result['items'][0]['qty']);
        $this->assertEquals(10.50, $result['items'][0]['unit_price']);
        $this->assertEquals(50.00, $result['items'][0]['discount_amount']);
        $this->assertEquals(1000.00, $result['items'][0]['final_total']);

        // Assert: Check totals with proper rounding
        // Subtotal = sum of rounded line_subtotal values
        // Item 1: round(100 * 10.50, 2) = 1050.00
        // Item 2: round(50 * 5.25, 2) = 262.50
        // Subtotal = round(1050.00 + 262.50, 2) = 1312.50
        $this->assertEquals(1312.50, $result['subtotal']);
        $this->assertEquals(50.00, $result['total_discount']);
        $this->assertEquals(1262.50, $result['final_total']);
    }

    /**
     * Test OrderDTO::fromModel handles bonuses correctly
     */
    public function test_from_model_includes_bonuses(): void
    {
        // Arrange: Create test data with bonuses
        $order = Order::factory()->create([
            'order_no' => 'ORD-20260219103045-B4G3',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $product = Product::factory()->create(['name' => 'Product A']);
        $bonusProduct = Product::factory()->create(['name' => 'Bonus Product']);
        $offer = Offer::factory()->create(['title' => 'Buy 100 Get 10 Free']);

        // Create order item without using factory
        $orderItem = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 100,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 1000.00,
            'selected_offer_id' => $offer->id,
        ]);
        $orderItem->save();

        // Create bonus without using factory
        $bonus = new OrderItemBonus([
            'order_item_id' => $orderItem->id,
            'bonus_product_id' => $bonusProduct->id,
            'bonus_qty' => 10,
            'offer_id' => $offer->id,
        ]);
        $bonus->save();

        // Load relationships
        $order->load(['items.product', 'items.bonuses.bonusProduct', 'items.bonuses.offer']);

        // Act: Transform to DTO
        $result = OrderDTO::fromModel($order);

        // Assert: Check bonuses
        $this->assertCount(1, $result['items']);
        $this->assertCount(1, $result['items'][0]['bonuses']);
        
        $bonusData = $result['items'][0]['bonuses'][0];
        $this->assertEquals($bonusProduct->id, $bonusData['bonus_product_id']);
        $this->assertEquals('Bonus Product', $bonusData['bonus_product_name']);
        $this->assertEquals(10, $bonusData['bonus_qty']);
        $this->assertEquals('Buy 100 Get 10 Free', $bonusData['offer_title']);
    }

    /**
     * Test OrderDTO::fromModel rounds monetary values correctly
     */
    public function test_from_model_rounds_monetary_values(): void
    {
        // Arrange: Create test data with values that need rounding
        $order = Order::factory()->create([
            'order_no' => 'ORD-20260219103045-C5H4',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $product = Product::factory()->create(['name' => 'Product A']);

        // Create order item without using factory
        $orderItem = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 3,
            'unit_price_snapshot' => 10.456, // Should round to 10.46
            'discount_amount_snapshot' => 5.125, // Should round to 5.13
            'final_line_total_snapshot' => 26.243, // Should round to 26.24
            'selected_offer_id' => null,
        ]);
        $orderItem->save();

        // Load relationships
        $order->load(['items.product', 'items.bonuses.bonusProduct']);

        // Act: Transform to DTO
        $result = OrderDTO::fromModel($order);

        // Assert: Check rounding (PHP_ROUND_HALF_UP)
        $this->assertEquals(10.46, $result['items'][0]['unit_price']);
        $this->assertEquals(5.13, $result['items'][0]['discount_amount']);
        $this->assertEquals(26.24, $result['items'][0]['final_total']);
        
        // Subtotal = round(3 * 10.456, 2) = round(31.368, 2) = 31.37
        $this->assertEquals(31.37, $result['subtotal']);
        $this->assertEquals(5.13, $result['total_discount']);
        $this->assertEquals(26.24, $result['final_total']);
    }
}
