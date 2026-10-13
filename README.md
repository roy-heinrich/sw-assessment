# Southwest Tours (Boracay) — Booking Manifest

A small passenger-transfer booking tool. Staff can create, edit, search,
filter, archive (soft-delete) and restore bookings, and change their status.

Plain PHP 8.2, no framework. One entry point (`index.php`), a PDO repository,
and Tailwind + htmx views. Deployed on Vercel as a single PHP function.

## Run locally

```bash
# Optional: prebuilt CSS is committed. Rebuild it after adding utility classes.
npm install
npm run build:css

php -S localhost:8000
```

Open <http://localhost:8000>.

## Configuration

Copy `.env.example` to `.env` and set:

- `DATABASE_URL` — a PostgreSQL connection string (Neon or any Postgres).
- `SQLITE_PATH` — optional; used only when `DATABASE_URL` is unset and not on
  Vercel. Defaults to a file in the system temp directory.

With no `DATABASE_URL` the app uses a local SQLite file for development. On
Vercel, `DATABASE_URL` is required and the app refuses to start without it.

The database schema (table + indexes) is created automatically on first
connection, so no manual migration step is needed.

## Tests

```bash
php tests/repository_test.php
php tests/input_test.php
```

They run against a throwaway SQLite file (`SQLITE_PATH`) and touch no real data.

## Deploy

`vercel.json` sends all requests to the PHP function. The compiled CSS is
inlined into the page, so there is no separate static-file route to configure.
Set `DATABASE_URL` in the Vercel project's environment variables.
