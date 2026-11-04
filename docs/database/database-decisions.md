# ShopSphere – Database Design Decisions

This document explains the main database architecture decisions and their technical justifications.

## 1. Product Variants

Each product can have multiple variants.

For example:

A T-Shirt can have:
- Small / Red
- Medium / Red
- Large / Red
- Small / Blue

Each variant has:
- Unique SKU.
- Independent price.
- Independent stock quantity.
- Flexible attributes stored as JSON.

Reason:

Different product variants may have different prices and inventory levels.

## 2. Order Items Snapshots

Order items store product information at purchase time.

Each order item stores:
- Product name.
- SKU.
- Unit price.
- Quantity.
- Total price.

Reason:

Product information and prices may change after an order is placed.

Historical orders must preserve the original purchase information.

## 3. Money Representation

All monetary values use DECIMAL(12,2).

Reason:

Floating-point numbers can introduce precision errors in financial calculations.

DECIMAL provides predictable monetary precision.

## 4. Inventory Management

Product inventory belongs to product variants.

Inventory movements track stock changes.

Examples:
- Stock received.
- Order placed.
- Order cancelled.
- Stock adjustment.
- Product returned.

Reason:

A movement history provides traceability and helps investigate inventory discrepancies.

## 5. Categories Hierarchy

Categories use a self-referencing parent_id.

Example:

Electronics
- Phones
- Laptops
- Accessories

Reason:

This allows unlimited category nesting without creating separate tables for each category level.

## 6. Database Relationships

Foreign keys enforce referential integrity.

Unique constraints prevent duplicate records where required.

Examples:
- Unique user email.
- Unique product slug.
- Unique product SKU.
- Unique order number.
- Unique cart item per variant.

Reason:

Database constraints protect data integrity even when application-level validation is bypassed.

## 7. Soft Deletes

Products support soft deletion.

Reason:

Products referenced by historical orders should not disappear permanently when removed from the catalog.

## 8. Payments

Payments are stored separately from orders.

One order can have multiple payment attempts.

Reason:

Payment attempts may fail, be retried, or require refunds without creating duplicate orders.

## 9. Shipments

Shipment information is stored separately from orders.

Reason:

Shipping operations have their own tracking information and lifecycle.

## 10. Cart Design

Each authenticated user has one active cart.

Cart items reference product variants rather than products directly.

Reason:

Customers purchase specific variants, not just generic products.

## 11. Product Images

Product images are stored in a separate table.

Reason:

A product can have multiple images, and each image can have its own display order and primary-image flag.

## 12. Reviews

A user can review a product only once.

A unique constraint is applied to (user_id, product_id).

Reason:

This prevents duplicate reviews from the same user for the same product.

## 13. Database Indexing

Indexes will be added to frequently queried columns.

Examples:
- products.category_id
- products.status
- product_variants.sku
- orders.user_id
- orders.status
- orders.created_at
- payments.order_id

Reason:

Indexes improve query performance for common filtering and lookup operations.

Indexes will be evaluated using actual query patterns and EXPLAIN.

## 14. Transactions

Database transactions will be used for critical business operations.

Examples:
- Creating an order.
- Deducting inventory.
- Recording payments.
- Applying coupons.

Reason:

Related operations must succeed or fail together to maintain data consistency.

## 15. Future Scalability

The schema is designed to support future features such as:
- Guest checkout.
- Multiple payment gateways.
- Multiple shipments per order.
- Product attributes and filtering.
- Advanced inventory management.
- Discount campaigns.
