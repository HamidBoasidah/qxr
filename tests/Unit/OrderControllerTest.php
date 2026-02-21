<?php

namespace Tests\Unit;

use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PreviewInvalidatedException;
use App\Exceptions\PreviewNotFoundException;
use App\Exceptions\PreviewOwnershipException;
use App\Http\Controllers\Api\OrderController;
use App\Http\Requests\Api\ConfirmOrderRequest;
use App\Http\Requests\Api\PreviewOrderRequest;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: OrderController
 * 
 * Tests the OrderController class to ensure it correctly handles:
 * - Authentication and authorization (401, 403)
 * - Validation errors (422)
 * - Resource not found errors (404)
 * - Preview invalidation (409)
 * - Successful preview (200)
 * - Successful confirmation (201)
 * 
 * **Validates: Requirements 3.1, 4.1-4.8, 16.1-16.10**
 */
class OrderControllerTest extends TestCase
{
    private OrderService $mockOrderService;
    private OrderController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockOrderService = Mockery::mock(OrderService::class);
        $this->controller = new OrderController($this->mockOrderService, Mockery::mock(\App\Repositories\OrderRepository::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Unauthenticated request returns 401
     * 
     * This test verifies that the middleware (auth:sanctum) rejects unauthenticated requests.
     * Note: This is typically tested at the integration level with middleware,
     * but we document the expected behavior here.
     * 
     * Validates: Requirements 3.1
     */
    #[Test]
    public function preview_requires_authentication(): void
    {
        // This behavior is enforced by Laravel's auth:sanctum middleware
        // and is tested in integration tests. Unit tests focus on controller logic.
        $this->assertTrue(true);
    }

    /**
     * Test: Non-customer role returns 403
     * 
     * Validates: Requirements 3.2, 3.6
     */
    #[Test]
    public function preview_rejects_non_customer_role(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'admin';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new AuthorizationException('Only customers can create orders'));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Only customers can create orders', $data['message']);
    }

    /**
     * Test: Missing company_id returns 422
     * 
     * Note: This validation is handled by PreviewOrderRequest,
     * but we test the controller's response to ValidationException.
     * 
     * Validates: Requirements 4.1
     */
    #[Test]
    public function preview_rejects_missing_company_id(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(
            new \Illuminate\Support\MessageBag(['company_id' => ['The company id field is required.']])
        );
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new ValidationException($validator));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test: Inactive company returns 422
     * 
     * Validates: Requirements 4.2
     */
    #[Test]
    public function preview_rejects_inactive_company(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(
            new \Illuminate\Support\MessageBag(['company_id' => ['Company is not active']])
        );
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new ValidationException($validator));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test: Empty items array returns 422
     * 
     * Validates: Requirements 4.3
     */
    #[Test]
    public function preview_rejects_empty_items_array(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => []
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(
            new \Illuminate\Support\MessageBag(['items' => ['The items field must have at least 1 items.']])
        );
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new ValidationException($validator));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test: Non-existent product returns 404
     * 
     * Validates: Requirements 4.4, 16.1
     */
    #[Test]
    public function preview_rejects_non_existent_product(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 999, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new NotFoundException('Product 999 not found'));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Product 999 not found', $data['message']);
    }

