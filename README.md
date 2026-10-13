# Invory

Costing and production-capacity tool for fashion products (clothing, shoes, bags, accessories).

- Keep an inventory of **materials** with stock and cost per unit.
- Define **products** and the materials that compose them (bill of materials, with waste %).
- See each product's **unit cost**, **how many units you can produce** from current stock, and the **bottleneck material**.

**Stack:** Laravel 13 · Inertia 3 · React 19 · TypeScript · Tailwind 4 · shadcn/ui · Pest · Vitest

| | |
|---|---|
| Full setup (environments, tooling) | [docs/SETUP.md](docs/SETUP.md) |
| Architecture | [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) |
| Decisions | [docs/adr/](docs/adr/) |
| MVP backlog | [docs/BACKLOG.md](docs/BACKLOG.md) |
| AI team and workflow | [docs/TEAM.md](docs/TEAM.md) |

---

## Fresh installation

These steps take you from a bare clone to a running app with demo data. The detailed environment setup (Laragon, Docker, OPcache, Apache virtual host) is in [docs/SETUP.md](docs/SETUP.md).

### Prerequisites

You need the following installed and reachable from your terminal before starting:

| Tool | Minimum version | Check |
|---|---|---|
| PHP | 8.3 | `php -v` |
| Composer | 2 | `composer -V` |
| Node | 20 | `node -v` |
| Docker Desktop | any recent | `docker version` |

PHP must have these extensions enabled: `pdo_pgsql`, `pgsql`, `pdo_sqlite`, `sqlite3`, `intl`, `mbstring`, `zip`.  
Check: `php -m | grep -E "pdo_pgsql|intl|zip"`

---

### Step 1 — Clone and install dependencies

```bash
git clone <repo-url> invory
cd invory
composer install
npm install
```

---

### Step 2 — Create your `.env` file

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set at minimum:

```dotenv
APP_NAME=Invory
APP_URL=http://localhost:8000
APP_CURRENCY=TND

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

> The default credentials match the Docker Compose PostgreSQL service. Change them if your database uses different values.

---

### Step 3 — Start the database

```bash
docker compose up -d
```

This starts PostgreSQL (port 5432) and Mailpit (port 8026). Wait a few seconds for PostgreSQL to be ready, then verify:

```bash
docker compose ps
```

Both `invory-pgsql-1` and `invory-mailpit-1` should show **running**.

---

### Step 4 — Run migrations

```bash
php artisan migrate
```

The migrations create all tables **and automatically seed the reference data** (units, material categories, product categories). You do not need to run a separate command for these — they are part of the schema.

**What the migrations insert automatically:**

| Table | Records |
|---|---|
| `units` | 13 units of measure (see below) |
| `material_categories` | 12 material categories (see below) |
| `product_categories` | 6 product categories (see below) |

<details>
<summary>Units (13)</summary>

| Code | Name | Dimension |
|---|---|---|
| `m` | Metre | length |
| `cm` | Centimetre | length |
| `yd` | Yard | length |
| `sqm` | Square metre | area |
| `sqft` | Square foot | area |
| `kg` | Kilogram | mass |
| `g` | Gram | mass |
| `l` | Litre | volume |
| `ml` | Millilitre | volume |
| `pc` | Piece | count |
| `pair` | Pair | count |
| `roll` | Roll | package |
| `cone` | Cone | package |

</details>

<details>
<summary>Material categories (12)</summary>

Fabric · Lining · Interlining · Leather · Thread · Trim · Fastener · Label · Sole · Hardware · Packaging · Other

</details>

<details>
<summary>Product categories (6)</summary>

Apparel · Footwear · Bag · Accessory · Jewelry · Other

</details>

---

### Step 5 — Seed demo data (optional but recommended)

```bash
php artisan db:seed
```

This populates the app with realistic sample data so every screen has something to show:

| What | Count | Details |
|---|---|---|
| User account | 1 | email `test@example.com`, password `password` |
| Materials | 12 | fabrics, leather, trims, hardware, thread, labels, sole |
| Products | 7 | apparel, footwear, bags, accessories; mix of active and draft |
| Sales | 60 | spread across the demo products |

**Demo materials** include four restock alerts (3 low stock, 1 out of stock) so the dashboard cards are populated on first load.

**Demo products** include a worked costing example: *Denim Jacket* costs **35.75 TND/unit** and is limited by the metal shank button (900 in stock → 112 units producible).

> The seeder is idempotent — running it again resets the 12 demo materials to their original values and skips any product that already exists (including soft-deleted ones).

---

### Step 6 — Build the frontend and start the dev server

```bash
npm run build      # one-time build, or
npm run dev        # live HMR during development (keeps running in the terminal)
```

---

### Step 7 — Serve the app

**Option A — Laravel's built-in server (quickest, no extra config):**

```bash
php artisan serve --port=8000
```

Then open http://localhost:8000.

**Option B — Laragon + Apache (recommended for this project, faster):**  
See [docs/SETUP.md §4](docs/SETUP.md) for the one-time virtual-host setup.

---

### Log in

Go to http://localhost:8000 and sign in with:

- **Email:** `test@example.com`
- **Password:** `password`

---

### Running the test suite

```bash
composer test      # Pint (style check) + Larastan + Pest on SQLite in-memory
npx vp test        # Vitest (React component tests)
npm run check      # Oxfmt + Oxlint
npm run types:check
npm run build
```

All five gates must be green before committing.
