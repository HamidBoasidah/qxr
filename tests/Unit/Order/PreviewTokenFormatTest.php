<?php

namespace Tests\Unit\Order;

use App\Services\OrderService;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Preview Token Format and Cache Key
 * 
 * **Validates: Requirements 7.2, 8.5**
 * 
 * Tests that preview tokens:
 * - Match format PV-YYYYMMDD-XXXX
 * - Use cache key format "preview:{$previewToken}"
 * - Are unique (no collisions)
 * - Handle collisions by regenerating
 */
class PreviewTokenFormatTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test that preview_token matches format PV-YYYYMMDD-XXXX
     * 
     * Format: PV-{date}-{4-char-hex}
     * Example: PV-20260220-A3F2
     */
    #[Test]
    public function it_generates_preview_token_in_correct_format(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 5]
            ]
        ];

        // Act
        $result = $this->service->previewOrder($previewData, $customer);

        // Assert: Check token format
        $token = $result['preview_token'];
        $this->assertMatchesRegularExpression(
            '/^PV-\d{8}-[A-F0-9]{4}$/',
            $token,
            'Preview token should match format PV-YYYYMMDD-XXXX'
        );
    }

    /**
     * Test that token date portion matches current date
     */
    #[Test]
    public function it_includes_current_date_in_token(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 3]
            ]
        ];

        // Act
        $result = $this->service->previewOrder($previewData, $customer);

        // Assert: Extract date portion and verify it matches today
        $token = $result['preview_token'];
        preg_match('/^PV-(\d{8})-[A-F0-9]{4}$/', $token, $matches);
        
        $this->assertNotEmpty($matches, 'Token should match expected format');
        $tokenDate = $matches[1];
        $expectedDate = now()->format('Ymd');
        
        $this->assertEquals($expectedDate, $tokenDate, 
            'Token date portion should match current date');
    }

    /**
     * Test that cache key format is "preview:{$previewToken}"
     */
    #[Test]
    public function it_uses_correct_cache_key_format(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 10]
            ]
        ];

        // Act
        $result = $this->service->previewOrder($previewData, $customer);
        $token = $result['preview_token'];

        // Assert: Verify cache key exists with correct format
        $expectedCacheKey = "preview:{$token}";
        $this->assertTrue(
            Cache::has($expectedCacheKey),
            "Cache should have key in format 'preview:{token}'"
        );

        // Verify stored data matches returned data
        $cachedData = Cache::get($expectedCacheKey);
        $this->assertEquals($result['preview_token'], $cachedData['preview_token']);
        $this->assertEquals($result['customer_user_id'], $cachedData['customer_user_id']);
        $this->assertEquals($result['company_id'], $cachedData['company_id']);
    }

    /**
     * Test uniqueness of generated tokens
     * 
     * Generates multiple tokens concurrently and verifies no duplicates
     */
    #[Test]
    public function it_generates_unique_tokens(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ];

        // Act: Generate multiple tokens
        $tokens = [];
        for ($i = 0; $i < 50; $i++) {
            $result = $this->service->previewOrder($previewData, $customer);
            $tokens[] = $result['preview_token'];
        }

        // Assert: All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(50, $uniqueTokens, 
            'All 50 generated tokens should be unique');
    }

    /**
     * Test collision handling: regenerates token if key already exists
     * 
     * Pre-populate cache with a token, then verify generator creates a different one
     */
    #[Test]
    public function it_handles_token_collision_by_regenerating(): void
    {
        // Arrange: Pre-populate cache with a token for today
        $existingToken = 'PV-' . now()->format('Ymd') . '-0000';
        Cache::put("preview:{$existingToken}", ['test' => 'data'], now()->addMinutes(15));

        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ];

        // Act: Generate new token (should avoid collision)
        $result = $this->service->previewOrder($previewData, $customer);
        $newToken = $result['preview_token'];

        // Assert: New token should be different from existing one
        $this->assertNotEquals($existingToken, $newToken, 
            'Generator should produce a different token to avoid collision');
        
        // Both should exist in cache
        $this->assertTrue(Cache::has("preview:{$existingToken}"));
        $this->assertTrue(Cache::has("preview:{$newToken}"));
    }

    /**
     * Test preview token expiration (15 minutes)
     */
    #[Test]
    public function it_sets_preview_token_expiration_to_15_minutes(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ];

        // Act
        $result = $this->service->previewOrder($previewData, $customer);
        $token = $result['preview_token'];
        $cacheKey = "preview:{$token}";

        // Assert: Token should exist now
        $this->assertTrue(Cache::has($cacheKey));

        // Fast-forward time by 14 minutes (should still exist)
        $this->travel(14)->minutes();
        $this->assertTrue(Cache::has($cacheKey), 
            'Token should still exist after 14 minutes');

        // Fast-forward time by 2 more minutes (total 16 minutes, should be expired)
        $this->travel(2)->minutes();
        $this->assertFalse(Cache::has($cacheKey), 
            'Token should be expired after 16 minutes');
    }

    /**
     * Test that random portion is uppercase hexadecimal
     */
    #[Test]
    public function it_uses_uppercase_hexadecimal_for_random_portion(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ];

        // Act: Generate multiple tokens to test randomness
        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->previewOrder($previewData, $customer);
            $token = $result['preview_token'];
            
            // Extract random portion
            preg_match('/^PV-\d{8}-([A-F0-9]{4})$/', $token, $matches);
            
            // Assert: Random portion should be 4-character uppercase hex
            $this->assertNotEmpty($matches, 'Token should match format');
            $randomPortion = $matches[1];
            $this->assertMatchesRegularExpression('/^[A-F0-9]{4}$/', $randomPortion, 
                'Random portion should be 4 uppercase hexadecimal characters');
        }
    }

    /**
     * Test that token format is consistent across multiple calls
     */
    #[Test]
    public function it_maintains_consistent_format_across_calls(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ];

        // Act & Assert: Generate 20 tokens and verify all match format
        $pattern = '/^PV-\d{8}-[A-F0-9]{4}$/';
        
        for ($i = 0; $i < 20; $i++) {
            $result = $this->service->previewOrder($previewData, $customer);
            $token = $result['preview_token'];
            
            $this->assertMatchesRegularExpression($pattern, $token, 
                "Token #{$i} should match expected format");
        }
    }
}
