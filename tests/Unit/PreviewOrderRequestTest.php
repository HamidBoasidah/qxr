<?php

namespace Tests\Unit;

use App\Http\Requests\Api\PreviewOrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PreviewOrderRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_authorizes_authenticated_customer()
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $this->actingAs($customer);

        $request = new PreviewOrderRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_rejects_non_customer_user()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $this->actingAs($company);

        $request = new PreviewOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_rejects_unauthenticated_user()
    {
        $request = new PreviewOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $request = new PreviewOrderRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('company_id'));
        $this->assertTrue($validator->errors()->has('items'));
    }

    /** @test */
    public function it_validates_qty_is_integer_and_minimum_one()
    {
        $request = new PreviewOrderRequest();
        $rules = $request->rules();

        $data = [
            'company_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 0, // Invalid: must be at least 1
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('items.0.qty'));
    }

    /** @test */
    public function it_detects_duplicate_products()
    {
        $customer = User::factory()->create(['user_type' => 'customer']);

        $data = [
            'company_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'qty' => 100,
                ],
                [
                    'product_id' => 1, // Duplicate
                    'qty' => 50,
                ]
            ]
        ];

        $this->actingAs($customer);
        $request = PreviewOrderRequest::create('/api/orders/preview', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('items'));
        $this->assertStringContainsString('Duplicate', $validator->errors()->first('items'));
    }
}
