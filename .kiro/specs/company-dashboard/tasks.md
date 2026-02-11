# Implementation Plan: Company Dashboard

## Overview

هذه الخطة تحدد المهام اللازمة لتنفيذ لوحة تحكم الشركات (Company Dashboard) في Laravel 12 + Inertia.js + Vue 3.

**استراتيجية التنفيذ:**
بدلاً من بناء كل شيء من الصفر، سنقوم بعمل **Duplicate للـ Admin Dashboard** (Frontend + Backend) ثم نعدل فقط:
- Guards (من `admin` إلى `web`)
- Routes (من `/admin` إلى `/company`)
- Permissions/Authorization (فلترة حسب `user_type = 'company'`)
- Data Filters (فلترة البيانات حسب الشركة المسجلة)

هذا النهج يوفر الوقت ويضمن التناسق في التصميم والوظائف.

## Tasks

- [x] 1. Setup Middleware and Authentication Flow
  - [x] 1.1 Create EnsureUserIsCompany middleware
    - Create `app/Http/Middleware/EnsureUserIsCompany.php`
    - **Copy logic from any existing admin middleware as reference**
    - Implement logic to check `Auth::guard('web')->user()` and verify `user_type === 'company'`
    - Redirect to login if user is null
    - Abort with 403 if user_type is not 'company'
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_
  
  - [x] 1.2 Register middleware alias in bootstrap/app.php
    - Add 'company' alias for EnsureUserIsCompany middleware
    - _Requirements: 2.1_
  
  - [x] 1.3 Modify AuthenticatedSessionController for company user type check
    - Update `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
    - In `store` method, check if `user_type === 'company'` after authentication
    - Redirect to `company.dashboard` if company user
    - Logout using `Auth::guard('web')->logout()` if not company user
    - Invalidate session and regenerate token
    - Redirect to login with error message "This account is not a company account."
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_
  
  - [ ]* 1.4 Write unit tests for middleware
    - Test authenticated non-company user receives 403
    - Test authenticated company user is allowed to proceed (200)
    - Note: Guest redirect to /login is handled by auth:web middleware, not by EnsureUserIsCompany
    - _Requirements: 2.4, 2.5_
  
  - [ ]* 1.5 Write unit tests for login flow
    - Test company user login redirects to company dashboard
    - Test non-company user login is rejected with error message
    - Test logout is called for non-company users
    - _Requirements: 1.3, 1.4, 1.5, 1.7_

- [x] 2. Duplicate Admin Routes to Company Routes
  - [x] 2.1 Copy routes/admin.php to routes/company.php
    - **Duplicate entire file**: `cp routes/admin.php routes/company.php`
    - Replace all `admin` with `company` in route names
    - Replace all `auth:admin` with `auth:web` middleware
    - Replace all `/admin` prefix with `/company`
    - Replace all `Admin\` namespace with `Company\` in controller imports
    - Add `company` middleware to authenticated routes group
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  
  - [x] 2.2 Include company routes in web.php or bootstrap
    - Add `require __DIR__.'/company.php';` in routes/web.php or appropriate location
    - Ensure routes are loaded correctly
    - _Requirements: 3.1_
  
  - [ ]* 2.3 Write unit tests for route configuration
    - Test all company routes have correct prefix
    - Test all company routes have correct middleware stack
    - Test all company routes have correct name prefix
    - Test no overlap with admin or auth routes
    - _Requirements: 3.2, 3.3, 3.4, 3.5, 3.6_

- [x] 3. Duplicate Admin Controllers to Company Controllers
  - [x] 3.1 Copy entire Admin Controllers directory
    - **Duplicate directory**: `cp -r app/Http/Controllers/Admin app/Http/Controllers/Company`
    - This includes all controllers: Dashboard, Profile, Product, User, Category, Tag, etc.
    - _Requirements: 5.1, 5.2_
  
  - [x] 3.2 Update namespace in all Company controllers
    - Replace `namespace App\Http\Controllers\Admin;` with `namespace App\Http\Controllers\Company;`
    - Use find & replace in all files under `app/Http/Controllers/Company/`
    - _Requirements: 5.1_
  
  - [x] 3.3 Update Auth guard in all Company controllers
    - Replace all `Auth::guard('admin')` with `Auth::guard('web')`
    - Replace all `auth('admin')` with `auth('web')`
    - Search in all Company controller files
    - _Requirements: 5.6_
  
  - [x] 3.4 Add user_type filter to Company controllers
    - In all data retrieval methods, add filter: `where('user_type', 'company')`
    - Apply to User model queries where applicable
    - For resource ownership (products, offers), filter by authenticated user's ID
    - _Requirements: 8.2, 10.2, 15.1, 15.2_
  
  - [x] 3.5 Update Inertia render paths
    - Replace all `Inertia::render('Admin/...` with `Inertia::render('Company/...`
    - Use find & replace in all Company controller files
    - _Requirements: 5.5_
  
  - [ ]* 3.6 Write property test for dashboard statistics accuracy
    - **Property 9: Statistics Accuracy**
    - **Validates: Requirements 6.3, 6.4, 6.5**
    - Generate random company user with random products, conversations, and offers
    - Verify dashboard stats match actual counts
  
  - [ ]* 3.7 Write unit tests for DashboardController
    - Test dashboard returns Inertia response
    - Test dashboard includes correct stats
    - Test dashboard includes profile data
    - _Requirements: 6.1, 6.2, 6.6, 6.7_

- [x] 4. Duplicate Frontend Layout Components
  - [x] 4.1 Copy AdminLayout.vue to CompanyLayout.vue
    - **Duplicate file**: `cp resources/js/components/layout/AdminLayout.vue resources/js/components/layout/CompanyLayout.vue`
    - Replace `app-sidebar` with `company-sidebar`
    - Replace `app-header` with `company-header`
    - Keep all styling, dark mode, and responsive logic as-is
    - _Requirements: 4.1, 4.2, 4.5, 4.6_
  
  - [x] 4.2 Copy AppSidebar.vue to CompanySidebar.vue
    - **Duplicate file**: `cp resources/js/components/layout/AppSidebar.vue resources/js/components/layout/CompanySidebar.vue`
    - Update navigation items to company routes (replace `admin.` with `company.`)
    - Update route names: dashboard, profile, products, messages, offers
    - Keep all sidebar expand/collapse logic as-is
    - _Requirements: 4.3_
  
  - [x] 4.3 Copy AppHeader.vue to CompanyHeader.vue
    - **Duplicate file**: `cp resources/js/components/layout/AppHeader.vue resources/js/components/layout/CompanyHeader.vue`
    - Update logout route from `admin.logout` to `logout` (web guard)
    - Update profile route from `admin.profile` to `company.profile`
    - Keep language switcher, dark mode toggle, and all other features as-is
    - _Requirements: 4.4, 4.7, 4.8_

- [x] 5. Duplicate Frontend Pages
  - [x] 5.1 Copy entire Admin Pages directory
    - **Duplicate directory**: `cp -r resources/js/Pages/Admin resources/js/Pages/Company`
    - This includes all pages: Dashboard, Profile, Products, Categories, Tags, Users, etc.
    - _Requirements: 11.1, 11.2, 11.3_
  
  - [x] 5.2 Update all Inertia Links in Company pages
    - Replace all `route('admin.*')` with `route('company.*')`
    - Use find & replace in all files under `resources/js/Pages/Company/`
    - _Requirements: 11.13_
  
  - [x] 5.3 Update Layout imports in Company pages
    - Replace all `import AdminLayout from '@/components/layout/AdminLayout.vue'` 
    - With `import CompanyLayout from '@/components/layout/CompanyLayout.vue'`
    - Replace all `<AdminLayout>` with `<CompanyLayout>`
    - Use find & replace in all Company page files
    - _Requirements: 11.13_
  
  - [x] 5.4 Update API endpoints if any
    - Check for any hardcoded `/admin/` API paths
    - Replace with `/company/` where applicable
    - _Requirements: 3.5_

- [x] 6. Checkpoint - Test Basic Dashboard Access
  - Ensure middleware is working correctly
  - Ensure company users can access dashboard
  - Ensure non-company users are rejected
  - Ensure dashboard displays correctly with layout
  - Raise an implementation question if a blocking ambiguity appears

- [x] 7. Update Profile Management for Companies
  - [x] 7.1 Update ProfileController for CompanyProfile
    - ProfileController already duplicated in step 3.1
    - Modify `show()` method to load `companyProfile` relationship instead of admin data
    - Modify `update()` method to update both User and CompanyProfile in transaction
    - Ensure logo upload stores in 'company-logos' directory
    - _Requirements: 7.1, 7.6, 7.7, 7.8, 7.9_
  
  - [x] 7.2 Copy and update ProfileUpdateRequest
    - **Duplicate**: `cp app/Http/Requests/Admin/ProfileUpdateRequest.php app/Http/Requests/Company/ProfileUpdateRequest.php`
    - Update namespace to `App\Http\Requests\Company`
    - Adjust validation rules for company-specific fields (company_name, category_id, logo)
    - _Requirements: 7.6_
  
  - [x] 7.3 Update Profile.vue page for company fields
    - Profile.vue already duplicated in step 5.1
    - Update form fields to show company-specific data (company_name, category, logo)
    - Update form submission to use company profile structure
    - _Requirements: 7.2, 7.3, 7.4, 7.5_
  
  - [ ]* 7.4 Write property test for profile update atomicity
    - **Property 10: Profile Update Atomicity**
    - **Validates: Requirements 7.7**
    - Generate random valid profile data
    - Verify both User and CompanyProfile are updated
  
  - [ ]* 7.5 Write property test for validation prevents invalid updates
    - **Property 11: Validation Prevents Invalid Updates**
    - **Validates: Requirements 7.10**
    - Generate random invalid profile data
    - Verify no changes are persisted
  
  - [ ]* 7.6 Write unit tests for ProfileController
    - Test profile page displays current data
    - Test valid update succeeds
    - Test invalid update returns errors
    - Test logo upload works
    - _Requirements: 7.1, 7.2, 7.8, 7.9, 7.10_

- [x] 8. Update Products Management for Company Ownership
  - [x] 8.1 Verify Product model exists
    - Check if `app/Models/Product.php` exists
    - If not, create with SoftDeletes, fillable fields, and relationships
    - Ensure user() relationship exists
    - _Requirements: 8.1_
  
  - [x] 8.2 Update or create ProductPolicy
    - Check if `app/Policies/ProductPolicy.php` exists
    - Implement `update()` method: check `$user->id === $product->user_id && $user->user_type === 'company'`
    - Implement `delete()` method: same ownership check
    - _Requirements: 8.10, 15.4, 15.5_
  
  - [x] 8.3 Update ProductController for company ownership
    - ProductController already duplicated in step 3.1
    - In `index()`: filter products by authenticated user: `Auth::guard('web')->user()->products()`
    - In `store()`: automatically set `user_id` to authenticated user's ID
    - In `edit()`, `update()`, `destroy()`: add authorization check using policy
    - _Requirements: 8.1, 8.2, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10_
  
  - [x] 8.4 Copy and update Product Request classes
    - **Duplicate**: `cp app/Http/Requests/StoreProductRequest.php app/Http/Requests/Company/StoreProductRequest.php`
    - **Duplicate**: `cp app/Http/Requests/UpdateProductRequest.php app/Http/Requests/Company/UpdateProductRequest.php`
    - Update namespaces to `App\Http\Requests\Company`
    - Adjust validation rules if needed
    - _Requirements: 8.5, 8.8_
  
  - [x] 8.5 Update Product pages (already duplicated)
    - Products pages already duplicated in step 5.1
    - Verify routes are updated to `company.*` (done in step 5.2)
    - Verify layout is CompanyLayout (done in step 5.3)
    - _Requirements: 8.1, 8.3, 8.4, 8.7_
  
  - [ ]* 8.6 Write property test for product ownership on creation
    - **Property 12: Product Ownership on Creation**
    - **Validates: Requirements 8.6**
    - Generate random product data
    - Verify created product has correct user_id
  
  - [ ]* 8.7 Write property test for product ownership filtering
    - **Property 13: Product Ownership Filtering**
    - **Validates: Requirements 8.1, 8.2, 15.1**
    - Generate random products for multiple companies
    - Verify each company only sees their own products
  
  - [ ]* 8.8 Write property test for product authorization
    - **Property 14: Product Authorization**
    - **Validates: Requirements 8.10, 15.4, 15.5**
    - Generate random products for different companies
    - Verify company cannot update/delete other company's products
  
  - [ ]* 8.9 Write property test for product soft delete
    - **Property 15: Product Soft Delete**
    - **Validates: Requirements 8.9**
    - Generate random products
    - Verify deletion sets deleted_at instead of removing record
  
  - [ ]* 8.10 Write unit tests for ProductController
    - Test index shows only company's products
    - Test create displays form
    - Test store creates product with correct ownership
    - Test edit requires authorization
    - Test update requires authorization
    - Test destroy soft deletes product
    - _Requirements: 8.1, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10_

- [x] 9. Checkpoint - Test Products Management
  - Ensure products CRUD works correctly
  - Ensure authorization prevents cross-company access
  - Ensure soft delete works
  - Raise an implementation question if a blocking ambiguity appears

- [x] 10. Update Messages Management for Company Ownership
  - [x] 10.1 Verify Conversation and Message models exist
    - Check if models exist in `app/Models/`
    - If not, create with proper relationships
    - _Requirements: 9.1_
  
  - [x] 10.2 Update or create ConversationPolicy
    - Check if `app/Policies/ConversationPolicy.php` exists
    - Implement `view()` method to check if user is participant
    - _Requirements: 9.9, 15.4_
  
  - [x] 10.3 Update MessageController for company ownership
    - MessageController already duplicated in step 3.1
    - In `index()`: filter conversations by authenticated user as participant
    - In `show()`: add authorization check using policy
    - In `store()`: validate and create message, update last_message_at
    - _Requirements: 9.1, 9.4, 9.6, 9.7, 9.8, 9.9_
  
  - [x] 10.4 Copy and update MessageStoreRequest
    - **Duplicate**: `cp app/Http/Requests/StoreMessageRequest.php app/Http/Requests/Company/MessageStoreRequest.php` (if exists)
    - Update namespace to `App\Http\Requests\Company`
    - Define validation rules for message content
    - _Requirements: 9.6_
  
  - [x] 10.5 Update Messages pages (already duplicated)
    - Messages pages already duplicated in step 5.1
    - Verify routes are updated to `company.*` (done in step 5.2)
    - Verify layout is CompanyLayout (done in step 5.3)
    - _Requirements: 9.1, 9.3, 9.4, 9.5_
  
  - [ ]* 10.6 Write property test for conversation participant filtering
    - **Property 16: Conversation Participant Filtering**
    - **Validates: Requirements 9.1, 9.2, 15.3**
    - Generate random conversations with different participants
    - Verify each company only sees conversations they're part of
  
  - [ ]* 10.7 Write property test for message chronological ordering
    - **Property 17: Message Chronological Ordering**
    - **Validates: Requirements 9.5**
    - Generate random messages with different timestamps
    - Verify messages are ordered chronologically
  
  - [ ]* 10.8 Write property test for message association
    - **Property 18: Message Association**
    - **Validates: Requirements 9.7, 9.8**
    - Generate random messages
    - Verify message is associated with conversation
    - Verify last_message_at is updated
  
  - [ ]* 10.9 Write property test for conversation authorization
    - **Property 19: Conversation Authorization**
    - **Validates: Requirements 9.9, 15.4**
    - Generate random conversations
    - Verify company cannot access conversations they're not part of
  
  - [ ]* 10.10 Write unit tests for MessageController
    - Test index shows only user's conversations
    - Test show requires authorization
    - Test store validates message content
    - Test store updates last_message_at
    - _Requirements: 9.1, 9.4, 9.6, 9.7, 9.8, 9.9_

- [x] 11. Update Offers Management for Company Ownership
  - [x] 11.1 Verify Offer model exists
    - Check if `app/Models/Offer.php` exists with SoftDeletes
    - If not, create with fillable fields, casts, and relationships
    - _Requirements: 10.1_
  
  - [x] 11.2 Update or create OfferPolicy
    - Check if `app/Policies/OfferPolicy.php` exists
    - Implement `update()` method: check `$user->id === $offer->user_id && $user->user_type === 'company'`
    - Implement `delete()` method: same ownership check
    - _Requirements: 10.10, 15.4, 15.5_
  
  - [x] 11.3 Update OfferController for company ownership
    - OfferController already duplicated in step 3.1
    - In `index()`: filter offers by authenticated user: `Auth::guard('web')->user()->offers()`
    - In `store()`: automatically set `user_id` to authenticated user's ID
    - In `edit()`, `update()`, `destroy()`: add authorization check using policy
    - _Requirements: 10.1, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 10.10_
  
  - [x] 11.4 Copy and update Offer Request classes
    - **Duplicate**: `cp app/Http/Requests/StoreOfferRequest.php app/Http/Requests/Company/StoreOfferRequest.php`
    - **Duplicate**: `cp app/Http/Requests/UpdateOfferRequest.php app/Http/Requests/Company/UpdateOfferRequest.php`
    - Update namespaces to `App\Http\Requests\Company`
    - Adjust validation rules if needed
    - _Requirements: 10.5, 10.8_
  
  - [x] 11.5 Update Offer pages (already duplicated)
    - Offer pages already duplicated in step 5.1
    - Verify routes are updated to `company.*` (done in step 5.2)
    - Verify layout is CompanyLayout (done in step 5.3)
    - _Requirements: 10.1, 10.3, 10.4, 10.7_
  
  - [ ]* 11.6 Write property test for offer ownership on creation
    - **Property 20: Offer Ownership on Creation**
    - **Validates: Requirements 10.6**
    - Generate random offer data
    - Verify created offer has correct user_id
  
  - [ ]* 11.7 Write property test for offer ownership filtering
    - **Property 21: Offer Ownership Filtering**
    - **Validates: Requirements 10.1, 10.2, 15.2**
    - Generate random offers for multiple companies
    - Verify each company only sees their own offers
  
  - [ ]* 11.8 Write property test for offer authorization
    - **Property 22: Offer Authorization**
    - **Validates: Requirements 10.10, 15.4, 15.5**
    - Generate random offers for different companies
    - Verify company cannot update/delete other company's offers
  
  - [ ]* 11.9 Write property test for offer soft delete
    - **Property 23: Offer Soft Delete**
    - **Validates: Requirements 10.9**
    - Generate random offers
    - Verify deletion sets deleted_at instead of removing record
  
  - [ ]* 11.10 Write unit tests for OfferController
    - Test index shows only company's offers
    - Test create displays form
    - Test store creates offer with correct ownership
    - Test edit requires authorization
    - Test update requires authorization
    - Test destroy soft deletes offer
    - _Requirements: 10.1, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 10.10_

- [x] 12. Update Internationalization (Already Exists)
  - [x] 12.1 Add company translations to existing locale files
    - Update `resources/js/locales/ar.json` with company section
    - Update `resources/js/locales/en.json` with company section
    - **Note**: Most translations already exist from Admin, just verify company-specific terms
    - Example structure: `{ "company": { "welcome": "مرحباً", "dashboard": "لوحة التحكم", ... } }`
    - _Requirements: 14.1, 14.2_
  
  - [x] 12.2 Verify language preference persistence
    - Language switching already implemented in Admin
    - Verify it works correctly for company users
    - _Requirements: 14.4_
  
  - [x] 12.3 Verify RTL/LTR layout switching
    - RTL/LTR already implemented in Admin layout
    - Verify CompanyLayout inherits this functionality correctly
    - _Requirements: 14.5, 14.6_
  
  - [ ]* 12.4 Write property test for language preference persistence
    - **Property 24: Language Preference Persistence**
    - **Validates: Requirements 14.4**
    - Generate random language changes
    - Verify preference is persisted and applied
  
  - [ ]* 12.5 Write unit tests for i18n
    - Test company translations exist in locale files
    - Test RTL is applied for Arabic
    - Test LTR is applied for English
    - _Requirements: 14.1, 14.2, 14.5, 14.6_

- [x] 13. Implement Security and Logging
  - [x] 13.1 Add unauthorized access logging
    - Log 403 errors with user and resource information
    - Use Laravel's logging system
    - _Requirements: 15.7_
  
  - [ ]* 13.2 Write property test for unauthorized access logging
    - **Property 25: Unauthorized Access Logging**
    - **Validates: Requirements 15.7**
    - Generate random unauthorized access attempts
    - Verify attempts are logged
  
  - [ ]* 13.3 Write integration tests for guard isolation
    - **Property 3: Admin Guard Isolation**
    - **Validates: Requirements 1.9, 1.10, 12.3, 12.7**
    - Test admin authentication doesn't affect web guard
    - Test web authentication doesn't affect admin guard

- [x] 14. Final Integration Testing
  - [ ]* 14.1 Write end-to-end tests for critical flows
    - Test complete company user journey (login → dashboard → products → logout)
    - Test authorization prevents cross-company access
    - Test non-company user rejection
  
  - [ ]* 14.2 Write property tests for route configuration
    - **Property 4: Company Middleware Protection**
    - **Validates: Requirements 2.6, 3.3**
    - Verify all company routes have correct middleware
    
    - **Property 5: Route Naming Convention**
    - **Validates: Requirements 3.4**
    - Verify all company routes have 'company.' prefix
    
    - **Property 6: Route Path Isolation**
    - **Validates: Requirements 3.5, 3.6, 12.1, 12.2**
    - Verify no path overlaps with admin or auth routes
  
  - [ ]* 14.3 Write property tests for controller patterns
    - **Property 7: Inertia Response Type**
    - **Validates: Requirements 5.5**
    - Verify all view-rendering methods return Inertia responses
    
    - **Property 8: Company Profile Access Pattern**
    - **Validates: Requirements 5.6**
    - Verify profile access uses authenticated user relationship

- [x] 15. Final Checkpoint - Complete System Test
  - Run all unit tests and ensure they pass
  - Run all property tests with 100+ iterations
  - Test complete user flows manually
  - Verify no conflicts with admin system
  - Verify all authorization rules work correctly
  - Raise an implementation question if a blocking ambiguity appears

## Notes

### General Guidelines

- Tasks marked with `*` are optional test tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
- Checkpoints ensure incremental validation of functionality

### Technical Implementation Notes

#### Authentication & Guards

**Always use explicit guards:**
- In all Company controllers and middleware, use `Auth::guard('web')`
- Do NOT use `Auth::user()` without guard to avoid ambiguity if admin guard is active

**EnsureUserIsCompany behavior:**
- Guest redirect to /login is handled by `auth:web` middleware
- EnsureUserIsCompany should only handle:
  - Company user → allow
  - Non-company user → abort(403)

**Logout must always be guard-specific:**
- Company logout:
  ```php
  Auth::guard('web')->logout();
  $request->session()->invalidate();
  $request->session()->regenerateToken();
  ```
- Admin logout:
  ```php
  Auth::guard('admin')->logout();
  $request->session()->invalidate();
  $request->session()->regenerateToken();
  ```
- Never call plain `Auth::logout()`

**Session isolation:**
- Simultaneous admin + company login in same browser is NOT required
- Sequential login (admin → logout → company) is safe as long as guard logout, session invalidate, and token regenerate are respected

#### Routes

**Controller imports:**
- In routes/web.php, ensure correct imports:
  ```php
  use App\Http\Controllers\Company\DashboardController;
  use App\Http\Controllers\Company\ProductController;
  ```
- Controller class names must match exactly (no CompanyDashboardController vs DashboardController mismatch)

**Middleware stack:**
- Company routes: `['web', 'auth:web', 'company']`
- The `web` middleware is typically already applied in routes/web.php, but keeping it explicit is acceptable for clarity

#### BaseCompanyController

**Implementation:**
```php
protected function getCompanyUser()
{
    return Auth::guard('web')->user();
}
```
- Never rely on default Auth

#### Internationalization

**Do NOT create Laravel backend translation files:**
- We already use vue-i18n with:
  - `resources/js/locales/ar.json`
  - `resources/js/locales/en.json`
- Add company section:
  ```json
  {
    "company": {
      "welcome": "مرحباً",
      "dashboard": "لوحة التحكم",
      ...
    }
  }
  ```
- Use `$t('company.*')` everywhere in Vue components

#### Middleware Tests

**Test scenarios:**
- Authenticated company user → 200
- Authenticated non-company user → 403
- Guest redirect is tested via `auth:web`, not EnsureUserIsCompany

#### Inertia

**All Company controllers returning views must use:**
```php
Inertia::render(...)
```
- Never use blade views

#### Authorization

**Policies must always verify:**
```php
$user->id === $model->user_id 
AND 
$user->user_type === 'company'
```
- Never rely on ID only

#### Transactions

**Profile update must stay wrapped in DB::transaction** (already correct in design)

#### Logging

**For unauthorized access, log:**
- user_id
- route
- model id (if applicable)
- This helps with security auditing

#### Testing Priority (if MVP)

**If skipping tests for speed, minimum to keep:**
- Login flow tests
- EnsureUserIsCompany tests
- Product ownership tests
- Everything else is optional