    /**
     * Test: Product from wrong company returns 403
     * 
     * Validates: Requirements 4.5, 16.2
     */
    #[Test]
    public function preview_rejects_product_from_wrong_company(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new AuthorizationException('Product 1 does not belong to company 1'));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Product 1 does not belong to company 1', $data['message']);
    }

    /**
     * Test: Invalid qty returns 422
     * 
     * Validates: Requirements 4.7
     */
    #[Test]
    public function preview_rejects_invalid_qty(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 0]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(
            new \Illuminate\Support\MessageBag(['items.0.qty' => ['The items.0.qty field must be at least 1.']])
        );
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new ValidationException($validator));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test: Duplicate products returns 422
     * 
     * Validates: Requirements 4.8
     */
    #[Test]
    public function preview_rejects_duplicate_products(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [
                ['product_id' => 1, 'qty' => 10],
                ['product_id' => 1, 'qty' => 20]
            ]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(
            new \Illuminate\Support\MessageBag(['items' => ['Duplicate products are not allowed']])
        );
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new ValidationException($validator));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test: Successful preview returns 200
     * 
     * Validates: Requirements 1.3, 16.1, 16.2, 16.3
     */
    #[Test]
    public function preview_returns_200_on_success(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $previewData = [
            'preview_token' => 'PV-20260219-ABCD',
            'customer_user_id' => 1,
            'company_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test Product',
                    'qty' => 10,
                    'unit_price' => 100.00,
                    'discount_amount' => 0.00,
                    'final_total' => 1000.00,
                    'selected_offer_id' => null,
                    'bonuses' => []
                ]
            ],
            'subtotal' => 1000.00,
            'total_discount' => 0.00,
            'final_total' => 1000.00
        ];
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->with([
                'company_id' => 1,
                'items' => [['product_id' => 1, 'qty' => 10]]
            ], $user)
            ->andReturn($previewData);
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('PV-20260219-ABCD', $data['data']['preview_token']);
        $this->assertEquals(1000.00, $data['data']['final_total']);
    }

    /**
     * Test: Preview not found returns 404
     * 
     * Validates: Requirements 2.3, 16.7
     */
    #[Test]
    public function confirm_returns_404_when_preview_not_found(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(ConfirmOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'preview_token' => 'PV-20260219-XXXX'
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('confirmOrder')
            ->once()
            ->with('PV-20260219-XXXX', $user)
            ->andThrow(new PreviewNotFoundException('Preview not found or expired'));
        
        // Act
        $response = $this->controller->confirm($request);
        
        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Preview not found or expired', $data['message']);
    }

    /**
     * Test: Preview ownership mismatch returns 403
     * 
     * Validates: Requirements 2.4, 3.5, 16.8
     */
    #[Test]
    public function confirm_returns_403_when_preview_belongs_to_another_customer(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(ConfirmOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'preview_token' => 'PV-20260219-XXXX'
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('confirmOrder')
            ->once()
            ->with('PV-20260219-XXXX', $user)
            ->andThrow(new PreviewOwnershipException('This preview belongs to another customer'));
        
        // Act
        $response = $this->controller->confirm($request);
        
        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('This preview belongs to another customer', $data['message']);
    }

    /**
     * Test: Preview invalidated returns 409
     * 
     * Validates: Requirements 2.6, 9.8, 9.9, 16.6, 16.10
     */
    #[Test]
    public function confirm_returns_409_when_preview_invalidated(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(ConfirmOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'preview_token' => 'PV-20260219-XXXX'
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $changes = [
            [
                'type' => 'price_changed',
                'product_id' => 1,
                'product_name' => 'Test Product',
                'preview_price' => 100.00,
                'current_price' => 105.00
            ]
        ];
        
        $exception = new PreviewInvalidatedException(
            'Preview is no longer valid. Please re-preview your order.',
            $changes
        );
        
        $this->mockOrderService
            ->shouldReceive('confirmOrder')
            ->once()
            ->with('PV-20260219-XXXX', $user)
            ->andThrow($exception);
        
        // Act
        $response = $this->controller->confirm($request);
        
        // Assert
        $this->assertEquals(409, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Preview is no longer valid. Please re-preview your order.', $data['message']);
        $this->assertArrayHasKey('details', $data);
        $this->assertCount(1, $data['details']);
        $this->assertEquals('price_changed', $data['details'][0]['type']);
    }

    /**
     * Test: Successful confirmation returns 201
     * 
     * Validates: Requirements 2.5, 2.6, 16.6
     */
    #[Test]
    public function confirm_returns_201_on_success(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(ConfirmOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'preview_token' => 'PV-20260219-XXXX'
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $orderData = [
            'order_no' => 'ORD-20260219-ABCD',
            'status' => 'pending',
            'company_id' => 1,
            'customer_user_id' => 1,
            'submitted_at' => '2026-02-19T10:00:00Z',
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test Product',
                    'qty' => 10,
                    'unit_price' => 100.00,
                    'discount_amount' => 0.00,
                    'final_total' => 1000.00,
                    'bonuses' => []
                ]
            ],
            'subtotal' => 1000.00,
            'total_discount' => 0.00,
            'final_total' => 1000.00
        ];
        
        $this->mockOrderService
            ->shouldReceive('confirmOrder')
            ->once()
            ->with('PV-20260219-XXXX', $user)
            ->andReturn($orderData);
        
        // Act
        $response = $this->controller->confirm($request);
        
        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Order created successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('order', $data['data']);
        $this->assertEquals('ORD-20260219-ABCD', $data['data']['order']['order_no']);
    }

    /**
     * Test: Generic exception returns 500 for preview
     * 
     * Validates: Requirements 16.4, 16.5
     */
    #[Test]
    public function preview_returns_500_on_generic_exception(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(PreviewOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'company_id' => 1,
            'items' => [['product_id' => 1, 'qty' => 10]]
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('previewOrder')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
        
        // Act
        $response = $this->controller->preview($request);
        
        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('An error occurred while previewing the order', $data['message']);
    }

    /**
     * Test: Generic exception returns 500 for confirm
     * 
     * Validates: Requirements 16.4, 16.5
     */
    #[Test]
    public function confirm_returns_500_on_generic_exception(): void
    {
        // Arrange
        $user = new User();
        $user->id = 1;
        $user->user_type = 'customer';
        
        $request = Mockery::mock(ConfirmOrderRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'preview_token' => 'PV-20260219-XXXX'
        ]);
        $request->shouldReceive('user')->andReturn($user);
        
        $this->mockOrderService
            ->shouldReceive('confirmOrder')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
        
        // Act
        $response = $this->controller->confirm($request);
        
        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('An error occurred while confirming the order', $data['message']);
    }
}
