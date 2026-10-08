# Store API

A Laravel 12 JSON API for a small online store. It provides a public catalogue, Sanctum token authentication, role-based catalogue administration, customer-owned addresses, and transactional checkout with historical price snapshots.

## Requirements

- PHP 8.2+
- Composer
- SQLite (simplest for local development) or MySQL
- Node.js/npm only if you intend to use the bundled Vite frontend tooling

## Development setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

For SQLite, create `database/database.sqlite` and set:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

For MySQL, set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

For real order emails, configure `MAIL_MAILER=smtp` plus `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS`. The default local `log` mailer writes messages to `storage/logs/laravel.log`.

Then initialize and run the application:

```bash
php artisan migrate --seed
php artisan serve
```

The API is available at `http://127.0.0.1:8000/api`. Run the test suite with:

```bash
php artisan test
```

The order redesign migration converts each complete legacy `order_details` record and its product row into a new order with a priced item snapshot. Unattached legacy cart rows have no owner and are discarded; back up important databases before migrating.

## Authentication and roles

Register with `POST /api/register`, then log in with `POST /api/login`. Login returns a Sanctum bearer token. Send it on protected requests:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Public registration always creates a `Customer`; supplied role IDs are ignored. The seeded roles are `Admin`, `Employee`, and `Customer`.

| Capability | Public | Customer | Employee | Admin |
|---|---:|---:|---:|---:|
| Read catalogue | Yes | Yes | Yes | Yes |
| Manage own addresses/orders | No | Yes | Yes | Yes |
| Create/update catalogue | No | No | Yes | Yes |
| Delete catalogue | No | No | No | Yes |
| Manage users/roles | No | No | No | Yes |
| Change order status | No | No | Yes | Yes |

Customers only see their own orders and addresses. Employees and admins can inspect all orders and addresses.

## Request conventions

JSON request and response fields use camelCase. Relevant write endpoints also accept their snake_case database equivalents for compatibility. PATCH only changes provided fields; omitted camelCase fields are not converted into null values.

Validation failures return HTTP `422`, unauthenticated requests return `401`, and forbidden requests return `403`.

## API endpoints

### Authentication

| Method | Endpoint | Access | Purpose |
|---|---|---|---|
| POST | `/api/register` | Public | Register a customer |
| POST | `/api/login` | Public | Create a bearer token |
| GET | `/api/user` | Authenticated | Current user; `?includeAddresses=true` is supported |
| POST | `/api/logout` | Authenticated | Revoke the current token |

Registration body:

```json
{
  "name": "Ada Lovelace",
  "email": "ada@example.com",
  "password": "a-secure-password",
  "password_confirmation": "a-secure-password"
}
```

### Catalogue

| Resource | Public reads | Employee/Admin writes | Admin delete |
|---|---|---|---|
| `/api/v1/products` | GET list/item | POST, PUT, PATCH | DELETE |
| `/api/v1/categories` | GET list/item | POST, PUT, PATCH | DELETE |
| `/api/v1/images` | GET list/item | POST, PUT, PATCH | DELETE |
| `/api/v1/images/bulk` | — | POST | — |

Product write fields are `categoryId`, `name`, `description`, `price`, `offerPrice`, and `date`. Prices must be non-negative. Catalogue list endpoints retain their documented filter query parameters and pagination metadata.

### Addresses

`/api/v1/addresses` supports GET, POST, PUT/PATCH, and DELETE. Customers are automatically assigned as the owner and cannot transfer an address to another user.

```json
{
  "fullName": "Ada Lovelace",
  "postalCode": "1010",
  "streetName": "1 Queen Street",
  "suburb": "Central",
  "city": "Auckland",
  "country": "New Zealand"
}
```

### Checkout and orders

`POST /api/v1/orders` is the checkout endpoint. `addressId` must belong to the authenticated customer. Products must be unique in the item list and quantities must be from 1 to 100.

```json
{
  "addressId": 12,
  "paymentMethod": "bank_transfer",
  "items": [
    { "productId": 4, "quantity": 2 },
    { "productId": 9, "quantity": 1 }
  ]
}
```

Checkout runs in one database transaction. The server loads current product prices, uses a positive lower offer price when available, and stores `productName`, `unitPrice`, `quantity`, and `lineTotal` in `order_items`. It calculates and stores the order `subtotal` and `total`; client-supplied monetary values are ignored.

`paymentMethod` is one of `bank_transfer`, `cash_on_delivery`, or `manual`. Checkout reserves stock and sends an order confirmation containing the stored items and totals.

| Method | Endpoint | Access |
|---|---|---|
| GET | `/api/v1/orders` | Owner sees own; staff see all |
| POST | `/api/v1/orders` | Authenticated checkout |
| GET | `/api/v1/orders/{id}` | Owner or staff |
| PATCH | `/api/v1/orders/{id}` | Employee/Admin; payment and fulfillment updates |
| DELETE | `/api/v1/orders/{id}` | Admin |

Fulfillment follows `unfulfilled` → `processing` → `shipped` → `delivered`; steps cannot be skipped or reversed. Staff may cancel an unpaid order before shipment using `{ "fulfillmentStatus": "cancelled" }`. That cancellation restores reserved stock exactly once. Processing, shipping, and delivery changes send customer update emails. Order items do not have independent mutation endpoints: their stored prices and totals are an immutable checkout snapshot.

### Administration

Admins can manage `/api/v1/users` and `/api/v1/roles`. A role still assigned to users cannot be deleted. Password hashes are never included in API resources.
