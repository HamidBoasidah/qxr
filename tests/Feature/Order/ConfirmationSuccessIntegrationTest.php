<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Successful Order Confirmation
 * 
 * **Validates: Requirements 2.1-2.8, 9.1-9.19, 10.1-10.7, 11.1-11.5, 12.1-12.6, 13.1-13.5**
 * 
 * This test verifies the complete end-to-end flow from preview to successful
 * confirmation with database persistence and preview token deletion.
 */
class ConfirmationSuccessIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete preview → confirm flow with successful order creation
     * 
     * **Validates: Requirements 2.1-2.8, 9.1-9.19, 10.1-10.7, 11.1-11.5, 12.1-12.6, 13.1-13.5**
     * 
     * Verifies that:
     * 1. Preview is created successfully
     * 2. Confirmation succeeds with HTTP 201
     * 3. Order header is persisted with correct data
     * 4. Order items are persisted with snapshots
     * 5. Bonuses are created for bonus_qty offers
     * 6. Status log is created
     * 7. Preview token is deleted from cache
     * 8. Response contains complete order details
     */
    #[Test]
    public function confirmation_succeeds_with_complete_order_persistence(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        // Product 1: With percentage discount
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '10% Off 100+',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Product 2: With bonus qty
        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.00,
            'is_active' => true
        ]);
        
        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'Buy 50 Get 5 Free',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 50,
            'reward_type' => 'bonus_qty',
            'discount_percent' => null,
            'discount_fixed' => null,
            'bonus_product_id' => $product2->id,
            'bonus_qty' => 5
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test successful confirmation',
            'items' => [
                ['product_id' => $product1->id, 'qty' => 100],
                ['product_id' => $product2->id, 'qty' => 50]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse->assertStatus(200);
        $previewToken = $previewResponse->json('data.preview_token');

        // Verify preview is in cache
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Step 2: Confirm order
        $confirmData = ['preview_token' => $previewToken];

        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', $confirmData);

        // Assert: HTTP response
        $confirmResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully'
            ]);

        $orderData = $confirmResponse->json('data.order');

        // Assert: Order header persisted
        $this->assertDatabaseHas('orders', [
            'company_user_id' => $company->id,
            'customer_user_id' => $customer->id,
            'status' => 'pending',
            'notes_customer' => 'Test successful confirmation'
        ]);

        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->order_no);
        $this->assertMatchesRegularExpression('/^ORD-\d{14}-[A-Z0-9]{4}$/', $order->order_no);
        $this->assertNotNull($order->submitted_at);
        $this->assertNull($order->approved_at);
        $this->assertNull($order->delivered_at);

        // Assert: Order items persisted with snapshots
        $this->assertEquals(2, $order->items()->count());

        // Item 1: Percentage discount
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'qty' => 100,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 100.00,
            'final_line_total_snapshot' => 900.00,
            'selected_offer_id' => $offer1->id
        ]);

        // Item 2: Bonus qty
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'qty' => 50,
            'unit_price_snapshot' => 5.00,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 250.00,
            'selected_offer_id' => $offer2->id
        ]);

        // Assert: Bonus created for item 2
        $orderItem2 = OrderItem::where('order_id', $order->id)
            ->where('product_id', $product2->id)
            ->first();

        $this->assertDatabaseHas('order_item_bonuses', [
            'order_item_id' => $orderItem2->id,
            'bonus_product_id' => $product2->id,
            'bonus_qty' => 5,
            'offer_id' => $offer2->id
        ]);

        $this->assertEquals(1, OrderItemBonus::count());

        // Assert: Status log created
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'pending',
            'changed_by_user_id' => $customer->id
        ]);

        // Assert: Preview token deleted from cache
        $this->assertNull(Cache::get("preview:{$previewToken}"));

        // Assert: Response structure
        $this->assertEquals($order->order_no, $orderData['order_no']);
        $this->assertEquals('pending', $orderData['status']);
        $this->assertEquals('Test successful confirmation', $orderData['notes']);
        $this->assertCount(2, $orderData['items']);

        // Assert: Response totals
        $this->assertEquals(1250.00, $orderData['subtotal']); // 100*10 + 50*5 = 1000 + 250
        $this->assertEquals(100.00, $orderData['total_discount']); // 10% of 1000
        $this->assertEquals(1150.00, $orderData['final_total']); // 1250 - 100

        // Assert: Item details in response
        $responseItem1 = collect($orderData['items'])->firstWhere('product_id', $product1->id);
        $this->assertNotNull($responseItem1);
        $this->assertEquals(100, $responseItem1['qty']);
        $this->assertEquals(10.00, $responseItem1['unit_price']);
        $this->assertEquals(100.00, $responseItem1['discount_amount']);
        $this->assertEquals(900.00, $responseItem1['final_total']);
        $this->assertEquals($offer1->id, $responseItem1['selected_offer_id']);
        $this->assertEmpty($responseItem1['bonuses']);

        $responseItem2 = collect($orderData['items'])->firstWhere('product_id', $product2->id);
        $this->assertNotNull($responseItem2);
        $this->assertEquals(50, $responseItem2['qty']);
        $this->assertEquals(5.00, $responseItem2['unit_price']);
        $this->assertEquals(0.00, $responseItem2['discount_amount']);
        $this->assertEquals(250.00, $responseItem2['final_total']);
        $this->assertEquals($offer2->id, $responseItem2['selected_offer_id']);
        $this->assertCount(1, $responseItem2['bonuses']);
        $this->assertEquals($product2->id, $responseItem2['bonuses'][0]['bonus_product_id']);
        $this->assertEquals(5, $responseItem2['bonuses'][0]['bonus_qty']);
    }

    /**
     * Test: Confirmation with no offers
     * 
     * **Validates: Requirements 2.5, 10.1-10.7, 11.1-11.5**
     */
    #[Test]
    public function confirmation_succeeds_with_no_offers(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 10]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Confirm order
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert
        $confirmResponse->assertStatus(201);

        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(1, $order->items()->count());

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 10,
            'unit_price_snapshot' => 15.00,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 150.00,
            'selected_offer_id' => null
        ]);

        $this->assertEquals(0, OrderItemBonus::count());
        $this->assertNull(Cache::get("preview:{$previewToken}"));
    }

    /**
     * Test: Confirmation without notes
     * 
     * **Validates: Requirements 10.6**
     */
    #[Test]
    public function confirmation_succeeds_without_notes(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true
        ]);

        // Step 1: Create preview without notes
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 5]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Confirm order
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert
        $confirmResponse->assertStatus(201);

        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertNull($order->notes_customer);

        $orderData = $confirmResponse->json('data.order');
        $this->assertNull($orderData['notes']);
    }
}
