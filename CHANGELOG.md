# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-07-27

### Added
- Initial release of SofizPay PHP SDK
- Account balance checking and trustline verification
- Payment history retrieval with pagination
- Transaction search by memo
- Comprehensive error handling with custom exceptions
- Service-based architecture (AccountService, PaymentService)
- Complete documentation and usage examples

### Features
- Send DZD payments with optional memos
- Check account DZD balances and authorization status
- Retrieve all asset balances for accounts
- Verify account existence on Stellar network
- Search transactions by memo text
- Built on reliable Soneso Stellar PHP SDK
- PSR-4 autoloading support
- MIT License

### Dependencies
- PHP 8.0 or higher
- Soneso Stellar PHP SDK ^1.8
- Guzzle HTTP ^7.0
- JSON and cURL extensions
