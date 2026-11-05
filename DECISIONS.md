# Decisions and assumptions

## Stack

| Choice | Why |
|---|---|
| React web app with TypeScript | I first wanted React Native, but it isn't one of the brief's client options. React web is, and it runs in Docker, so a reviewer needs no Simulator or Emulator. |
| Laravel API | One of the allowed backends, with mail, validation, rate limiting and an HTTP client built in. |
| SQLite | Enquiries need saving, but nothing here justifies a database container. Moving to MySQL or Postgres is mainly a config change. |
| Mailpit | A reviewer can see a real email arrive with no account or paid service. |
| Vite dev server in Docker | Hot reload makes the live change in the follow-up quick, and proxying `/api` means no CORS setup. Production images are not built yet. |

## Assumptions

### Home page
- "I have chosen 5 products" means a list of product IDs in config: `FEATURED_PRODUCT_IDS=3,5,11,12,18`.
- Those IDs happen to be the five highest rated, but they are hand-picked, not calculated.
- An ID that no longer exists is skipped and logged, so one stale ID doesn't break the page.

### Category page
- "Top rated" means the 3 highest rated by the API's score, because there are no per-category picks to read. The count is configurable.
- This differs on purpose from the home page, which is curated.
- Ties break on review count, then ID, so the order is stable.
- Jewelery has 4 products, so it shows 3 top rated and 1 other.

### Search
- Search matches the whole term, ignoring case, in the title, description and category name. `gold ring` won't match "ring made of gold".
- Fake Store has no search endpoint, so the backend filters the list.
- Clicking Search with a blank term does nothing. A blank term sent directly to the API returns all products.
- The term goes in the URL (`/search?q=`), so results can be linked to and the Back button works.

### Product data
- Prices are shown in USD, because the API has no currency field.
- Category names come straight from the source, including its spelling of "jewelery". The app uses slugs it generates in URLs, such as `mens-clothing`.

### Enquiry
- Name and email are required. Phone and message are optional.
- The enquiry is saved before the email is sent. If sending fails, the failure is logged and recorded on the enquiry, and the customer still sees "Thank you", because the lead is kept.
- The product name in the email is looked up on the server, so a client can't fake it.
- Replying to the email goes to the customer.
- The customer doesn't get a confirmation email, because the criteria don't ask for one.

### "No direct calls to Fake Store"
I read this as covering product data, which all goes through the backend. Product images still load from `fakestoreapi.com` URLs in the browser. An image proxy didn't seem worth it for a PoC, but this is a known gap and easy to challenge.

## Dropped or simplified

| What | Why |
|---|---|
| Admin screen for choosing featured products | It would need authentication to be done responsibly, which doesn't fit the time-box. |
| Pagination | The catalogue has about 20 products. |
| Queue worker for email | It would add another process to run. Mail is sent during the request with a 5-second timeout. |
| Serving stale data in an outage | Once the 10-minute cache expires, an outage returns a 503. `CATALOGUE_DRIVER=fixture` is the manual fallback. |
| Production Docker images and AWS infrastructure | Not needed for a local PoC. Everything used is compatible with AWS, such as moving to RDS and SES. |
| Automated browser tests | Frontend tests use a simulated browser and a fake API. See the testing note below. |

## Known gaps

- **Rate limiting isn't really per customer yet.** Requests arrive through the Vite proxy, so Laravel sees the proxy's IP and all visitors share one limit of 5 per minute. Behind an AWS load balancer this needs trusted proxies configured. I didn't trust all proxies, because that would let clients fake their IP.
- **Containers aren't hardened.** They run as root and use PHP's built-in server, which suits local development only.
- **Customer data.** Enquiries are stored unencrypted in SQLite with no retention policy, which needs sorting out before real use.
- **Enquiries with email failures.** They are recorded as `failed`, but nothing resends them or alerts anyone yet.
- **Images during an outage.** `fixture` mode serves product data, but images still point at `fakestoreapi.com`.
- **Scaffold leftover.** `config/auth.php` still names Laravel's default `User` class, which was removed. It's harmless because nothing uses authentication.

## How the design supports growth

The brief names three things that will change. Each has one place to change it:

| Expected change | What changes | What stays the same |
|---|---|---|
| Product data source | Add a class implementing `ProductCatalogue` and a case in `AppServiceProvider` | Controllers, API responses, frontend |
| Look and feel | Mostly `frontend/src/styles/tokens.css`, then `app.css` | Pages and data fetching |
| Email provider | `MAIL_*` settings in `.env` | Enquiry logic and the email itself |

- **The boundary is tested.** `ProductCatalogueContractTest` runs the same tests against both current sources, so a new source only has to pass them.
- **The second source is real.** `FixtureCatalogue` proves the app runs with no Fake Store at all.
- **Search belongs to the source.** `search()` and `inCategory()` are on the interface, so a future source can use its own search engine rather than loading every product.
- **The API uses its own format.** Field names like `imageUrl` are ours, and a test checks Fake Store's `image` field doesn't leak through.
- **The frontend has one way out.** Only `src/api/client.ts` calls `fetch`.
- **Featured products have one owner.** Moving them to a database or admin screen only changes `FeaturedProducts`.

## Testing approach

- **Backend (63 tests):** every endpoint, the same contract tests on both sources, upstream failures, mail failure and rate limiting. Tests can't reach the real Fake Store API.
- **Frontend (22 tests):** these render the whole app and follow the acceptance criteria journeys, using a stubbed `fetch`.
- **Do the tests catch bugs?** I had deliberate bugs put into the code to check: the tests caught all 12 in the backend and all 14 in the frontend. These were spot checks, not full mutation testing.
- **Limits:** no automated test covers real layout, real image loading or the real browser. Those are manual checks for now.

## With another day

1. Add Playwright tests for the main journeys against the running stack.
2. Build production images with PHP-FPM, a static frontend and a non-root user, plus CI to run the tests.
3. Configure trusted proxies so rate limiting works per customer.
4. Move email to a queue, with retries and an alert for failed enquiries.
5. Serve the last good product list during an outage.
6. Decide on an image approach and a data retention policy.
