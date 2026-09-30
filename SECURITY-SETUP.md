# Security setup (for hosting patient data)

This branch hardens the app before it goes on a public host such as Laravel Cloud.
Merge it into `main` when you're ready to deploy it.

> **Before anything else:** Laravel Cloud has no Saudi Arabia region. Saudi health-data
> rules (PDPL, MOH/NCA policies) generally require patient data to stay in the Kingdom
> or need explicit approval. Get sign-off from the hospital's IT / compliance team
> before putting real patient data on any external host.

---

## What changed

| Area | Before | Now |
|---|---|---|
| Login | Password only | Password **+ authenticator app code** for every user |
| Sign-up | Anyone could register at `/admin/register` | No public sign-up; clinics are created from the command line |
| Patient PDFs & document downloads | Password was enough | Also require the 2FA code |
| Passwords | Any length (admin "create user" form) | At least 12 characters, letters and numbers |
| Browser security headers | None | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS on HTTPS |
| Session cookie | Unencrypted, not HTTPS-only | Encrypted, HTTPS-only in production |
| Dependencies | 27 known vulnerabilities | `composer audit` clean |

---

## Deploying

1. **Merge the branch** into `main` and deploy.
2. **Run the migrations** (adds the 2FA columns and a `passkeys` table):
   ```bash
   php artisan migrate --force
   ```
3. **Set these environment variables** in the Laravel Cloud dashboard:

   | Variable | Value | Why |
   |---|---|---|
   | `APP_ENV` | `production` | Enables HTTPS-only cookies and forced HTTPS links |
   | `APP_DEBUG` | `false` | Debug pages leak secrets and patient data on errors |
   | `APP_KEY` | a generated key (`php artisan key:generate --show`) | Encrypts sessions and 2FA secrets. **Never change it after launch**: every user's 2FA would break |
   | `PATIENT_DOCUMENTS_DISK` | `s3` | Store uploaded documents in a **private** bucket, plus the `AWS_*` bucket settings |
   | `SESSION_LIFETIME` | e.g. `60` | Minutes of inactivity before logout (default 120) |

   Do **not** set `TWO_FACTOR_ENFORCED`. It defaults to on; only the test suite turns it off.
4. **Turn on database backups** in Laravel Cloud.

---

## Creating a clinic and its first admin

Public sign-up is gone. On a fresh database, create the workspace from the command line
(in Laravel Cloud: the **Commands** tab; locally: `ddev exec php artisan ...`):

```bash
php artisan pccekau:create-clinic
```

It asks for the clinic name, admin name, admin email and password. Everyone else is then
added by that admin under **Admin → Users**, as before.

If your production database already has your clinic and admin, you don't need this.

---

## Two-factor authentication: what users see

**First login after the deploy (every existing user, including you):**
1. Log in with email and password as usual.
2. You're taken to the **two-factor setup** page. Confirm your password.
3. Scan the QR code with an authenticator app: Google Authenticator, Microsoft
   Authenticator, 1Password, Authy, or similar.
4. Type the 6-digit code from the app to confirm.
5. **Save the recovery codes** somewhere safe (a password manager, or print them).
   Each one works once if you lose your phone.

**Every login after that:** email + password, then the 6-digit code from the app.
There are at most 5 attempts per minute.

**Lost phone:** on the code screen, click **use a recovery code** and enter one of your
recovery codes. Then go to the user menu (top right), **Two-factor authentication**, to
set up the new phone.

**Lost phone *and* recovery codes:** an admin with server access can reset that user,
who will then be asked to set up 2FA again at next login:
```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'someone@example.com')->first();
>>> $u->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
```

Tell your team before you deploy. Everyone gets the setup screen on their next login.

---

## Local development

Local logins require 2FA too. Use an authenticator app with your local account, or reset it
with the tinker snippet above. Tests run with 2FA enforcement off (`phpunit.xml`), and
`tests/Feature/SecurityHardeningTest.php` turns it back on to test it.
