# ShopSphere – Database Schema Design

## 1. Users

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | UNIQUE, NOT NULL |
| password | VARCHAR(255) | NOT NULL |
| email_verified_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 2. Addresses

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| user_id | BIGINT | FK → users.id |
| first_name | VARCHAR(100) | NOT NULL |
| last_name | VARCHAR(100) | NOT NULL |
| phone | VARCHAR(30) | NOT NULL |
| country | VARCHAR(100) | NOT NULL |
| city | VARCHAR(100) | NOT NULL |
| street | VARCHAR(255) | NOT NULL |
| postal_code | VARCHAR(30) | NULL |
| is_default | BOOLEAN | DEFAULT FALSE |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 3. Categories

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| parent_id | BIGINT | Nullable FK → categories.id |
| name | VARCHAR(255) | NOT NULL |
| slug | VARCHAR(255) | UNIQUE |
| description | TEXT | NULL |
| is_active | BOOLEAN | DEFAULT TRUE |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 4. Products

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| category_id | BIGINT | FK → categories.id |
| name | VARCHAR(255) | NOT NULL |
| slug | VARCHAR(255) | UNIQUE |
| description | TEXT | NULL |
| status | VARCHAR(30) | DEFAULT 'draft' |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |
| deleted_at | TIMESTAMP | NULL |

## 5. Product Variants

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| product_id | BIGINT | FK → products.id |
| sku | VARCHAR(100) | UNIQUE |
| price | DECIMAL(12,2) | NOT NULL |
| stock_quantity | INTEGER | DEFAULT 0 |
| attributes | JSON | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 6. Product Images

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| product_id | BIGINT | FK → products.id |
| image_path | VARCHAR(500) | NOT NULL |
| is_primary | BOOLEAN | DEFAULT FALSE |
| sort_order | INTEGER | DEFAULT 0 |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 7. Carts

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| user_id | BIGINT | UNIQUE FK → users.id |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 8. Cart Items

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| cart_id | BIGINT | FK → carts.id |
| product_variant_id | BIGINT | FK → product_variants.id |
| quantity | INTEGER | NOT NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

Unique Constraint: (cart_id, product_variant_id)

## 9. Orders

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| user_id | BIGINT | FK → users.id |
| coupon_id | BIGINT | Nullable FK → coupons.id |
| order_number | VARCHAR(50) | UNIQUE |
| subtotal | DECIMAL(12,2) | NOT NULL |
| discount | DECIMAL(12,2) | DEFAULT 0 |
| shipping_cost | DECIMAL(12,2) | DEFAULT 0 |
| tax | DECIMAL(12,2) | DEFAULT 0 |
| total | DECIMAL(12,2) | NOT NULL |
| status | VARCHAR(30) | NOT NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 10. Order Items

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| order_id | BIGINT | FK → orders.id |
| product_variant_id | BIGINT | Nullable FK → product_variants.id |
| product_name | VARCHAR(255) | NOT NULL |
| sku | VARCHAR(100) | NOT NULL |
| unit_price | DECIMAL(12,2) | NOT NULL |
| quantity | INTEGER | NOT NULL |
| total | DECIMAL(12,2) | NOT NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 11. Payments

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| order_id | BIGINT | FK → orders.id |
| amount | DECIMAL(12,2) | NOT NULL |
| payment_method | VARCHAR(50) | NOT NULL |
| status | VARCHAR(30) | NOT NULL |
| transaction_id | VARCHAR(255) | NULL |
| paid_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 12. Shipments

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| order_id | BIGINT | UNIQUE FK → orders.id |
| carrier | VARCHAR(100) | NULL |
| tracking_number | VARCHAR(255) | NULL |
| status | VARCHAR(30) | NOT NULL |
| shipped_at | TIMESTAMP | NULL |
| delivered_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 13. Coupons

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| code | VARCHAR(100) | UNIQUE |
| discount_type | VARCHAR(30) | NOT NULL |
| discount_value | DECIMAL(12,2) | NOT NULL |
| minimum_order_amount | DECIMAL(12,2) | DEFAULT 0 |
| usage_limit | INTEGER | NULL |
| used_count | INTEGER | DEFAULT 0 |
| starts_at | TIMESTAMP | NULL |
| expires_at | TIMESTAMP | NULL |
| is_active | BOOLEAN | DEFAULT TRUE |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 14. Reviews

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| user_id | BIGINT | FK → users.id |
| product_id | BIGINT | FK → products.id |
| rating | TINYINT | NOT NULL |
| comment | TEXT | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

Unique Constraint: (user_id, product_id)

## 15. Wishlists

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| user_id | BIGINT | FK → users.id |
| name | VARCHAR(255) | NOT NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## 16. Wishlist Items

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| wishlist_id | BIGINT | FK → wishlists.id |
| product_id | BIGINT | FK → products.id |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

Unique Constraint: (wishlist_id, product_id)

## 17. Inventory Movements

| Column | Type | Constraints |
|---|---|---|
| id | BIGINT | Primary Key |
| product_variant_id | BIGINT | FK → product_variants.id |
| quantity | INTEGER | NOT NULL |
| movement_type | VARCHAR(30) | NOT NULL |
| reference | VARCHAR(255) | NULL |
| created_at | TIMESTAMP | NULL |
| updated_at | TIMESTAMP | NULL |

## Database Standards

- Primary keys use BIGINT.
- Foreign keys use BIGINT.
- Monetary values use DECIMAL(12,2).
- Timestamps use TIMESTAMP.
- Boolean fields use BOOLEAN.
- Flexible attributes use JSON.
- Tables use Laravel timestamps.
- Soft deletes are used where historical recovery is required.
