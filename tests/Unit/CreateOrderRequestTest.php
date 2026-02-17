<?php

namespace Tests\Unit;

use App\Http\Requests\Api\CreateOrderRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateOrderRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_authorizes_authenticated_customer()
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $this->actingAs($customer);

        $request = new CreateOrderRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_rejects_non_customer_user()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $this->actingAs($company);

        $request = new CreateOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_rejects_unauthenticated_user()
    {
        $request = new CreateOrderRequest();
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $request = new CreateOrderRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('company_id'));
        $this->assertTrue($validator->errors()->has('order_items'));
    }

    /** @test */
    public function it_validates_qty_is_integer_and_minimum_one()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $product = Product::factory()->create(['company_user_id' => $company->id]);

        $request = new CreateOrderRequest();
        $rules = $request->rules();

        $data = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 0, // Invalid: must be at least 1
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 10.00,
                    'selected_offer_id' => null,
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('order_items.0.qty'));
    }

    /** @test */
    public function it_validates_bonus_qty_is_integer_and_minimum_one()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $product = Product::factory()->create(['company_user_id' => $company->id]);

        $request = new CreateOrderRequest();
        $rules = $request->rules();

        $data = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1000.00,
                    'selected_offer_id' => null,
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 0, // Invalid: must be at least 1
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('order_item_bonuses.0.bonus_qty'));
    }

    /** @test */
    public function it_detects_duplicate_products()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $product = Product::factory()->create(['company_user_id' => $company->id]);
        $customer = User::factory()->create(['user_type' => 'customer']);

        $data = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1000.00,
                    'selected_offer_id' => null,
                ],
                [
                    'product_id' => $product->id, // Duplicate
                    'qty' => 50,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 500.00,
                    'selected_offer_id' => null,
                ]
            ]
        ];

        $this->actingAs($customer);
        $request = CreateOrderRequest::create('/api/orders', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('order_items'));
        $this->assertStringContainsString('Duplicate', $validator->errors()->first('order_items'));
    }

    /** @test */
    public function it_validates_order_item_index_bounds()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $product = Product::factory()->create(['company_user_id' => $company->id]);
        $customer = User::factory()->create(['user_type' => 'customer']);

        $data = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1000.00,
                    'selected_offer_id' => null,
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 5, // Out of bounds (only index 0 exists)
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 10,
                ]
            ]
        ];

        $this->actingAs($customer);
        $request = CreateOrderRequest::create('/api/orders', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('order_item_bonuses'));
        $this->assertStringContainsString('Invalid order_item_index', $validator->errors()->first('order_item_bonuses'));
    }

    /** @test */
    public function it_detects_duplicate_bonuses_for_same_item()
    {
        $company = User::factory()->create(['user_type' => 'company']);
        $product = Product::factory()->create(['company_user_id' => $company->id]);
        $customer = User::factory()->create(['user_type' => 'customer']);

        $data = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1000.00,
                    'selected_offer_id' => null,
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 10,
                ],
                [
                    'order_item_index' => 0, // Duplicate bonus for same item
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 5,
                ]
            ]
        ];

        $this->actingAs($customer);
        $request = CreateOrderRequest::create('/api/orders', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('order_item_bonuses.1'));
        $this->assertStringContainsString('Multiple bonuses', $validator->errors()->first('order_item_bonuses.1'));
    }
}
