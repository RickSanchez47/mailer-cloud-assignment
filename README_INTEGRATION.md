# Integrating this into your fresh Laravel project

These files implement the vertical slice: create/publish a form, serve it
publicly, submit to it with server-side validation re-derived from the
dynamic schema, and store the submission durably. They're laid out to
drop straight into a fresh Laravel install.

## 1. Copy files in

Copy each folder's contents into the matching path in your project root
(these are additive — nothing here overwrites Laravel's defaults except
`routes/api.php`, so merge that one by hand if you've already touched it):

```
database/migrations/*   -> database/migrations/
database/factories/*    -> database/factories/
app/Models/*             -> app/Models/
app/Services/*           -> app/Services/
app/Http/Controllers/*   -> app/Http/Controllers/
app/Jobs/*               -> app/Jobs/
routes/api.php           -> routes/api.php   (merge if you have one already)
tests/Feature/*          -> tests/Feature/
scripts/*                -> scripts/
```

## 2. Auth scaffolding (not included — out of scope for this slice)

The authenticated `/api/forms*` routes assume `$request->user()->account`
resolves to an `Account`. This slice doesn't include user/auth
scaffolding — wire up whatever fits your stack, e.g.:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Then add an `account_id` foreign key to your `users` table and an
`account()` relation on your `User` model. The public `/api/public/...`
routes need no auth at all — they're resolved by the account's `api_key`
in the URL.

## 3. Queue driver

Side effects (webhooks/notifications) run through
`App\Jobs\ProcessSubmissionSideEffects`. For anything beyond local
testing, set a real queue connection in `.env`:

```
QUEUE_CONNECTION=redis
```

(`database` works for local dev: `php artisan queue:table && php artisan migrate`.)
Run a worker with `php artisan queue:work`.

## 4. Migrate and test

```bash
php artisan migrate
php artisan test
```

The three feature tests cover the correctness-critical paths called out
in the brief:
- `DynamicValidationTest` — server-side validation re-derived from the
  schema, including conditional-required fields and stored-XSS stripping.
- `SubmissionVersionIntegrityTest` — republishing a form never reattaches
  or corrupts prior submissions.
- `TenantIsolationTest` — one account's form is never resolvable under
  another account's public key.

## 5. Run the working slice end-to-end

```bash
php artisan serve
```

Create and publish a form (replace `YOUR_TOKEN` with a Sanctum token for
a user tied to an account):

```bash
curl -X POST http://localhost:8000/api/forms \
  -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Newsletter Signup","schema":{"fields":[
        {"key":"email","type":"email","label":"Email","required":true}
      ]}}'
# -> note the form id

curl -X POST http://localhost:8000/api/forms/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Fetch the public schema and submit to it (replace with your account's
`api_key` and the form's `slug` from the responses above):

```bash
curl http://localhost:8000/api/public/ACCOUNT_API_KEY/forms/newsletter-signup-abc123
curl -X POST http://localhost:8000/api/public/ACCOUNT_API_KEY/forms/newsletter-signup-abc123/submit \
  -H "Content-Type: application/json" -d '{"data":{"email":"a@example.com"}}'
```

## 6. Load-test the burst path

```bash
composer require guzzlehttp/guzzle   # skip if already a dependency
php scripts/load_test.php ACCOUNT_API_KEY newsletter-signup-abc123 2000 100
```

This fires 2000 requests at concurrency 100 and reports 2xx / 429
(rate-limited) / 5xx / transport-error counts plus req/s — enough to show
the path stays responsive and doesn't drop submissions under burst at
this scale. It won't by itself prove out million-request bursts; that's
addressed architecturally in `ARCHITECTURE.md`, not by this script.

## What's intentionally not here

This is the vertical slice, not the full system. Left as designed-but-
not-built (belongs in your `ARCHITECTURE.md`): a buffering/queue layer
in front of the DB write for extreme burst absorption, webhook delivery
in `ProcessSubmissionSideEffects`, per-tenant rate-limit tiers, and
horizontal read replicas for the submissions dashboard/export at very
large volume.
