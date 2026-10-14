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
| Roadmap (phases) | [docs/ROADMAP.md](docs/ROADMAP.md) |
| Backlog | [docs/BACKLOG.md](docs/BACKLOG.md) |
| Project status | [docs/STATUS.md](docs/STATUS.md) |
| AI team and workflow | [docs/TEAM.md](docs/TEAM.md) |

---

## Fresh installation

These steps take you from a bare clone to a running app with demo data on Windows (they work the same on macOS and Linux).
The app runs natively on PHP's built-in server; Docker runs only **PostgreSQL** and **Mailpit**
([ADR-0012](docs/adr/0012-dev-docker-db-and-mail-only.md)). More detail: [docs/SETUP.md](docs/SETUP.md).

### Prerequisites

You need the following installed and reachable from your terminal before starting:

| Tool | Minimum version | Check |
|---|---|---|
| PHP | 8.3 | `php -v` |
| Composer | 2 | `composer -V` |
| Node | 20 | `node -v` |
| Docker Desktop | any recent | `docker version` |

PHP must have these extensions enabled: `pdo_pgsql`, `pgsql`, `pdo_sqlite`, `sqlite3`, `intl`, `mbstring`, `zip`, `sockets`.  
Check: `php -m | grep -E "pdo_pgsql|intl|zip|sockets"`

`sockets` is needed by every Pest run (not only the browser test): since S-08 the browser plugin opens a socket at the end of each run.

