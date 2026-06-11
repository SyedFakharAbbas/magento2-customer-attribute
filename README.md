# EliteRemoteFirm CustomerAttribute

Magento 2 module that adds a unique, system-managed `uuid` attribute to customers — with GraphQL exposure, admin grid display, automatic assignment, and read-only enforcement.

**Composer package:** `elite-remote-firm/magento2-customer-attribute`  
**Module:** `EliteRemoteFirm_CustomerAttribute`

## Quick Install

```bash
composer require elite-remote-firm/magento2-customer-attribute:^1.0
bin/magento module:enable EliteRemoteFirm_CustomerAttribute
bin/magento setup:upgrade
bin/magento indexer:reindex customer_grid
bin/magento cache:flush
```

## Features

- RFC 4122 v4 UUID customer attribute (unique, read-only)
- Auto-assigned to existing customers on install
- Auto-assigned to new customers on creation
- GraphQL `Customer.uuid` field (authenticated)
- Admin customer grid column
- Unit and integration tests included

## Documentation

Full documentation covering installation, GraphQL API access, admin usage, and testing procedures:

**[DOCUMENTATION.md](DOCUMENTATION.md)**

## Requirements

- Magento 2.4.x
- PHP 8.3+
- `magento/module-customer-graph-ql`

## License

Proprietary
