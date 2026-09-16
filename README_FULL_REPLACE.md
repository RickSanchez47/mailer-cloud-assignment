# Complete project files — Webform Builder (Laravel 13)

This is everything in one package: backend (models, dynamic validation,
API controllers, migrations, jobs, tests) plus frontend (dashboard for
building/publishing forms, public form page, submissions view). You can
replace the matching folders in your project wholesale with what's here
— nothing has been left as a "merge this by hand" snippet this time.

## Back up first

```bash
cp -r /Applications/XAMPP/xamppfiles/htdocs/mailer-cloud-assignment /Applications/XAMPP/xamppfiles/htdocs/mailer-cloud-assignment-backup
```

## What to replace

Copy each of these **whole folders/files**, overwriting what's there:

```
app/                     -> app/                     (replace entirely)
bootstrap/app.php        -> bootstrap/app.php         (replace this one file only — leave the rest of bootstrap/ alone)
routes/                  -> routes/                   (replace entirely — web.php, api.php, console.php all included)
database/migrations/     -> database/migrations/      (replace entirely)
database/factories/      -> database/factories/       (replace entirely)
resources/views/         -> resources/views/          (replace entirely)
public/js/               -> public/js/                (add/replace this subfolder; don't touch anything else in public/)
tests/Feature/           -> tests/Feature/             (replace entirely)
scripts/                 -> scripts/                  (add this folder)
```

Everything else in your project (`config/`, `storage/`, `vendor/`,
`composer.json`, `.env`, `public/index.php`, `public/build/`, etc.)
is untouched by this package — don't replace those.

## Setup after copying

```bash
composer require laravel/sanctum guzzlehttp/guzzle

cp .env.example .env          # if you don't already have one
php artisan key:generate
touch database/database.sqlite   # if using sqlite and it doesn't exist

php artisan migrate:fresh     # drops and recreates all tables in order
php artisan test              # should show 3 passing test files
```

## Try it

```bash
php artisan serve
```

Open **http://localhost:8000/builder**:
1. Create a demo account (stands in for real login — see the note in
   `BuilderController`).
2. Create a form, add fields (key, label, type, required, help text,
   options, conditional "only show if"), save the draft, publish it.
3. Open the public URL shown on the form's row — it renders the form
   dynamically from the schema, including conditional field visibility,
   and submits to the validated/rate-limited public API.
4. Check **Submissions** on the form to see it land, and try **Export CSV**.

Burst-test the submission endpoint:

```bash
php scripts/load_test.php {account_api_key} {form_slug} 2000 100
```

## What's included vs. what's still on you

**Included:** dynamic form schema + versioning + immutable publish,
server-side validation re-derived from the schema (incl. conditional
required fields), stored-XSS sanitization on both schema and submission
data, per-form/IP rate limiting + honeypot spam check, tenant-scoped
queries throughout, cursor-friendly submissions listing + chunked CSV
export, automated tests for the three correctness-critical paths, a
burst load generator, and a full browser UI for both building and
publicly serving forms.

**Not included, called out as designed-not-built:** real account
authentication (the `/builder` account picker is a deliberate stand-in),
a buffering/queue layer in front of the DB write for extreme-scale burst
absorption, webhook delivery in `ProcessSubmissionSideEffects` (stubbed),
and horizontal read replicas for submissions at very large volume. These
belong in your `ARCHITECTURE.md`'s "Built vs. Designed" section.

## If something still errors

The most likely remaining gap, if any, is a Laravel default file outside
what's listed above (e.g. something in `config/`). If `php artisan serve`
or `php artisan test` complains about a missing file, the fastest fix is
`composer create-project laravel/laravel temp-laravel` in a separate
folder and copy just that one missing file over — not a whole directory.
