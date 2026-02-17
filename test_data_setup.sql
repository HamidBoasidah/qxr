-- ============================================
-- Test Data Setup for Order Creation API
-- ============================================
-- هذا الملف يحتوي على بيانات تجريبية لاختبار الـ API
-- ============================================

-- 1. إنشاء شركة تجريبية (إذا لم تكن موجودة)
INSERT INTO users (name, email, password, user_type, is_active, created_at, updated_at)
VALUES 
    ('شركة الشفاء للأدوية', 'company@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', 1, NOW(), NOW()),
    ('صيدلية السالم', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- احصل على IDs
SET @company_id = (SELECT id FROM users WHERE email = 'company@example.com' LIMIT 1);
SET @customer_id = (SELECT id FROM users WHERE email = 'customer@example.com' LIMIT 1);

-- 2. إنشاء منتجات تجريبية
INSERT INTO products (company_user_id, name, description, base_price, is_active, created_at, updated_at)
VALUES 
    (@company_id, 'Aspirin 500mg', 'أسبرين 500 ملغ - علبة 100 قرص', 10.00, 1, NOW(), NOW()),
    (@company_id, 'Paracetamol 1000mg', 'باراسيتامول 1000 ملغ - علبة 50 قرص', 20.00, 1, NOW(), NOW()),
    (@company_id, 'Vitamin C 1000mg', 'فيتامين سي 1000 ملغ - علبة 30 كبسولة', 15.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- احصل على product IDs
SET @product_1 = (SELECT id FROM products WHERE name = 'Aspirin 500mg' AND company_user_id = @company_id LIMIT 1);
SET @product_2 = (SELECT id FROM products WHERE name = 'Paracetamol 1000mg' AND company_user_id = @company_id LIMIT 1);
SET @product_3 = (SELECT id FROM products WHERE name = 'Vitamin C 1000mg' AND company_user_id = @company_id LIMIT 1);

-- 3. إنشاء عروض تجريبية

-- عرض 1: خصم نسبي 10% (percentage_discount)
INSERT INTO offers (company_user_id, scope, status, title, reward_type, reward_value, start_at, end_at, created_at, updated_at)
VALUES 
    (@company_id, 'public', 'active', 'خصم 10% على الأسبرين', 'percentage_discount', 10, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

SET @offer_percentage = LAST_INSERT_ID();

INSERT INTO offer_items (offer_id, product_id, min_qty, created_at, updated_at)
VALUES 
    (@offer_percentage, @product_1, 1000, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- عرض 2: خصم ثابت 100 ريال (discount_fixed)
INSERT INTO offers (company_user_id, scope, status, title, reward_type, reward_value, start_at, end_at, created_at, updated_at)
VALUES 
    (@company_id, 'public', 'active', 'خصم 100 ريال على الباراسيتامول', 'discount_fixed', 100, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

SET @offer_fixed = LAST_INSERT_ID();

INSERT INTO offer_items (offer_id, product_id, min_qty, created_at, updated_at)
VALUES 
    (@offer_fixed, @product_2, 500, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- عرض 3: بونص 20 قطعة (bonus_qty)
INSERT INTO offers (company_user_id, scope, status, title, reward_type, reward_value, start_at, end_at, created_at, updated_at)
VALUES 
    (@company_id, 'public', 'active', 'احصل على 20 قطعة مجانية', 'bonus_qty', 20, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

SET @offer_bonus = LAST_INSERT_ID();

INSERT INTO offer_items (offer_id, product_id, min_qty, bonus_product_id, created_at, updated_at)
VALUES 
    (@offer_bonus, @product_1, 1000, @product_1, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- عرض 4: عرض خاص (private) لعميل محدد - خصم 15%
INSERT INTO offers (company_user_id, scope, status, title, reward_type, reward_value, start_at, end_at, created_at, updated_at)
VALUES 
    (@company_id, 'private', 'active', 'عرض خاص - خصم 15%', 'percentage_discount', 15, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

SET @offer_private = LAST_INSERT_ID();

INSERT INTO offer_items (offer_id, product_id, min_qty, created_at, updated_at)
VALUES 
    (@offer_private, @product_3, 100, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- ربط العرض الخاص بالعميل
INSERT INTO offer_targets (offer_id, target_type, target_id, created_at, updated_at)
VALUES 
    (@offer_private, 'customer', @customer_id, NOW(), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- 4. عرض ملخص البيانات المنشأة
SELECT '=== Companies ===' as '';
SELECT id, name, email, user_type FROM users WHERE user_type = 'company';

SELECT '=== Customers ===' as '';
SELECT id, name, email, user_type FROM users WHERE user_type = 'customer';

SELECT '=== Products ===' as '';
SELECT id, name, base_price, is_active FROM products WHERE company_user_id = @company_id;

SELECT '=== Offers ===' as '';
SELECT id, title, scope, status, reward_type, reward_value FROM offers WHERE company_user_id = @company_id;

SELECT '=== Offer Items ===' as '';
SELECT oi.id, o.title as offer_title, p.name as product_name, oi.min_qty, oi.bonus_product_id
FROM offer_items oi
JOIN offers o ON oi.offer_id = o.id
JOIN products p ON oi.product_id = p.id
WHERE o.company_user_id = @company_id;

SELECT '=== Offer Targets (Private Offers) ===' as '';
SELECT ot.id, o.title as offer_title, u.name as customer_name, ot.target_type
FROM offer_targets ot
JOIN offers o ON ot.offer_id = o.id
JOIN users u ON ot.target_id = u.id
WHERE o.company_user_id = @company_id;

-- 5. طباعة IDs للاستخدام في Postman
SELECT '=== IDs for Postman Environment ===' as '';
SELECT 
    @company_id as company_id,
    @customer_id as customer_id,
    @product_1 as product_id_1,
    @product_2 as product_id_2,
    @product_3 as product_id_3,
    @offer_percentage as offer_id_percentage,
    @offer_fixed as offer_id_fixed,
    @offer_bonus as offer_id_bonus,
    @offer_private as offer_id_private;

-- ============================================
-- ملاحظات:
-- ============================================
-- 1. Password للمستخدمين: "password"
-- 2. تأكد من تحديث IDs في Postman Environment
-- 3. العروض العامة (public) تنطبق على جميع العملاء
-- 4. العرض الخاص (private) ينطبق فقط على العميل المحدد
-- ============================================
