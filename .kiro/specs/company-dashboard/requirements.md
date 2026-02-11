# Requirements Document

## Introduction

هذه الوثيقة تحدد متطلبات ميزة لوحة تحكم الشركات (Company Dashboard) في نظام Laravel 12 + Inertia.js + Vue 3. الهدف هو إنشاء لوحة تحكم مستقلة تمامًا للشركات، منفصلة عن لوحة تحكم المشرفين (Admin Dashboard)، مع ضمان عدم وجود أي تعارض في Guards، Routes، Middleware، أو Views.

## Glossary

- **System**: نظام لوحة تحكم الشركات بالكامل
- **Company_Dashboard**: لوحة التحكم الخاصة بالشركات
- **Web_Guard**: نظام المصادقة الافتراضي (auth:web) الذي يستخدم جدول users
- **Admin_Guard**: نظام المصادقة الخاص بالمشرفين (auth:admin) الذي يستخدم جدول admins
- **Company_User**: مستخدم من نوع 'company' في جدول users
- **Company_Profile**: بروفايل الشركة المرتبط بـ Company_User (علاقة 1:1)
- **Company_Layout**: واجهة المستخدم الرئيسية للوحة تحكم الشركات
- **Admin_Layout**: واجهة المستخدم الرئيسية للوحة تحكم المشرفين
- **Company_Middleware**: Middleware للتحقق من أن المستخدم من نوع 'company'
- **Company_Routes**: مسارات لوحة تحكم الشركات (prefix: /company) داخل routes/web.php
- **Admin_Routes**: مسارات لوحة تحكم المشرفين (prefix: /admin)
- **Auth_Routes**: مسارات المصادقة الافتراضية (Breeze) في routes/auth.php
- **Inertia**: إطار عمل Inertia.js للربط بين Laravel و Vue 3
- **Company_Controller**: Controllers خاصة بوظائف الشركات
- **Admin_Controller**: Controllers خاصة بوظائف المشرفين
- **Laravel_12**: إصدار Laravel المستخدم في المشروع

## Requirements

### Requirement 1: Authentication and Authorization

**User Story:** كشركة، أريد تسجيل الدخول إلى لوحة التحكم الخاصة بي باستخدام بيانات اعتمادي، حتى أتمكن من إدارة أعمالي بشكل آمن.

#### Acceptance Criteria

1. WHEN a Company_User accesses the login page, THE System SHALL use the existing Auth_Routes at /login
2. WHEN a Company_User submits valid credentials, THE System SHALL authenticate using Web_Guard
3. WHEN authentication succeeds, THE System SHALL verify that user_type equals 'company'
4. IF user_type equals 'company', THEN THE System SHALL redirect to /company/dashboard
5. IF user_type does not equal 'company', THEN THE System SHALL immediately logout the user
6. WHEN logout occurs due to invalid user_type, THE System SHALL redirect to /login
7. WHEN redirecting to login after logout, THE System SHALL display error message "This account is not a company account."
8. THE System SHALL NOT modify Auth_Routes in routes/auth.php
9. THE System SHALL NOT modify or interfere with Admin_Guard authentication
10. THE System SHALL NOT modify or interfere with Admin_Routes authentication flow

### Requirement 2: Company Middleware

**User Story:** كمطور، أريد التأكد من أن فقط المستخدمين من نوع 'company' يمكنهم الوصول إلى لوحة تحكم الشركات، حتى أحافظ على أمان النظام.

#### Acceptance Criteria

1. THE System SHALL create a Company_Middleware named 'EnsureUserIsCompany'
2. WHEN Company_Middleware is invoked, THE System SHALL verify that the authenticated user exists
3. WHEN an authenticated user exists, THE System SHALL verify that user_type equals 'company'
4. IF user_type does not equal 'company', THEN THE System SHALL abort with 403 Forbidden status
5. WHEN user_type equals 'company', THE System SHALL allow the request to proceed
6. THE Company_Middleware SHALL be applied to all Company_Routes
7. THE Company_Middleware SHALL NOT interfere with Admin_Routes or Auth_Routes

### Requirement 3: Isolated Routing System

**User Story:** كمطور، أريد نظام توجيه منفصل تمامًا للشركات، حتى لا يحدث أي تعارض مع مسارات المشرفين.

#### Acceptance Criteria

1. THE System SHALL define Company_Routes inside routes/web.php file
2. THE System SHALL group Company_Routes with prefix '/company'
3. THE System SHALL apply middleware group ['web', 'auth:web', 'company'] to all Company_Routes
4. THE System SHALL use route name prefix 'company.' for all Company_Routes
5. THE Company_Routes SHALL NOT overlap with Admin_Routes paths
6. THE Company_Routes SHALL NOT overlap with Auth_Routes paths
7. THE Company_Routes SHALL be organized in a dedicated route group within routes/web.php

### Requirement 4: Company Dashboard Layout

**User Story:** كشركة، أريد واجهة مستخدم مخصصة لإدارة أعمالي، حتى أتمكن من التنقل بسهولة بين الوظائف المختلفة.

