# PCCEKAU

Clinical workspace for a paediatric cardiology centre: patient charts, imaging and EP
reports with a sign-off workflow, surgery and cath records, case discussions, procedure
booking with a wait-list and calendar, clinic follow-ups, inpatients and staff schedules.

Built with Laravel 13, Filament 3 and Livewire 3 on PHP 8.4 and Postgres. Each clinic is
its own workspace (multi-tenant).

## Local setup (ddev)

```bash
ddev start
ddev composer install
ddev npm install && ddev npm run build
cp .env.example .env    # first time only
ddev artisan key:generate
ddev artisan migrate
ddev artisan db:seed --class=DemoDataSeeder   # optional made-up data
```

Open http://pccekau.ddev.site. Create a clinic and its admin with
`ddev artisan pccekau:create-clinic`, or sign up at `/admin/register`.

## Tests

```bash
ddev artisan test
```

Tests run on in-memory SQLite. To run them against Postgres, as in production:

```bash
ddev exec 'DB_CONNECTION=pgsql DB_HOST=db DB_DATABASE=testing DB_USERNAME=db DB_PASSWORD=db php artisan test'
```

(create the `testing` database once with `ddev exec -s db createdb -U db testing`).

`tests/Feature/PageSmokeTest.php` loads every page, widget, patient tab and PDF for every
role, so a server error shows up there before it reaches users.

## Deploying

The app runs on Laravel Cloud. Environment variables, deploy commands, the scheduler and
the steps to wipe demo data are in [SECURITY-SETUP.md](SECURITY-SETUP.md).
