# Extendable Order & Payment Management API

A Laravel REST API for managing orders and payments, built around the Strategy pattern so new
payment gateways can be added without touching existing code. Authentication is handled with
JWT, all input is validated through Form Requests, and business logic lives in DTOs + Services
rather than in the controllers.

## Tech Stack

- Laravel 13 / PHP 8.4
- MySQL
- `php-open-source-saver/jwt-auth` for JWT authentication
- PHPUnit for testing
- Laravel Pint (PSR-12)

## Architecture

```
app/
├── DTOs/                      Immutable data carriers between requests and services
├── Enums/                     OrderStatus, PaymentStatus, PaymentMethod
├── Exceptions/                Domain exceptions (mapped to HTTP responses in bootstrap/app.php)
├── Http/
│   ├── Controllers/Api/       Thin controllers: validate → delegate to a Service → respond
│   ├── Requests/               Form Requests (validation + error messages)
│   └── Resources/              API Resources (response shaping)
├── Models/                    Order, OrderItem, Payment, User
└── Services/
    ├── AuthService.php
    ├── OrderService.php
    ├── PaymentService.php
    └── PaymentGateways/       Strategy pattern implementation (see below)
```

Controllers never contain business logic — they translate HTTP input into DTOs, call a Service,
and shape the response with an API Resource. Services own transactions and business rules.
Models stay thin (relationships + casts only).

## Payment Gateway Extensibility (Strategy Pattern)

Each payment method is a **strategy**: a class implementing `PaymentGatewayInterface`:

```php
interface PaymentGatewayInterface
{
    public function charge(Payment $payment): PaymentGatewayResult;
}
```

`App\Services\PaymentGateways\PaymentGatewayManager` is the context/factory that resolves the
correct strategy at runtime, based on a config-driven map in `config/payment_gateways.php`:

```php
'gateways' => [
    'credit_card' => [
        'class'  => CreditCardGateway::class,
        'key'    => env('CREDIT_CARD_GATEWAY_KEY'),
        'secret' => env('CREDIT_CARD_GATEWAY_SECRET'),
    ],
    'paypal' => [
        'class'  => PaypalGateway::class,
        'key'    => env('PAYPAL_GATEWAY_KEY'),
        'secret' => env('PAYPAL_GATEWAY_SECRET'),
    ],
],
```

`PaymentService::process()` never knows which gateway it's talking to — it asks the manager to
`resolve()` the strategy for the requested `PaymentMethod` and calls `charge()` on it.

### Adding a new gateway (e.g. Stripe)

1. Add a case to `App\Enums\PaymentMethod`:
   ```php
   case Stripe = 'stripe';
   ```
2. Create `App\Services\PaymentGateways\StripeGateway implements PaymentGatewayInterface`
   with a `charge(Payment $payment): PaymentGatewayResult` method.
3. Register it in `config/payment_gateways.php`:
   ```php
   'stripe' => [
       'class' => StripeGateway::class,
       'key' => env('STRIPE_GATEWAY_KEY'),
       'secret' => env('STRIPE_GATEWAY_SECRET'),
   ],
   ```
4. Add the credentials to `.env`.

No existing class is modified — `PaymentService`, `PaymentGatewayManager`, and every other
gateway are untouched. This satisfies the "minimal changes" extensibility requirement.

## Business Rules Enforced

- Payments can only be processed for orders in `confirmed` status (`OrderNotConfirmedException` → `409`).
- Orders cannot be deleted while they have associated payments (`OrderHasPaymentsException` → `409`).
- Order `total` is always server-calculated from its items — never trusted from client input.

## Setup Instructions

### Requirements

- PHP 8.2+
- Composer
- MySQL 8+

### Steps

```bash
composer install

cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Set DB credentials in .env (defaults to a local MySQL instance named order_payment_api)
php artisan migrate --seed

php artisan serve
```

The seeder creates a demo user:

- email: `test@example.com`
- password: `password`

...with one confirmed order and two order items, ready for payment testing.

### Running Tests

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so no database
setup is required to run the suite:

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

## API Overview

All endpoints are prefixed with `/api/v1`. Full request/response examples (success and error
cases) are in [`docs/Order_Payment_API.postman_collection.json`](docs/Order_Payment_API.postman_collection.json) —
import it into Postman.

### Authentication

| Method | Endpoint             | Description                   | Auth |
|--------|-----------------------|--------------------------------|------|
| POST   | `/auth/register`      | Register a new user            | No   |
| POST   | `/auth/login`         | Log in and receive a JWT       | No   |
| POST   | `/auth/logout`        | Invalidate the current token   | Yes  |
| POST   | `/auth/refresh`       | Refresh the current token      | Yes  |
| GET    | `/auth/me`             | Get the authenticated user     | Yes  |

### Orders

| Method | Endpoint             | Description                              | Auth |
|--------|-----------------------|--------------------------------------------|------|
| GET    | `/orders`              | List orders (paginated, `?status=` filter) | Yes  |
| POST   | `/orders`              | Create an order                            | Yes  |
| GET    | `/orders/{order}`      | View a single order                        | Yes  |
| PUT    | `/orders/{order}`      | Update an order (details, items, status)   | Yes  |
| DELETE | `/orders/{order}`      | Delete an order (fails if it has payments) | Yes  |

### Payments

| Method | Endpoint                        | Description                                 | Auth |
|--------|----------------------------------|-----------------------------------------------|------|
| GET    | `/payments`                      | List all payments (paginated)                 | Yes  |
| GET    | `/orders/{order}/payments`       | List payments for a specific order            | Yes  |
| POST   | `/orders/{order}/payments`       | Process a payment (order must be `confirmed`) | Yes  |

All authenticated endpoints expect `Authorization: Bearer <token>`.

## Assumptions & Notes

- An order belongs to the authenticated user who created it (`user_id`); listing/showing is not
  scoped to "my orders only" since the task describes a single-tenant admin-style API, but the
  relationship is in place if per-user scoping is needed later.
- Payment gateway `charge()` calls are simulated (no real external HTTP calls) per the task's
  "simulate payment processing" requirement — each gateway returns a successful result with a
  generated transaction reference. Swapping in a real HTTP client only touches that one gateway
  class.
- Order `status` transitions are not state-machine-restricted beyond the payment business rule;
  any status can be set via `PUT /orders/{order}` with a `status` field.
- Validation errors return `422` with a `message` + `errors` map; domain rule violations
  (payment on non-confirmed order, delete with payments) return `409`; authentication failures
  return `401`.
