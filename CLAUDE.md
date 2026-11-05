# Product catalogue PoC

A product catalogue with a React web client and a Laravel API, built as a foundation that will grow. The product data source, look and feel, and email provider are all expected to change, so keep their boundaries intact.

## Commands

Everything runs in Docker. Don't rely on the host's PHP or Node versions.

```bash
docker compose up --build                  # start the stack
docker compose exec api php artisan test   # backend tests
docker compose exec api vendor/bin/pint    # backend formatting
docker compose exec web npm test           # frontend tests
docker compose exec web npx tsc -b --force # frontend type check
docker compose exec web npm run lint       # frontend lint
```

Add dependencies inside the container, for example `docker compose exec web npm install <package>`.

Before saying work is done, run the tests, type check, lint and formatter for whatever you changed.

## Boundaries

- **Product data:** only classes implementing `App\Catalogue\ProductCatalogue` may know about a data source. New sources must pass `ProductCatalogueContractTest`.
- **API format:** responses come from `app/Http/Resources`. Never expose a source's field names.
- **Frontend:** only `frontend/src/api/client.ts` may call `fetch`. The browser must never call Fake Store for product data.
- **Styling:** colours, fonts and spacing go in `frontend/src/styles/tokens.css`, not in components.
- **Email:** configured by `MAIL_*` settings. Keep provider details out of the enquiry code.

## Gotchas

- Fake Store returns `200` with an empty body for an unknown product ID. Look IDs up in the full list.
- `vendor` and `node_modules` live in Docker volumes. The host copies only exist for editors, so a check inside the container doesn't prove the editor is happy, and the reverse is also true.
- `backend/.env` is only created on first start, so changes to `.env.example` don't reach an existing one. After changing `.env`, run `docker compose restart api`.
- The API runs with `--no-reload`, which `PHP_CLI_SERVER_WORKERS` needs. PHP code changes still apply straight away.
- Tests block real HTTP calls and use `CATALOGUE_DRIVER=fixture`. They must never depend on the real Fake Store API.
- The rate limiter sees the proxy's IP in development. See "Known gaps" in `DECISIONS.md`.

## Working here

- Make assumptions where the criteria are unclear, and record them in `DECISIONS.md`.
- Record real AI mistakes in `AI_USAGE.md` when they happen. Never invent any.
- Keep it small. Prefer the simplest thing that keeps the boundaries above.
- Ask before large downloads or anything hard to undo.
- Never commit secrets. Configuration belongs in `backend/.env.example`, with safe defaults.
