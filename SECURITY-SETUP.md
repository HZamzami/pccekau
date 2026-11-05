# Production setup (Laravel Cloud)

> **Before anything else:** Laravel Cloud has no Saudi Arabia region. Saudi health-data
> rules (PDPL, MOH/NCA policies) generally require patient data to stay in the Kingdom
> or need explicit approval. Get sign-off from the hospital's IT / compliance team
> before putting real patient data on any external host.

---

## Security in place

| Area | What the app does |
|---|---|
| Login | Optional **two-factor login** with an authenticator app (user menu → Two-factor authentication) |
| Sign-up | Public sign-up at `/admin/register` creates a new clinic with that person as its admin |
| Patient PDFs & documents | Users with two-factor on must enter their code here too |
| Passwords | At least 12 characters, letters and numbers |
| Browser headers | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS on HTTPS |
| Session cookie | Encrypted, HTTPS-only in production |
| Files | Patient documents and import spreadsheets go to a **private** bucket; import spreadsheets are deleted once the import finishes |
| Calendar feed | `/staff/{id}/schedule.ics` needs the staff member's secret token and is limited to 60 requests a minute |

---

## Environment variables

Set these in the Laravel Cloud dashboard:

| Variable | Value | Why |
|---|---|---|
| `APP_NAME` | `PCCEKAU` | Shown in emails and the browser title |
| `APP_ENV` | `production` | HTTPS-only cookies and links |
| `APP_DEBUG` | `false` | Debug pages leak secrets and patient data on errors |
| `APP_KEY` | a generated key (`php artisan key:generate --show`) | Encrypts sessions and 2FA secrets. **Never change it after launch**: every user's 2FA would break |
| `LOG_LEVEL` | `warning` | Keeps logs to problems |
| `MAIL_MAILER` | `resend` | Password-reset and notification emails |
| `RESEND_API_KEY` | your Resend key | |
| `MAIL_FROM_ADDRESS` | an address on a domain verified in Resend | |
| `PATIENT_DOCUMENTS_DISK` | the bucket's **disk name** in Laravel Cloud, or leave unset | If unset or wrong, the app finds the attached bucket by itself |
| `SESSION_LIFETIME` | e.g. `60` | Minutes of inactivity before logout (default 120) |

Attach a **bucket** (private) and a **Postgres database** to the environment.

---

## Deploy commands

In the environment's deploy commands, in this order:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

`optimize` also caches Filament's components and icons. Clearing first stops caches from
an earlier release causing errors after a deploy.

Also in the dashboard:
- Turn on the **scheduler**. It sends the 07:00 daily digest (`pccekau:daily-digest`).
- Turn on **database backups**.

No queue worker is needed: nothing is queued.

---

## Wiping the demo data before real use

The database currently holds made-up data. To start clean, run these from the Laravel
Cloud **Commands** tab.

> **This permanently deletes every clinic, user, patient and record.** Take a backup first
> if there's anything to keep. Uploaded files in the bucket are not removed by this; empty
> the bucket's `patient-documents/` and `imports/` folders from the Cloud dashboard too.

```bash
php artisan migrate:fresh --force
php artisan pccekau:create-clinic
```

The second command asks for the clinic name and its first admin's name, email and
password. That admin then adds everyone else under **Admin → Users**.

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
