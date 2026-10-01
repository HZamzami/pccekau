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
| Login | Password only | Optional **two-factor login**: each user can turn on an authenticator-app code from their user menu |
| Sign-up | Public sign-up at `/admin/register` | Unchanged (still public), now with the stronger password rule; admins can also create a clinic from the command line |
| Patient PDFs & document downloads | Password was enough | Users with two-factor on must enter their code here too |
| Passwords | Any length | At least 12 characters, letters and numbers (sign-up, password reset, admin "create user" form) |
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

4. **Turn on database backups** in Laravel Cloud.

---

## Creating a clinic and its first admin

Anyone can still sign up at `/admin/register`, which creates a new clinic with them as its admin.
Everyone else is added by that admin under **Admin → Users**.

Server admins can also create a clinic from the command line (in Laravel Cloud: the **Commands**
tab; locally: `ddev exec php artisan ...`):

```bash
php artisan pccekau:create-clinic
```

It asks for the clinic name, admin name, admin email and password.

---

## Two-factor authentication: what users see

Two-factor login is **optional**. Nobody is forced to set it up, but it's strongly recommended
for anyone who sees patient data.

**Turning it on:**
1. Open the user menu (top right) and choose **Two-factor authentication**.
2. Confirm your password.
3. Scan the QR code with an authenticator app: Google Authenticator, Microsoft
   Authenticator, 1Password, Authy, or similar.
4. Type the 6-digit code from the app to confirm.
5. **Save the recovery codes** somewhere safe (a password manager, or print them).
   Each one works once if you lose your phone.

You can turn it off again from the same page.

**Every login after turning it on:** email + password, then the 6-digit code from the app.
The code is also asked for before opening patient PDFs and documents.
There are at most 5 attempts per minute.

**Lost phone:** on the code screen, click **use a recovery code** and enter one of your
recovery codes. Then go to the user menu (top right), **Two-factor authentication**, to
set up the new phone.

**Lost phone *and* recovery codes:** an admin with server access can turn two-factor off
for that user, who can then log in with just their password and set it up again:
```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'someone@example.com')->first();
>>> $u->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
```


---

## Local development

Two-factor is off unless you turn it on for your local account. If you do and lose access, reset
it with the tinker snippet above. `tests/Feature/SecurityHardeningTest.php` covers the code check.