#### Acceptance Criteria

1. THE System SHALL create a Company_Layout component at 'resources/js/components/layout/CompanyLayout.vue'
2. THE Company_Layout SHALL be independent from Admin_Layout
3. THE Company_Layout SHALL include a sidebar component for navigation
4. THE Company_Layout SHALL include a header component with user information
5. THE Company_Layout SHALL support dark mode toggle
6. THE Company_Layout SHALL support i18n language switching (Arabic/English)
7. THE Company_Layout SHALL display the authenticated Company_User name and avatar
8. THE Company_Layout SHALL include a logout button

### Requirement 5: Company Controllers

**User Story:** كمطور، أريد controllers منفصلة لوظائف الشركات، حتى أحافظ على تنظيم الكود وفصل المسؤوليات.

#### Acceptance Criteria

1. THE System SHALL create a directory 'app/Http/Controllers/Company'
2. THE System SHALL create Company_Controller classes within this directory
3. THE Company_Controller classes SHALL handle only company-specific business logic
4. THE Company_Controller classes SHALL NOT interfere with Admin_Controller classes
5. THE Company_Controller classes SHALL use Inertia responses for rendering views
6. THE Company_Controller classes SHALL access Company_Profile through authenticated user relationship

### Requirement 6: Company Dashboard Home Page

**User Story:** كشركة، أريد رؤية صفحة رئيسية تعرض إحصائيات أعمالي، حتى أتمكن من متابعة أداء شركتي.

#### Acceptance Criteria

1. WHEN a Company_User accesses /company/dashboard, THE System SHALL display the dashboard home page
2. THE System SHALL render the page using Company_Layout
3. THE System SHALL display company statistics including total products count
4. THE System SHALL display company statistics including total messages count
5. THE System SHALL display company statistics including active offers count
6. THE System SHALL display company profile information including company name and logo
7. THE System SHALL use Inertia to render 'resources/js/Pages/Company/Dashboard.vue'

### Requirement 7: Company Profile Management

**User Story:** كشركة، أريد إدارة معلومات بروفايل شركتي، حتى أتمكن من تحديث بياناتي وعرضها للعملاء.

#### Acceptance Criteria

1. WHEN a Company_User accesses /company/profile, THE System SHALL display the profile management page
2. THE System SHALL display current Company_Profile data including company_name
3. THE System SHALL display current Company_Profile data including category
4. THE System SHALL display current Company_Profile data including logo
5. THE System SHALL display current User data including email, phone_number, and social media links
6. WHEN a Company_User submits profile updates, THE System SHALL validate the input data
7. WHEN validation passes, THE System SHALL update both User and Company_Profile records
8. WHEN logo upload is requested, THE System SHALL store the file and update logo_path
9. WHEN profile update succeeds, THE System SHALL redirect back with success message
10. IF validation fails, THEN THE System SHALL return errors without modifying data

### Requirement 8: Company Products Management

**User Story:** كشركة، أريد إدارة منتجاتي (إضافة، تعديل، حذف، عرض)، حتى أتمكن من عرض منتجاتي للعملاء.

#### Acceptance Criteria

1. WHEN a Company_User accesses /company/products, THE System SHALL display a list of products owned by the company
2. THE System SHALL filter products by the authenticated Company_User
3. THE System SHALL display product information including name, description, price, and images
4. WHEN a Company_User clicks create product, THE System SHALL display a product creation form
5. WHEN a Company_User submits a new product, THE System SHALL validate the input data
6. WHEN validation passes, THE System SHALL create the product associated with the Company_User
7. WHEN a Company_User clicks edit product, THE System SHALL display the product edit form with current data
8. WHEN a Company_User submits product updates, THE System SHALL validate and update the product
9. WHEN a Company_User clicks delete product, THE System SHALL soft delete the product
10. THE System SHALL NOT allow Company_User to view or modify products owned by other companies

### Requirement 9: Company Messages Management

**User Story:** كشركة، أريد إدارة المحادثات والرسائل مع العملاء، حتى أتمكن من التواصل معهم والرد على استفساراتهم.

#### Acceptance Criteria

1. WHEN a Company_User accesses /company/messages, THE System SHALL display a list of conversations
2. THE System SHALL filter conversations where the Company_User is a participant
3. THE System SHALL display conversation information including last message and timestamp
4. WHEN a Company_User clicks on a conversation, THE System SHALL display the full message history
5. THE System SHALL display messages in chronological order
6. WHEN a Company_User sends a new message, THE System SHALL validate the message content
7. WHEN validation passes, THE System SHALL store the message and associate it with the conversation
8. THE System SHALL update the conversation's last_message_at timestamp
9. THE System SHALL NOT allow Company_User to access conversations they are not part of

### Requirement 10: Company Offers Management

**User Story:** كشركة، أريد إدارة العروض الخاصة بمنتجاتي، حتى أتمكن من جذب العملاء وزيادة المبيعات.

#### Acceptance Criteria

