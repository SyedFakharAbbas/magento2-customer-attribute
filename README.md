# EliteRemoteFirm CustomerAttribute

Magento 2 module that adds a unique, system-managed `uuid` attribute to customers.

## Features

- Creates a customer EAV attribute `uuid` (RFC 4122 v4 UUID)
- Enforces uniqueness at the EAV layer (`unique = true`) with collision checks during generation
- Automatically assigns UUIDs to existing customers on module installation
- Automatically assigns UUIDs to newly created customers
- Exposes `uuid` on the GraphQL `Customer` type for authenticated queries
- Displays `uuid` in the admin customer grid
- Keeps the attribute read-only in admin (not included in any customer edit forms; save attempts are ignored)

## Assumptions

- UUIDs are version 4 (random) strings generated via `ramsey/uuid`
- The attribute is global-scoped and not editable by admins or customers through any save path covered by `CustomerRepositoryInterface`
- GraphQL access follows Magento's standard customer authentication (`customer` query requires a valid bearer token)
- The module is intended for Magento Open Source / Adobe Commerce 2.4.x (tested against 2.4.9)
- The UUID field is shown on the admin customer form as a **disabled** field (read-only) for visibility; the authoritative display for admins is the customer grid column

## Requirements

- PHP 8.3+
- Magento 2.4.x
- `magento/module-customer`
- `magento/module-customer-graph-ql`

## Installation

### Via Composer (recommended)

Add the package repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "app/code/EliteRemoteFirm/CustomerAttribute",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "elite-remote-firm/magento2-customer-attribute": "1.0.0"
    }
}
```

Then run:

```bash
composer require elite-remote-firm/magento2-customer-attribute:1.0.0
bin/magento module:enable EliteRemoteFirm_CustomerAttribute
bin/magento setup:upgrade
bin/magento cache:flush
bin/magento indexer:reindex customer_grid
```

### Manual installation

Copy the module to `app/code/EliteRemoteFirm/CustomerAttribute`, then run:

```bash
bin/magento module:enable EliteRemoteFirm_CustomerAttribute
bin/magento setup:upgrade
bin/magento cache:flush
bin/magento indexer:reindex customer_grid
```

## GraphQL API

Authenticate as a customer, then query the `uuid` field:

```graphql
mutation {
  generateCustomerToken(email: "customer@example.com", password: "Password123!") {
    token
  }
}
```

```graphql
{
  customer {
    firstname
    lastname
    email
    uuid
  }
}
```

Pass the token in the `Authorization: Bearer <token>` header.

The `uuid` field is also available on `Customer` objects returned by mutations such as `createCustomerV2` and `updateCustomerV2`.

## Admin

After installation and reindexing, the **UUID** column appears in **Customers > All Customers**. The value is visible in the grid but cannot be edited on the customer form because the attribute is excluded from all admin forms.

## Testing

### Unit tests

```bash
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/EliteRemoteFirm/CustomerAttribute/Test/Unit
```

### Integration tests

Ensure your integration test DB is configured (`dev/tests/integration/etc/install-config-mysql.php`), then run:

```bash
./vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist \
  app/code/EliteRemoteFirm/CustomerAttribute/Test/Integration
```

## Module structure

```
EliteRemoteFirm/CustomerAttribute/
├── Setup/Patch/Data/
│   ├── AddUuidCustomerAttribute.php      # Creates the EAV attribute
│   └── PopulateExistingCustomerUuids.php   # Backfills existing customers
├── Model/
│   ├── UuidGenerator.php                   # UUID generation + uniqueness check
│   └── Resolver/CustomerUuid.php           # GraphQL resolver
├── Plugin/CustomerRepository/UuidPlugin.php  # Auto-assign + read-only enforcement
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── schema.graphqls
└── view/adminhtml/ui_component/customer_listing.xml
```

## Uninstall

```bash
bin/magento module:disable EliteRemoteFirm_CustomerAttribute
bin/magento setup:upgrade
```

To fully remove the attribute, a custom uninstall routine would be required. This module does not ship an uninstall script.
