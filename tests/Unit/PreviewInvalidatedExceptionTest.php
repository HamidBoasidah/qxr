<?php

namespace Tests\Unit;

use App\Exceptions\PreviewInvalidatedException;
use Illuminate\Http\Request;
use Tests\TestCase;

class PreviewInvalidatedExceptionTest extends TestCase
{
    public function test_exception_has_correct_http_status_code(): void
    {
        $exception = new PreviewInvalidatedException();
        
        $this->assertEquals(409, $exception->getStatusCode());
    }

    public function test_exception_has_default_message(): void
    {
        $exception = new PreviewInvalidatedException();
        
        $this->assertEquals(
            'Preview is no longer valid. Please re-preview your order.',
            $exception->getMessage()
        );
    }

    public function test_exception_accepts_custom_message(): void
    {
        $customMessage = 'Custom preview invalidation message';
        $exception = new PreviewInvalidatedException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
    }

    public function test_exception_stores_details_array(): void
    {
        $details = [
            [
                'type' => 'price_changed',
                'product_id' => 123,
                'product_name' => 'Test Product',
                'preview_price' => 10.00,
                'current_price' => 10.50
            ],
            [
                'type' => 'best_offer_changed',
                'product_id' => 456,
                'product_name' => 'Another Product',
                'previous_offer_id' => null,
                'previous_offer_title' => null,
                'previous_reward_type' => null,
                'current_offer_id' => 789,
                'current_offer_title' => '10% Off',
                'current_reward_type' => 'percentage_discount',
                'change_reason' => 'new_better_offer'
            ]
        ];

        $exception = new PreviewInvalidatedException(
            'Preview is no longer valid. Please re-preview your order.',
            $details
        );
        
        $this->assertEquals($details, $exception->getDetails());
    }

    public function test_exception_renders_correct_json_response(): void
    {
        $details = [
            [
                'type' => 'price_changed',
                'product_id' => 123,
                'product_name' => 'Test Product',
                'preview_price' => 10.00,
                'current_price' => 10.50
            ]
        ];

        $exception = new PreviewInvalidatedException(
            'Preview is no longer valid. Please re-preview your order.',
            $details
        );

        $request = Request::create('/test', 'POST');
        $response = $exception->render($request);

        $this->assertEquals(409, $response->getStatusCode());
        
        $responseData = $response->getData(true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Preview is no longer valid. Please re-preview your order.', $responseData['message']);
        $this->assertEquals($details, $responseData['details']);
        $this->assertEquals('PREVIEW_INVALIDATED', $responseData['error_code']);
        $this->assertEquals(409, $responseData['status_code']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function test_exception_handles_empty_details_array(): void
    {
        $exception = new PreviewInvalidatedException();
        
        $this->assertEquals([], $exception->getDetails());
    }
}
