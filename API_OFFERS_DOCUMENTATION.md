# Offers API Documentation

## Overview
This document describes the Offers API endpoints. The API provides two types of endpoints:
1. **Public Endpoints**: Available to all users without authentication (view public offers)
2. **Company Endpoints**: Require authentication and company user type (full CRUD operations)

---

## Public Endpoints (No Authentication Required)

### 1. Get Public Offers List
**Endpoint:** `GET /api/offers/public`

**Description:** Returns a paginated list of active public offers.

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 10)

**Filters Applied Automatically:**
- Only `scope = 'public'`
- Only `status = 'active'`
- Only offers that are currently valid (start_at <= now, end_at >= now)

**Response Example:**
```json
{
  "success": true,
  "message": "تم جلب قائمة العروض العامة بنجاح",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "عرض الصيف الكبير",
        "scope": "public",
        "status": "active",
        "start_at": "2026-02-01 00:00:00",
        "end_at": "2026-03-31 23:59:59",
        "items_count": 3,
        "targets_count": 0,
        "company": {
          "id": 5,
          "name": "أحمد محمد",
          "company_name": "شركة التجارة الحديثة"
        },
        "created_at": "2026-02-14 10:00:00",
        "updated_at": "2026-02-14 10:00:00"
      }
    ],
    "per_page": 10,
    "total": 25
  }
}
```

---

### 2. Get Public Offer Details
**Endpoint:** `GET /api/offers/public/{id}`

**Description:** Returns detailed information about a specific public active offer.

**Response Example:**
```json
{
  "success": true,
  "message": "تم جلب بيانات العرض بنجاح",
  "data": {
    "id": 1,
    "company_user_id": 5,
    "title": "عرض الصيف الكبير",
    "description": "خصومات تصل إلى 50% على جميع المنتجات",
    "scope": "public",
    "status": "active",
    "start_at": "2026-02-01 00:00:00",
    "end_at": "2026-03-31 23:59:59",
    "items_count": 3,
    "targets_count": 0,
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "min_qty": 5,
        "reward_type": "discount_percent",
        "discount_percent": 20.00,
        "discount_fixed": null,
        "bonus_product_id": null,
        "bonus_qty": null,
        "product": {
          "id": 10,
          "name": "منتج أ",
          "sku": "PROD-001",
          "base_price": 100.00,
          "main_image": "/storage/products/image.jpg",
          "is_active": true
        },
        "bonus_product": null
      }
    ],
    "targets": [],
    "company": {
      "id": 5,
      "name": "أحمد محمد",
      "company_name": "شركة التجارة الحديثة"
    },
    "created_at": "2026-02-14 10:00:00",
    "updated_at": "2026-02-14 10:00:00"
  }
}
```

---

## Company Endpoints (Authentication Required)

**Authentication:** All company endpoints require:
- `Authorization: Bearer {token}` header
- User must be authenticated via Sanctum
- User must have `user_type = 'company'`

---

### 3. Get Company Offers List
**Endpoint:** `GET /api/offers`

**Description:** Returns a paginated list of offers belonging to the authenticated company.

**Headers:**
```
Authorization: Bearer {your_token}
```

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 10)
- `search` (optional): Search in title and description
- `scope` (optional): Filter by scope (public/private)
- `status` (optional): Filter by status (draft/active/paused/expired)

**Response:** Same structure as public offers list, but includes all offers (public/private, all statuses) belonging to the company.

---

### 4. Create New Offer
**Endpoint:** `POST /api/offers`

**Description:** Creates a new offer for the authenticated company.

**Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "عرض الصيف الكبير",
  "description": "خصومات تصل إلى 50%",
  "scope": "public",
  "status": "draft",
  "start_at": "2026-02-01",
  "end_at": "2026-03-31",
  "items": [
    {
      "product_id": 10,
      "min_qty": 5,
      "reward_type": "discount_percent",
      "discount_percent": 20.00
    },
    {
      "product_id": 11,
      "min_qty": 10,
      "reward_type": "bonus_qty",
      "bonus_qty": 2,
      "bonus_product_id": 12
    }
  ],
  "targets": []
}
```

**Validation Rules:**
- `title`: required, string, max 255
- `description`: optional, string
- `scope`: optional, in: public, private (default: public)
- `status`: optional, in: draft, active, paused (default: draft)
- `start_at`: optional, date
- `end_at`: optional, date, must be after or equal to start_at
- `items`: required, array, min 1 item
- `items.*.product_id`: required, must exist in products table
- `items.*.min_qty`: optional, integer, min 1 (default: 1)
- `items.*.reward_type`: required, in: discount_percent, discount_fixed, bonus_qty
- `items.*.discount_percent`: required if reward_type = discount_percent, numeric, 0.01-100
- `items.*.discount_fixed`: required if reward_type = discount_fixed, numeric, min 0.01
- `items.*.bonus_qty`: required if reward_type = bonus_qty, integer, min 1
- `items.*.bonus_product_id`: required if reward_type = bonus_qty, must exist in products
- `targets`: optional, array (required if scope = private)
- `targets.*.target_type`: required, in: customer, customer_category, customer_tag
- `targets.*.target_id`: required, integer

**Response:**
```json
{
  "success": true,
  "message": "تم إنشاء العرض بنجاح",
  "data": {
    "id": 1,
    "title": "عرض الصيف الكبير",
    ...
  }
}
```

---

### 5. Get Offer Details
**Endpoint:** `GET /api/offers/{id}`

**Description:** Returns detailed information about a specific offer belonging to the authenticated company.

**Headers:**
```
Authorization: Bearer {your_token}
```

**Response:** Same structure as public offer details.

---

### 6. Update Offer
**Endpoint:** `PUT /api/offers/{id}` or `PATCH /api/offers/{id}`

**Description:** Updates an existing offer. Only the company that owns the offer can update it.

**Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Request Body:** (all fields are optional)
```json
{
  "title": "عرض الصيف المحدث",
  "description": "خصومات جديدة",
  "scope": "private",
  "status": "active",
  "start_at": "2026-02-15",
  "end_at": "2026-04-15",
  "items": [
    {
      "product_id": 10,
      "min_qty": 3,
      "reward_type": "discount_fixed",
      "discount_fixed": 50.00
    }
  ],
  "targets": [
    {
      "target_type": "customer_category",
      "target_id": 5
    }
  ]
}
```

**Notes:**
- If `items` is provided, it will replace all existing items
- If `targets` is provided, it will replace all existing targets
- If `scope` is changed to `private`, `targets` must be provided in the same request

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث العرض بنجاح",
  "data": {
    "id": 1,
    "title": "عرض الصيف المحدث",
    ...
  }
}
```

---

### 7. Delete Offer
**Endpoint:** `DELETE /api/offers/{id}`

**Description:** Deletes an offer. Only the company that owns the offer can delete it.

**Headers:**
```
Authorization: Bearer {your_token}
```

**Response:**
```json
{
  "success": true,
  "message": "تم حذف العرض بنجاح",
  "data": null
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "العرض المطلوب غير موجود"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "العرض المطلوب غير موجود"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "خطأ في البيانات المدخلة",
  "errors": {
    "title": ["عنوان العرض مطلوب."],
    "items": ["يجب إضافة عنصر واحد على الأقل للعرض."]
  }
}
```

---

## Reward Types Explained

### 1. discount_percent
Percentage discount on the product when minimum quantity is reached.
```json
{
  "reward_type": "discount_percent",
  "discount_percent": 20.00
}
```
Example: Buy 5 items, get 20% off

### 2. discount_fixed
Fixed amount discount on the product when minimum quantity is reached.
```json
{
  "reward_type": "discount_fixed",
  "discount_fixed": 50.00
}
```
Example: Buy 10 items, get 50 SAR off

### 3. bonus_qty
Get bonus quantity of a product (same or different) when minimum quantity is reached.
```json
{
  "reward_type": "bonus_qty",
  "bonus_qty": 2,
  "bonus_product_id": 12
}
```
Example: Buy 10 of product A, get 2 free of product B

---

## Target Types Explained

### 1. customer
Target specific customer by user ID
```json
{
  "target_type": "customer",
  "target_id": 123
}
```

### 2. customer_category
Target all customers in a specific category
```json
{
  "target_type": "customer_category",
  "target_id": 5
}
```

### 3. customer_tag
Target all customers with a specific tag
```json
{
  "target_type": "customer_tag",
  "target_id": 8
}
```

---

## Testing with Postman/Insomnia

### 1. Login to get token
```
POST /api/login
{
  "email": "company@example.com",
  "password": "password"
}
```

### 2. Use token in subsequent requests
```
Authorization: Bearer {token_from_login}
```

### 3. Test public endpoints (no token needed)
```
GET /api/offers/public
GET /api/offers/public/1
```

### 4. Test company endpoints (token required)
```
GET /api/offers
POST /api/offers
GET /api/offers/1
PUT /api/offers/1
DELETE /api/offers/1
```

---

## Notes

1. **Authorization**: The API uses Laravel Policies to ensure:
   - Only companies can create/update/delete offers
   - Companies can only manage their own offers
   - Public endpoints show only active public offers

2. **Soft Delete**: Offers use soft delete, so deleted offers are not permanently removed from the database.

3. **Items and Targets**: When updating an offer, if you provide `items` or `targets`, they will completely replace the existing ones (not merge).

4. **Date Format**: Dates should be in `YYYY-MM-DD` format for input, and will be returned in `YYYY-MM-DD HH:MM:SS` format.

5. **Pagination**: All list endpoints support pagination with `per_page` parameter.

6. **Filtering**: Company offers list supports filtering by `scope`, `status`, and text search in `title` and `description`.
