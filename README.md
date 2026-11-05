# Product Catalogue

A proof of concept where customers browse and search products and send an enquiry, built as a foundation that can grow. The product data source, look and feel, and email provider can each change without rewriting the rest.

**Stack:** React with TypeScript, Laravel, SQLite, Mailpit and Docker. The reasons are in [`DECISIONS.md`](DECISIONS.md).

## Run it

You need Docker with Compose, and ports `5173`, `8000` and `8025` free. There are no accounts or keys to set up.

```bash
git clone https://github.com/technofreak5360/officework.git
cd officework
git checkout feature/product-catalogue   # until the PR is merged
docker compose up --build
```

From a clean copy this took about a minute with Docker's base images already downloaded. Allow a few more minutes the first time, while those download.

| Open | URL |
|---|---|
| The app | http://localhost:5173 |
| Sales inbox (Mailpit) | http://localhost:8025 |
| API health check | http://localhost:8000/api/health |

Stop it with `docker compose down`. Add `-v` to also delete saved enquiries and start clean.

If Docker refuses to mount the folder, add it under **Docker Desktop > Settings > Resources > File Sharing**.

## Run the tests

With the stack running:

```bash
docker compose exec api php artisan test   # backend
docker compose exec web npm test           # frontend
```

Tests don't call the real Fake Store API, so they pass offline.

## Acceptance criteria

| Feature | Status | Where to try it |
|---|---|---|
| Home page | Done | http://localhost:5173 shows the 5 chosen products |
| Category page | Done | Click a category: top rated first, then the rest |
| Search | Done | Enter a term and click **Search** |
| Product page | Done | Click a product tile |
| Enquiry | Done | On a product page, click **Enquire** and send the form, then see the email at http://localhost:8025 |

## How it fits together

```text
Browser ──> Vite dev server (web) ──/api──> Laravel API (api) ──> ProductCatalogue
                                                │                  ├─ FakeStoreCatalogue ──> Fake Store API
                                                │                  └─ FixtureCatalogue ───> local JSON file
                                                ├─> SQLite (enquiries)
                                                └─> SMTP ──> Mailpit (mailpit)
```

```text
backend/app/Catalogue/   Product data boundary, sources, featured and top rated logic
backend/app/Enquiries/   Saving an enquiry and emailing the sales inbox
backend/routes/api.php   The API endpoints
frontend/src/api/        The only frontend code that calls the backend
frontend/src/pages/      One component per page
frontend/src/styles/     tokens.css holds the look and feel
```

## API

| Endpoint | Returns |
|---|---|
| `GET /api/products/featured` | The chosen products |
| `GET /api/products?search=term` | Products matching the term, or all products if it's blank |
| `GET /api/products/{id}` | One product, or 404 |
| `GET /api/categories` | Categories with their slugs |
| `GET /api/categories/{slug}/products` | `{ category, topRated, others }`, or 404 |
| `POST /api/enquiries` | 201 on success, 422 if invalid, 429 if rate limited |
| `GET /api/health` | `{ "status": "ok" }` |

If the product source is down, product endpoints and enquiries return a 503.

## Configuration

The first start copies `backend/.env.example` to `backend/.env` and generates the app key. The defaults work as they are. `.env` is git-ignored.

To change a setting, edit `backend/.env`, then run `docker compose restart api`.

| Setting | Default | Purpose |
|---|---|---|
| `CATALOGUE_DRIVER` | `fakestore` | Product source. `fixture` uses a local copy with no calls to Fake Store. |
| `FEATURED_PRODUCT_IDS` | `3,5,11,12,18` | The home page products, in order |
| `CATEGORY_TOP_RATED_COUNT` | `3` | How many products count as top rated on a category page |
| `SALES_INBOX_ADDRESS` | `sales@example.com` | Who receives enquiries |
| `MAIL_*` | Mailpit | Point these at a real provider to send real email |

If Fake Store is unavailable, set `CATALOGUE_DRIVER=fixture`. Product data keeps working, but images still load from Fake Store.

## Editing the code

The app runs fine without this, but dependencies live in Docker volumes, so your editor can't see them. For autocomplete and to avoid false errors, install them on your machine too:

```bash
cd frontend && npm ci
cd ../backend && composer install --ignore-platform-reqs --no-scripts
```

Code changes apply without a rebuild.

## More documents

- [`DECISIONS.md`](DECISIONS.md): assumptions, cuts, known gaps, how it can grow, and next steps
- [`AI_USAGE.md`](AI_USAGE.md): how AI was used, and where it went wrong
- [`CLAUDE.md`](CLAUDE.md): instructions for AI agents working in this repo

## Time spent

| Activity | Time |
|---|---|
| Planning | 3–4 hours |
| Building | 3 hours |
| Testing | 1 hour |
| Reviewing | 1 day |
| **Total** | **7–8 hours, plus 1 day of review** |
