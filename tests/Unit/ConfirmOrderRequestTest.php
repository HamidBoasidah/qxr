<?php

namespace Tests\Unit;

use App\Http\Requests\Api\ConfirmOrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConfirmOrderRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_authorizes_authenticated_customer()
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $this->actingAs($customer);

        $request = new ConfirmOrderRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_rejects_non_customer_user()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $this->actingAs($company);

        $request = new ConfirmOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_rejects_unauthenticated_user()
    {
        $request = new ConfirmOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_validates_required_preview_token()
    {
        $request = new ConfirmOrderRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('preview_token'));
    }

    /** @test */
    public function it_validates_preview_token_format()
    {
        $request = new ConfirmOrderRequest();
        $rules = $request->rules();

        // Test invalid formats
        $invalidTokens = [
            'invalid-token',
            'PV-20260219-ABC',  // Only 3 chars instead of 4
            'PV-2026021-ABCD',  // Only 7 digits instead of 8
            'PV-20260219-abcd', // Lowercase not allowed
            'PV-20260219-ABC!', // Special char not allowed
        ];

        foreach ($invalidTokens as $token) {
            $validator = Validator::make(['preview_token' => $token], $rules);
            $this->assertFalse($validator->passes(), "Token '{$token}' should be invalid");
            $this->assertTrue($validator->errors()->has('preview_token'));
        }

        // Test valid format
        $validToken = 'PV-20260219-A3F2';
        $validator = Validator::make(['preview_token' => $validToken], $rules);
        $this->assertTrue($validator->passes(), "Token '{$validToken}' should be valid");
    }
}
