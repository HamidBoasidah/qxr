<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Response Format Validation
 * 
 * **Validates: Requirements 7.1-7.6, 15.1-15.8**
 * 
 * Property 25: Preview response structure (HTTP 200, preview_token, items array, totals)
 * Property 26: Preview token format (PV-YYYYMMDD-XXXX)
 * Property 27: Preview item details completeness
 * Property 28: Preview bonus details
 * Property 29: Preview notes persistence
 * Property 64: Confirmation response structure (HTTP 201, success, message, order data)
 * Property 65: Order details completeness
 * Property 66: Item details completeness
 * Property 67: Bonus details completeness
 * Property 68: Response totals accuracy
 */
class ResponseFormatPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview response has correct structure
     * 
     * **Validates: Requirements 7.1, 7.5**
     * 
     * Property 25: For any successful preview, the response should have HTTP 200 status
     * and include preview_token, items array, and totals (subtotal, total_discount, final_total).
     */
    #[Test]
    public function preview_response_has_correct_structure(): void
    {
        // Feature: order-creation-api, Property 25: Preview response structure
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            
            $numProducts = fake()->numberBetween(1, 5);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $items = [];
            foreach ($products as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }

            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $orderService->previewOrder($previewData, $customer);

            // Assert: Response structure
            $this->assertIsArray($response, 'Preview should return an array');
            
            // Verify required fields exist
            $this->assertArrayHasKey('preview_token', $response, 'Response should have preview_token');
            $this->assertArrayHasKey('items', $response, 'Response should have items array');
            $this->assertArrayHasKey('subtotal', $response, 'Response should have subtotal');
            $this->assertArrayHasKey('total_discount', $response, 'Response should have total_discount');
            $this->assertArrayHasKey('final_total', $response, 'Response should have final_total');
            
            // Verify items is an array
            $this->assertIsArray($response['items'], 'Items should be an array');
            $this->assertCount($numProducts, $response['items'], 'Items count should match request');
        }
    }

    /**
     * Property Test: Preview token has correct format
     * 
     * **Validates: Requirements 7.2**
     * 
     * Property 26: For any generated preview_token, it should match the format
     * "PV-YYYYMMDD-XXXX" where YYYYMMDD is the current date and XXXX is a
     * 4-character uppercase alphanumeric string.
     */
    #[Test]
    public function preview_token_has_correct_format(): void
    {
        // Feature: order-creation-api, Property 26: Preview token format
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            // Act: Submit preview request
            $response = $orderService->previewOrder($previewData, $customer);

            // Assert: Token format
            $previewToken = $response['preview_token'];
            $this->assertNotNull($previewToken, 'Preview token should not be null');
            
            // Verify format: PV-YYYYMMDD-XXXX
            $this->assertMatchesRegularExpression(
                '/^PV-\d{8}-[A-Z0-9]{4}$/',
                $previewToken,
                'Preview token should match format PV-YYYYMMDD-XXXX'
            );
            
            // Verify date part is today
            $datePart = substr($previewToken, 3, 8);
            $expectedDate = now()->format('Ymd');
            $this->assertEquals(
                $expectedDate,
                $datePart,
                'Preview token date should be today'
            );
        }
    }

    /**
     * Property Test: Preview item details are complete
     * 
     * **Validates: Requirements 7.3**
     * 
     * Property 27: For any item in the preview response, it should include product_id,
     * product_name, qty, unit_price, discount_amount, final_total, selected_offer_id,
     * and offer_title.
     */
    #[Test]
    public function preview_item_details_are_complete(): void
    {
        // Feature: order-creation-api, Property 27: Preview item details completeness
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            // Act: Submit preview request
            $response = $orderService->previewOrder($previewData, $customer);

            // Assert: Item details completeness
            $item = $response['items'][0];
            $this->assertNotNull($item, 'Item should exist');
            
            $requiredFields = [
                'product_id',
                'product_name',
                'qty',
                'unit_price',
                'discount_amount',
                'final_total',
                'selected_offer_id',
                'offer_title',
                'bonuses'
            ];
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $item,
                    "Item should have {$field} field"
                );
            }
            
            // Verify data types
            $this->assertIsInt($item['product_id'], 'product_id should be integer');
            $this->assertIsString($item['product_name'], 'product_name should be string');
            $this->assertIsInt($item['qty'], 'qty should be integer');
            $this->assertIsNumeric($item['unit_price'], 'unit_price should be numeric');
            $this->assertIsNumeric($item['discount_amount'], 'discount_amount should be numeric');
            $this->assertIsNumeric($item['final_total'], 'final_total should be numeric');
            $this->assertIsArray($item['bonuses'], 'bonuses should be array');
        }
    }

    /**
     * Property Test: Preview bonus details are complete
     * 
     * **Validates: Requirements 7.4**
     * 
     * Property 28: For any item with a bonus_qty offer in the preview response,
     * it should include a bonuses array with bonus_product_id, bonus_product_name,
     * bonus_qty, and offer_title.
     */
    #[Test]
    public function preview_bonus_details_are_complete(): void
    {
        // Feature: order-creation-api, Property 28: Preview bonus details
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with bonus offer
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true,
                'base_price' => fake()->randomFloat(2, 10, 100)
            ]);

            // Create bonus offer
            $offer = Offer::factory()->create([
                'status' => 'active',
                'scope' => 'public',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay()
            ]);

            $minQty = fake()->numberBetween(10, 50);
            $bonusQty = fake()->numberBetween(1, 10);
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty,
                'reward_type' => 'bonus_qty',
                'bonus_qty' => $bonusQty,
                'bonus_product_id' => $product->id
            ]);

            $qty = $minQty * fake()->numberBetween(1, 3);
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];

            // Act: Submit preview request
            $response = $orderService->previewOrder($previewData, $customer);

            // Assert: Bonus details completeness
            $item = $response['items'][0];
            $this->assertNotNull($item, 'Item should exist');
            $this->assertIsArray($item['bonuses'], 'Bonuses should be array');
            
            if (count($item['bonuses']) > 0) {
                $bonus = $item['bonuses'][0];
                
                $requiredFields = [
                    'bonus_product_id',
                    'bonus_product_name',
                    'bonus_qty',
                    'offer_title'
                ];
                
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $bonus,
                        "Bonus should have {$field} field"
                    );
                }
                
                // Verify data types
                $this->assertIsInt($bonus['bonus_product_id'], 'bonus_product_id should be integer');
                $this->assertIsString($bonus['bonus_product_name'], 'bonus_product_name should be string');
                $this->assertIsInt($bonus['bonus_qty'], 'bonus_qty should be integer');
                $this->assertIsString($bonus['offer_title'], 'offer_title should be string');
            }
        }
    }

    /**
     * Property Test: Preview notes are persisted
     * 
     * **Validates: Requirements 7.6**
     * 
     * Property 29: For any preview request with notes provided, the preview
     * response should include those notes.
     */
    #[Test]
    public function preview_notes_are_persisted(): void
    {
        // Feature: order-creation-api, Property 29: Preview notes persistence
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with notes
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $notes = fake()->sentence();
            $previewData = [
                'company_id' => $company->id,
                'notes' => $notes,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            // Act: Submit preview request
            $response = $orderService->previewOrder($previewData, $customer);

            // Assert: Notes persistence
            $this->assertEquals(
                $notes,
                $response['notes'],
                'Preview response should include the submitted notes'
            );
        }
    }

    /**
     * Property Test: Confirmation response has correct structure
     * 
     * **Validates: Requirements 15.1, 15.2, 15.3**
     * 
     * Property 64: For any successful confirmation, the response should have HTTP 201 status,
     * success=true, message="Order created successfully", and a data object containing
     * the complete order details.
     */
    #[Test]
    public function confirmation_response_has_correct_structure(): void
    {
        // Feature: order-creation-api, Property 64: Confirmation response structure
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create preview first
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];

            // Act: Confirm order
            $response = $orderService->confirmOrder($previewToken, $customer);

            // Assert: Response structure
            $this->assertIsArray($response, 'Confirmation should return an array');
            
            // Verify required fields exist
            $this->assertArrayHasKey('id', $response, 'Response should have id');
            $this->assertArrayHasKey('order_no', $response, 'Response should have order_no');
            $this->assertArrayHasKey('status', $response, 'Response should have status');
            $this->assertArrayHasKey('items', $response, 'Response should have items array');
        }
    }

    /**
     * Property Test: Order details are complete
     * 
     * **Validates: Requirements 15.4, 15.8**
     * 
     * Property 65: For any successful confirmation, the response should include
     * order_no, status, submitted_at, notes, items array, subtotal, total_discount,
     * and final_total.
     */
    #[Test]
    public function order_details_are_complete(): void
    {
        // Feature: order-creation-api, Property 65: Order details completeness
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create preview first
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $notes = fake()->sentence();
            $previewData = [
                'company_id' => $company->id,
                'notes' => $notes,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];

            // Act: Confirm order
            $response = $orderService->confirmOrder($previewToken, $customer);

            // Assert: Order details completeness
            $this->assertNotNull($response, 'Order should exist');
            
            $requiredFields = [
                'id',
                'order_no',
                'status',
                'submitted_at',
                'notes',
                'items',
                'subtotal',
                'total_discount',
                'final_total'
            ];
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $response,
                    "Order should have {$field} field"
                );
            }
            
            // Verify data types
            $this->assertIsInt($response['id'], 'id should be integer');
            $this->assertIsString($response['order_no'], 'order_no should be string');
            $this->assertIsString($response['status'], 'status should be string');
            $this->assertIsString($response['submitted_at'], 'submitted_at should be string');
            $this->assertIsArray($response['items'], 'items should be array');
            $this->assertIsNumeric($response['subtotal'], 'subtotal should be numeric');
            $this->assertIsNumeric($response['total_discount'], 'total_discount should be numeric');
            $this->assertIsNumeric($response['final_total'], 'final_total should be numeric');
            
            // Verify notes match
            $this->assertEquals($notes, $response['notes'], 'Notes should match');
        }
    }

    /**
     * Property Test: Item details are complete in confirmation
     * 
     * **Validates: Requirements 15.5, 15.6**
     * 
     * Property 66: For any order item in the confirmation response, it should include
     * product_id, product_name, qty, unit_price, discount_amount, final_total,
     * and selected_offer_id.
     */
    #[Test]
    public function item_details_are_complete_in_confirmation(): void
    {
        // Feature: order-creation-api, Property 66: Item details completeness
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create preview first
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => fake()->numberBetween(1, 100)]
                ]
            ];

            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];

            // Act: Confirm order
            $response = $orderService->confirmOrder($previewToken, $customer);

            // Assert: Item details completeness
            $item = $response['items'][0];
            $this->assertNotNull($item, 'Item should exist');
            
            $requiredFields = [
                'id',
                'product_id',
                'product_name',
                'qty',
                'unit_price',
                'discount_amount',
                'final_total',
                'selected_offer_id',
                'bonuses'
            ];
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $item,
                    "Item should have {$field} field"
                );
            }
            
            // Verify data types
            $this->assertIsInt($item['id'], 'id should be integer');
            $this->assertIsInt($item['product_id'], 'product_id should be integer');
            $this->assertIsString($item['product_name'], 'product_name should be string');
            $this->assertIsInt($item['qty'], 'qty should be integer');
            $this->assertIsNumeric($item['unit_price'], 'unit_price should be numeric');
            $this->assertIsNumeric($item['discount_amount'], 'discount_amount should be numeric');
            $this->assertIsNumeric($item['final_total'], 'final_total should be numeric');
            $this->assertIsArray($item['bonuses'], 'bonuses should be array');
        }
    }

    /**
     * Property Test: Bonus details are complete in confirmation
     * 
     * **Validates: Requirements 15.7**
     * 
     * Property 67: For any order item with bonuses in the confirmation response,
     * the bonuses array should include bonus_product_id, bonus_product_name,
     * bonus_qty, and offer_title.
     */
    #[Test]
    public function bonus_details_are_complete_in_confirmation(): void
    {
        // Feature: order-creation-api, Property 67: Bonus details completeness
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with bonus offer
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'is_active' => true,
                'base_price' => fake()->randomFloat(2, 10, 100)
            ]);

            // Create bonus offer
            $offer = Offer::factory()->create([
                'status' => 'active',
                'scope' => 'public',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay()
            ]);

            $minQty = fake()->numberBetween(10, 50);
            $bonusQty = fake()->numberBetween(1, 10);
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty,
                'reward_type' => 'bonus_qty',
                'bonus_qty' => $bonusQty,
                'bonus_product_id' => $product->id
            ]);

            $qty = $minQty * fake()->numberBetween(1, 3);
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];

            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];

            // Act: Confirm order
            $response = $orderService->confirmOrder($previewToken, $customer);

            // Assert: Bonus details completeness
            $item = $response['items'][0];
            $this->assertNotNull($item, 'Item should exist');
            $this->assertIsArray($item['bonuses'], 'Bonuses should be array');
            
            if (count($item['bonuses']) > 0) {
                $bonus = $item['bonuses'][0];
                
                $requiredFields = [
                    'bonus_product_id',
                    'bonus_product_name',
                    'bonus_qty',
                    'offer_title'
                ];
                
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $bonus,
                        "Bonus should have {$field} field"
                    );
                }
                
                // Verify data types
                $this->assertIsInt($bonus['bonus_product_id'], 'bonus_product_id should be integer');
                $this->assertIsString($bonus['bonus_product_name'], 'bonus_product_name should be string');
                $this->assertIsInt($bonus['bonus_qty'], 'bonus_qty should be integer');
                $this->assertIsString($bonus['offer_title'], 'offer_title should be string');
            }
        }
    }

    /**
     * Property Test: Response totals are accurate
     * 
     * **Validates: Requirements 15.8**
     * 
     * Property 68: For any successful confirmation, the response subtotal should equal
     * the sum of line_subtotal (rounded qty × unit_price values) for all items
     * (rounded to 2 decimal places), total_discount should equal the sum of
     * discount_amount for all items, and final_total should equal the sum of
     * final_total for all items (all rounded to 2 decimal places).
     */
    #[Test]
    public function response_totals_are_accurate(): void
    {
        // Feature: order-creation-api, Property 68: Response totals accuracy
        
        $orderService = app(OrderService::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create preview with multiple items
            $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
            $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
            
            $numProducts = fake()->numberBetween(2, 5);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $items = [];
            foreach ($products as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }

            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];

            // Act: Confirm order
            $response = $orderService->confirmOrder($previewToken, $customer);

            // Assert: Totals accuracy
            $orderItems = $response['items'];
            
            // Calculate expected totals
            $expectedSubtotal = 0;
            $expectedTotalDiscount = 0;
            $expectedFinalTotal = 0;
            
            foreach ($orderItems as $item) {
                $lineSubtotal = round($item['qty'] * $item['unit_price'], 2, PHP_ROUND_HALF_UP);
                $expectedSubtotal += $lineSubtotal;
                $expectedTotalDiscount += $item['discount_amount'];
                $expectedFinalTotal += $item['final_total'];
            }
            
            $expectedSubtotal = round($expectedSubtotal, 2, PHP_ROUND_HALF_UP);
            $expectedTotalDiscount = round($expectedTotalDiscount, 2, PHP_ROUND_HALF_UP);
            $expectedFinalTotal = round($expectedFinalTotal, 2, PHP_ROUND_HALF_UP);
            
            // Verify totals match
            $this->assertEquals(
                $expectedSubtotal,
                $response['subtotal'],
                'Subtotal should equal sum of line subtotals'
            );
            
            $this->assertEquals(
                $expectedTotalDiscount,
                $response['total_discount'],
                'Total discount should equal sum of discount amounts'
            );
            
            $this->assertEquals(
                $expectedFinalTotal,
                $response['final_total'],
                'Final total should equal sum of final line totals'
            );
        }
    }
}
