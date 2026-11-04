# ShopSphere – Entity Relationship Diagram

## Overview

This document describes the database relationships for the ShopSphere e-commerce platform.

## ERD

erDiagram

    USERS ||--o{ ADDRESSES : has
    USERS ||--o| CARTS : owns
    USERS ||--o{ ORDERS : places
    USERS ||--o{ REVIEWS : writes
    USERS ||--o{ WISHLISTS : owns

    CATEGORIES ||--o{ PRODUCTS : contains
    CATEGORIES ||--o| CATEGORIES : parent_of

    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    PRODUCTS ||--o{ PRODUCT_IMAGES : has
    PRODUCTS ||--o{ REVIEWS : receives

    PRODUCT_VARIANTS ||--o{ CART_ITEMS : added_to
    PRODUCT_VARIANTS ||--o{ ORDER_ITEMS : purchased_as
    PRODUCT_VARIANTS ||--o{ INVENTORY_MOVEMENTS : tracks

    CARTS ||--o{ CART_ITEMS : contains

    ORDERS ||--o{ ORDER_ITEMS : contains
    ORDERS ||--o{ PAYMENTS : has
    ORDERS ||--o| SHIPMENTS : has

    COUPONS ||--o{ ORDERS : applied_to

    WISHLISTS ||--o{ WISHLIST_ITEMS : contains
    PRODUCTS ||--o{ WISHLIST_ITEMS : saved_in

    USERS {
        bigint id PK
        string name
        string email UK
        string password
    }

    ADDRESSES {
        bigint id PK
        bigint user_id FK
        string city
        string street
        string postal_code
    }

    CATEGORIES {
        bigint id PK
        bigint parent_id FK
        string name
        string slug UK
    }

    PRODUCTS {
        bigint id PK
        bigint category_id FK
        string name
        string slug UK
        text description
        string status
    }

    PRODUCT_VARIANTS {
        bigint id PK
        bigint product_id FK
        string sku UK
        decimal price
        decimal stock
    }

    PRODUCT_IMAGES {
        bigint id PK
        bigint product_id FK
        string image_path
        boolean is_primary
    }

    CARTS {
        bigint id PK
        bigint user_id FK
    }

    CART_ITEMS {
        bigint id PK
        bigint cart_id FK
        bigint product_variant_id FK
        integer quantity
    }

    ORDERS {
        bigint id PK
        bigint user_id FK
        bigint coupon_id FK
        decimal subtotal
        decimal discount
        decimal total
        string status
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint product_variant_id FK
        string product_name
        string sku
        decimal unit_price
        integer quantity
        decimal total
    }

    PAYMENTS {
        bigint id PK
        bigint order_id FK
        decimal amount
        string payment_method
        string status
        string transaction_id
    }

    SHIPMENTS {
        bigint id PK
        bigint order_id FK
        string tracking_number
        string carrier
        string status
    }

    COUPONS {
        bigint id PK
        string code UK
        string discount_type
        decimal discount_value
        datetime expires_at
    }

    REVIEWS {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        integer rating
        text comment
    }

    WISHLISTS {
        bigint id PK
        bigint user_id FK
        string name
    }

    WISHLIST_ITEMS {
        bigint id PK
        bigint wishlist_id FK
        bigint product_id FK
    }

    INVENTORY_MOVEMENTS {
        bigint id PK
        bigint product_variant_id FK
        integer quantity
        string movement_type
        string reference
    }

## Important Notes

- Each product can have multiple variants.
- Each variant has its own SKU, price, and inventory.
- Orders contain multiple order items.
- Order items preserve product information at purchase time.
- Each user can have multiple addresses.
- Categories support hierarchical relationships.
- Inventory movements provide a history of stock changes.
