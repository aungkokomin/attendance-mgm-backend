# Attendance Management Backend

This backend is a Laravel 12 application for attendance management and correction workflows.

## Requirements

- PHP 8.2 or later
- Composer
- Node.js 18+ and npm
- A supported database (MySQL, PostgreSQL, SQLite)

## Installation

From the `backend` directory, run:

```powershell
composer install
cp .env.example .env
php artisan key:generate
```

If you plan to use an SQLite database, create the database file after `.env` is configured:

```powershell
php artisan migrate --force
```

## Environment

Copy the example env file and update your database settings:

```powershell
cp .env.example .env
```

Open `.env` and set the following values for your local environment:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Database Setup

Run migrations to create the database schema:

```powershell
php artisan migrate
```

If you need seed data, run:

```powershell
php artisan db:seed
```

## Local Development

Start the application server:

```powershell
php artisan serve
```

Open the backend at `http://127.0.0.1:8000`.

## Project Scripts

The backend includes a helper setup script in `composer.json`:

```powershell
composer run setup
```

This command will:

- install PHP dependencies
- create `.env` from `.env.example` if needed
- generate the application key
- run database migrations

## Testing

Run package tests with:

```powershell
php artisan test
```

## Notes

- If you change environment settings after install, run `php artisan config:clear`.
- Enable queue workers or scheduler tasks as needed for background processing.
- For production deployment, verify database credentials, caching, and app environment values.