**Windows:** install PHP and Composer natively (any source: the php.net zip, Laragon's `bin\php` folder, Scoop…) and make sure `php` and
`composer` are on your PATH. Restart the terminal after changing the PATH.

> **Shortcut:** after Step 3 (Docker running), `composer setup` does the rest of the installation in one command: `composer install`,
> creates `.env` from `.env.example` (only if `.env` doesn't exist), `php artisan key:generate`, `php artisan migrate --force`,
> `php artisan storage:link`, `npm install` and `npm run build`. Then go to Step 6 (demo data) and Step 7 (run).

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

`.env.example` already points the app at the Docker services. These are the values it ships (keep them unless your ports differ):

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

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1026
FORWARD_MAILPIT_PORT=1026
FORWARD_MAILPIT_DASHBOARD_PORT=8026
```

> The database credentials match the PostgreSQL service in `compose.yaml` (the user is called `sail` for historical reasons). `FORWARD_*`
> are the host ports Docker publishes; `MAIL_PORT` must equal `FORWARD_MAILPIT_PORT`.

---

### Step 3 — Start PostgreSQL and Mailpit

```bash
docker compose up -d
docker compose ps
```

`compose.yaml` has exactly two services, and both should show **running (healthy)**:

| Service | Container | Host ports |
|---|---|---|
| `pgsql` (PostgreSQL 18) | `invory-pgsql-1` | 5432 |
| `mailpit` | `invory-mailpit-1` | SMTP **1026**, inbox http://localhost:8026 |

Mailpit uses 1026/8026, not its usual 1025/8025, because another local Mailpit (Laragon ships one) often holds those ports.
The first start also creates the `testing` database used by the PostgreSQL test suite.

> **Never run `docker compose down -v`**: `-v` deletes the volume that holds your database. To stop the services and keep the data,
> use `docker compose stop`.

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

### Step 5 — Link public storage (photos)

```bash
php artisan storage:link
```

This makes `public/storage` point at `storage/app/public` (a junction on Windows, a symlink elsewhere). Without it, material and product
photos don't load (404/403). `composer setup` runs it for you; running it again only prints "link already exists".

---

### Step 6 — Seed demo data (optional, new empty database only)

```bash
php artisan db:seed
```

Use this for a **new, empty database only**. Never run it on a database with your own data: it resets the stock and costs of the 12 demo
materials to their demo values.

It is safe to run again: nothing is created twice, a demo product you deleted stays deleted, and the demo sales are added only when there are
no sales yet.

It populates the app with realistic sample data so every screen has something to show. Sign in with `test@example.com` and password
`password`, and you will see:

- **12 materials**, inventory value **TND 10,544.300**, and **4** that need a restock (3 low, 1 out of stock).
- **7 products**, including *Denim Jacket* at unit cost **TND 35.754**, can produce **112**, bottleneck **Metal shank button 17mm**, margin
  **59.83%** at TND 89.000.
- **60 sales**. The dashboard's "Last 30 days" shows Total sales **TND 16,850.000** from **48 sales**, **296** units sold, and the top 5 by
  units: Classic White T-shirt 142, Denim Jeans 5-pocket 64, Denim Jacket 38, Leather Belt 31, Leather Tote 12.
- A history on every demo material: each one has its "Created" entry, and three of them (Metal shank button 17mm, Woven brand label and
  Denim 12oz Indigo) also have a restock and a price change.

The worked costing example: *Denim Jacket* costs **TND 35.754** per unit and can be produced **112** times, limited by the bottleneck
**Metal shank button 17mm** (900 in stock, 8 per jacket).

---

### Step 7 — Run the app

```bash
composer dev
```

`composer dev` runs `php artisan dev`, which starts, in one terminal: `php artisan serve` on http://localhost:8000, the queue listener
(`php artisan queue:listen`) and Vite (`npm run dev`, hot reload). Ctrl+C stops them all.

Alternative, in two terminals:

```bash
php artisan serve --port=8000
npm run dev
```

> PHP's built-in server handles **one request at a time on Windows**, so the dashboard and product pages fill their panels one after another.
> Turning on `opcache.enable_cli=1` in `php.ini` makes each request several times faster ([docs/SETUP.md §4.3](docs/SETUP.md)).

---

### Log in

Go to http://localhost:8000 and sign in with:

- **Email:** `test@example.com`
- **Password:** `password`

Emails the app sends (account verification, password reset) arrive in Mailpit at http://localhost:8026.

---

### Running the test suite

Run the gates **one after another** (in parallel, a long frontend test can time out):

```bash
composer test         # Pint (style check) + Larastan + Pest on SQLite in-memory (no Docker needed)
npx vp test           # Vitest (React component tests)
npm run check         # Oxfmt + Oxlint
npm run types:check   # TypeScript
npm run build         # production bundle
composer test:browser # the golden path in Chromium (needs the build above)
```

All six gates must be green before committing.

`composer test:browser` runs one browser test, from sign-in to a sale on the dashboard, in Chromium, on its own throwaway database. Run it
after `npm run build`: it loads the built assets. Install the browser once first (about 110 MB, not part of `composer setup`):

```bash
npx playwright install chromium
```

PostgreSQL parity (Docker running):

```bash
composer test:pgsql   # the same Pest suite against Docker PostgreSQL, database `testing` only
```

It never touches your dev database `laravel` (the run stops if the database isn't `testing`). Don't start two parity runs at once: they share
the `testing` database.

CI (GitHub Actions) runs on every push to `main` and `phase/**` and on pull requests: lint, types, Vitest, build and Pest on SQLite, plus the
PostgreSQL suite on PHP 8.3 and 8.5. The golden-path browser test runs in its own `golden-path` job.

---

### Troubleshooting

| Problem | Fix |
|---|---|
| `docker compose up -d` fails: "port is already allocated" (1026, 8026 or 5432) | Something else holds the port. In `.env`, set free ports: `FORWARD_MAILPIT_PORT` and `MAIL_PORT` (SMTP), `FORWARD_MAILPIT_DASHBOARD_PORT` (inbox), or `FORWARD_DB_PORT` and `DB_PORT` (PostgreSQL). Then `php artisan config:clear` and `docker compose up -d` |
| Photos return 403 or 404 | `php artisan storage:link` (Step 5) |
| `php` or `composer`: command not found | Add PHP and Composer to your PATH, then restart the terminal |
| Emails don't arrive in Mailpit | In `.env`: `MAIL_HOST=127.0.0.1` and `MAIL_PORT=1026` (the SMTP port, not the 8026 inbox). Then `php artisan config:clear`, and check `docker compose ps` shows `mailpit` running |
