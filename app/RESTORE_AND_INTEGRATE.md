# Restoring & integrating — Laravel 13

This package is self-contained: it includes Laravel's own default files
(restored) *and* the webform-builder code, so you can drop the whole
thing in without hunting for what's missing piece by piece.

## Before you do anything: back up

```bash
cp -r /Applications/XAMPP/xamppfiles/htdocs/mailer-cloud-assignment /Applications/XAMPP/xamppfiles/htdocs/mailer-cloud-assignment-backup
```

## What's in this zip and where it goes

```
app/Http/Controllers/Controller.php        -> app/Http/Controllers/Controller.php   (Laravel default)
app/Http/Controllers/Api/*                 -> app/Http/Controllers/Api/
app/Http/Controllers/Public/*              -> app/Http/Controllers/Public/
app/Models/User.php                        -> app/Models/User.php   (Laravel default + Sanctum + account())
app/Models/Account.php, Form.php, ...      -> app/Models/
app/Providers/AppServiceProvider.php       -> app/Providers/AppServiceProvider.php  (Laravel default)
app/Services/FormSchema/*                  -> app/Services/FormSchema/
app/Jobs/*                                 -> app/Jobs/
bootstrap/app.php                          -> bootstrap/app.php   (Laravel default, with web+api+console wired)
routes/web.php, console.php, api.php       -> routes/
database/migrations/*                      -> database/migrations/
database/factories/*                       -> database/factories/
tests/Feature/*                            -> tests/Feature/
scripts/load_test.php                      -> scripts/
```

**Copy files into these folders — don't delete-and-replace whole
directories.** If your project already had other files in `app/Models`,
`app/Providers`, etc. (which it should, from `composer create-project`),
those stay; you're only adding/overwriting the specific files listed
above.

## If bootstrap/app.php already has custom content

Laravel 13's default `bootstrap/app.php` normally does **not** include
an `api:` line unless you ran `php artisan install:api` (which Sanctum's
installer usually adds for you). Before overwriting it, check whether
yours already has a `->withRouting(...)` block:

- If it does **and** already lists `api: __DIR__.'/../routes/api.php'`,
  keep your existing file — don't overwrite it.
- If it's missing the `api:` line, just add that one line to your
  existing file rather than replacing the whole thing.
- If your `bootstrap/app.php` is genuinely gone or badly broken, use the
  one in this zip as-is.

## Steps after copying files in

```bash
# 1. Composer packages you need
composer require laravel/sanctum guzzlehttp/guzzle

# 2. Migrate (creates accounts/forms/form_versions/submissions,
#    and adds account_id to users)
php artisan migrate

# 3. Run the test suite
php artisan test

# 4. Create a test account + user + token
php artisan tinker
>>> $account = \App\Models\Account::factory()->create();
>>> $user = \App\Models\User::factory()->create(['account_id' => $account->id]);
>>> $token = $user->createToken('test')->plainTextToken;
>>> exit

# 5. Serve and try it
php artisan serve
```

Create + publish a form (replace YOUR_TOKEN):

```bash
curl -X POST http://localhost:8000/api/forms \
  -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Newsletter Signup","schema":{"fields":[
        {"key":"email","type":"email","label":"Email","required":true}
      ]}}'

curl -X POST http://localhost:8000/api/forms/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Fetch and submit publicly (replace with your account's `api_key` and the
form's `slug` from the responses above):

```bash
curl http://localhost:8000/api/public/ACCOUNT_API_KEY/forms/newsletter-signup-abc123
curl -X POST http://localhost:8000/api/public/ACCOUNT_API_KEY/forms/newsletter-signup-abc123/submit \
  -H "Content-Type: application/json" -d '{"data":{"email":"a@example.com"}}'
```

Load-test the burst path:

```bash
php scripts/load_test.php ACCOUNT_API_KEY newsletter-signup-abc123 2000 100
```

## Still missing files after this?

If `php artisan test` or `php artisan serve` still complains about a
missing file, the fastest fix is: `composer create-project laravel/laravel temp-laravel`
in a separate folder, then copy the specific missing file from there —
don't copy whole folders across.
