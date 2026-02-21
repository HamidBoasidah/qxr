<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Property 16.2: Null offer date handling
 *
 * Verifies that offers with null start_at and/or null end_at are treated as active
 * (already started, never expires) during preview selection.
 */
class OfferNullDateHandlingPropertyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function offer_with_null_start_and_end_dates_is_applied(): void
    {
        // Arrange
        $company = User::factory()->create([
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'user_type' => 'customer',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true,
            'base_price' => 100.00,
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => null, // already started
            'end_at' => null,   // never expires
            'title' => 'Null Dates Offer',
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 5],
            ],
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $item = $response->json('data.items.0');
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEquals('Null Dates Offer', $item['offer_title']);
    }

    #[Test]
    public function offer_with_null_end_date_is_applied_if_already_started(): void
    {
        // Arrange
        $company = User::factory()->create([
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'user_type' => 'customer',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true,
            'base_price' => 50.00,
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'start_at' => now()->subDay(), // started yesterday
            'end_at' => null,               // open-ended
            'title' => 'Open Ended Offer',
        ]);

        OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_fixed',
            'discount_percent' => null,
            'discount_fixed' => 5.00,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $item = $response->json('data.items.0');
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEquals('Open Ended Offer', $item['offer_title']);
    }
}