1. WHEN a Company_User accesses /company/offers, THE System SHALL display a list of offers created by the company
2. THE System SHALL filter offers by the authenticated Company_User
3. THE System SHALL display offer information including title, discount percentage, and validity period
4. WHEN a Company_User clicks create offer, THE System SHALL display an offer creation form
5. WHEN a Company_User submits a new offer, THE System SHALL validate the input data
6. WHEN validation passes, THE System SHALL create the offer associated with the Company_User
7. WHEN a Company_User clicks edit offer, THE System SHALL display the offer edit form with current data
8. WHEN a Company_User submits offer updates, THE System SHALL validate and update the offer
9. WHEN a Company_User clicks delete offer, THE System SHALL soft delete the offer
10. THE System SHALL NOT allow Company_User to view or modify offers created by other companies

### Requirement 11: Frontend Pages Structure

**User Story:** كمطور، أريد بنية منظمة لصفحات Vue الخاصة بالشركات، حتى يسهل صيانة وتطوير الواجهات.

#### Acceptance Criteria

1. THE System SHALL create a directory 'resources/js/Pages/Company'
2. THE System SHALL organize Company pages within this directory
3. THE System SHALL create Dashboard.vue for the home page
4. THE System SHALL create Profile.vue for profile management
5. THE System SHALL create Products/Index.vue for products list
6. THE System SHALL create Products/Create.vue for product creation
7. THE System SHALL create Products/Edit.vue for product editing
8. THE System SHALL create Messages/Index.vue for conversations list
9. THE System SHALL create Messages/Show.vue for conversation details
10. THE System SHALL create Offers/Index.vue for offers list
11. THE System SHALL create Offers/Create.vue for offer creation
12. THE System SHALL create Offers/Edit.vue for offer editing
13. ALL Company pages SHALL use Company_Layout as the parent layout

### Requirement 12: No Conflicts with Admin System

**User Story:** كمطور، أريد التأكد من عدم وجود أي تعارض بين نظام الشركات ونظام المشرفين، حتى يعمل كلا النظامين بشكل مستقل وآمن.

#### Acceptance Criteria

1. THE Company_Routes SHALL NOT use the '/admin' prefix
2. THE Company_Routes SHALL NOT use the 'admin.' route name prefix
3. THE Company_Middleware SHALL NOT modify or check Admin_Guard authentication
4. THE Company_Layout SHALL NOT import or extend Admin_Layout components
5. THE Company_Controller classes SHALL NOT be placed in 'app/Http/Controllers/Admin' directory
6. THE Company pages SHALL NOT be placed in 'resources/js/Pages/Admin' directory
7. THE System SHALL logically separate Admin_Guard and Web_Guard authentication flows
8. Simultaneous admin and company login in the same browser is NOT required unless explicitly configured via separate session drivers or subdomains

### Requirement 13: Styling and UI Consistency

**User Story:** كشركة، أريد واجهة مستخدم متناسقة وجذابة، حتى تكون تجربة استخدام لوحة التحكم مريحة واحترافية.

#### Acceptance Criteria

1. THE Company_Layout SHALL use Tailwind CSS for styling
2. THE Company_Layout SHALL support dark mode with consistent color scheme
3. THE Company_Layout SHALL be responsive and work on mobile, tablet, and desktop devices
4. THE Company_Layout SHALL use the same design patterns as Admin_Layout for consistency
5. THE Company_Layout SHALL display company branding (logo and name) in the sidebar
6. THE System SHALL use consistent spacing, typography, and color palette across all Company pages
7. THE System SHALL provide visual feedback for user actions (loading states, success/error messages)

### Requirement 14: Internationalization Support

**User Story:** كشركة، أريد استخدام لوحة التحكم باللغة العربية أو الإنجليزية، حتى أتمكن من العمل باللغة التي أفضلها.

#### Acceptance Criteria

1. THE System SHALL support Arabic and English languages using vue-i18n
2. THE System SHALL load translation files for Company pages
3. THE System SHALL display all UI text in the selected language
4. WHEN a Company_User changes language, THE System SHALL persist the preference
5. THE System SHALL apply RTL layout for Arabic language
6. THE System SHALL apply LTR layout for English language
7. THE System SHALL translate all labels, buttons, messages, and validation errors

### Requirement 15: Data Isolation and Security

**User Story:** كشركة، أريد التأكد من أن بياناتي محمية ولا يمكن لشركات أخرى الوصول إليها، حتى أحافظ على خصوصية وأمان معلوماتي.

#### Acceptance Criteria

1. WHEN querying products, THE System SHALL filter by authenticated Company_User
2. WHEN querying offers, THE System SHALL filter by authenticated Company_User
3. WHEN querying conversations, THE System SHALL filter by authenticated Company_User as participant
4. WHEN a Company_User attempts to access another company's resource, THE System SHALL return 403 Forbidden
5. THE System SHALL validate resource ownership before any update or delete operation
6. THE System SHALL use Laravel policies or gates for authorization checks
7. THE System SHALL log unauthorized access attempts for security monitoring
