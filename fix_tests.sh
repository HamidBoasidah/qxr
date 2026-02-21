#!/bin/bash

# Fix all Preview property test files
for file in tests/Feature/Order/PreviewDuplicateProductRejectionPropertyTest.php \
            tests/Feature/Order/PreviewProductOwnershipValidationPropertyTest.php \
            tests/Feature/Order/PreviewNonCustomerRoleRejectionPropertyTest.php \
            tests/Feature/Order/PreviewValidRequestAcceptancePropertyTest.php \
            tests/Feature/Order/PreviewStorageAndRetrievalPropertyTest.php \
            tests/Feature/Order/PreviewRoundingConsistencyPropertyTest.php; do
    
    # Replace Company with User for company creation
    sed -i '' "s/\$company = Company::factory()->create/\$company = User::factory()->create/g" "$file"
    
    # Replace company_id with company_user_id
    sed -i '' "s/'company_id' => \$company->id/'company_user_id' => \$company->id/g" "$file"
    
    # Replace price with base_price
    sed -i '' "s/'price' => fake()/'base_price' => fake()/g" "$file"
    
    # Replace role with user_type for customer
    sed -i '' "s/'role' => 'customer'/'user_type' => 'customer'/g" "$file"
    
    # Add user_type for company (this is trickier, need to be careful)
    sed -i '' "s/\$company = User::factory()->create(\[/\$company = User::factory()->create([\n                'user_type' => 'company',/g" "$file"
    
    # Fix the is_active line that might have been duplicated
    sed -i '' "s/'user_type' => 'company',\n                'is_active' => true/'user_type' => 'company',\n                'is_active' => true/g" "$file"
done

echo "Fixed all test files"
