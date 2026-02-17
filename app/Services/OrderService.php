<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\DTOs\OrderDTO;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PriceVerifier $priceVerifier,
        private OfferVerifier $offerVerifier,
        private CalculationVerifier $calculationVerifier
    ) {
    }

    /**
     * Create a new order with verification
     * 
     * @param array $data Validated order data from request
     * @param User $customer Authenticated customer user
     * @return array OrderDTO array representation
     * @throws AuthorizationException if user is not a customer or products don't belong to company
     * @throws ValidationException if company is inactive or products are invalid
     */
    public function createOrder(array $data, User $customer): array
    {
        // Verify user has customer role
        $this->verifyAuthorization($customer);
        
        // Verify company exists and is active
        $company = $this->orderRepository->findCompany($data['company_id']);
        if (!$company) {
            throw new ValidationException('Company not found');
        }
        if (!$company->is_active) {
            throw new ValidationException('Company is not active');
        }
        
        // Verify all products belong to company and are active
        $this->verifyProducts($data['order_items'], $data['company_id']);
        
        // Call PriceVerifier to verify prices
        $this->priceVerifier->verifyPrices($data['order_items']);
        
        // Call OfferVerifier to verify offers
        $this->offerVerifier->verifyOffers($data['order_items'], $customer->id);
        
        // Call CalculationVerifier to verify calculations
        $this->calculationVerifier->verifyCalculations(
            $data['order_items'],
            $data['order_item_bonuses'] ?? []
        );
        
        // Call OrderRepository to create order in transaction
        $order = $this->orderRepository->createOrderWithTransaction(
            $customer->id,
            $data
        );
        
        // Transform to OrderDTO and return
        return OrderDTO::fromModel($order);
    }

    /**
     * Verify user is a customer
     * 
     * @param User $customer User to verify
     * @throws AuthorizationException if user is not a customer
     */
    private function verifyAuthorization(User $customer): void
    {
        if ($customer->user_type !== 'customer') {
            throw new AuthorizationException('Only customers can create orders');
        }
    }

    /**
     * Verify all products belong to company and are active
     * 
     * @param array $orderItems Array of order items from request
     * @param int $companyId Company ID from request
     * @throws AuthorizationException if product doesn't belong to company
     * @throws ValidationException if product is not active
     */
    private function verifyProducts(array $orderItems, int $companyId): void
    {
        foreach ($orderItems as $item) {
            $product = $this->orderRepository->findProduct($item['product_id']);
            
            if (!$product) {
                throw new ValidationException("Product {$item['product_id']} not found");
            }
            
            if ($product->company_user_id !== $companyId) {
                throw new AuthorizationException(
                    "Product {$item['product_id']} does not belong to company {$companyId}"
                );
            }
            
            if (!$product->is_active) {
                throw new ValidationException("Product {$item['product_id']} is not active");
            }
        }
    }
}
