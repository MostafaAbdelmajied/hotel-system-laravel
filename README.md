# Hotel System Laravel

Hotel System Laravel is a hotel management application built with Laravel 13, Inertia.js v2, Vue 3, Tailwind CSS v4, and Fortify authentication.

The current codebase includes:

- Authentication with login, registration, password reset, email verification, and two-factor authentication
- An authenticated dashboard
- User settings pages
- Role and permission management using `spatie/laravel-permission`
- A seeded admin user for local development

## Tech Stack

- PHP 8.3
- Laravel 13
- Inertia.js v2
- Vue 3
- Tailwind CSS v4
- Vite
- Pest
- SQLite by default for local development

## Prerequisites

Make sure these are installed before you start:

- PHP 8.3+
- Composer
- Node.js 20+
- npm
- SQLite

## Setup After Cloning

Run these commands after cloning the repository:

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
npm install
```

If you want a production-style frontend build:

```bash
npm run build
```

## Quick Setup

This project already includes a Composer setup script:

```bash
composer setup
```

After that, run the database seeder because the setup script migrates the database but does not seed it:

```bash
php artisan db:seed
```

## Start The Development Environment

Run the full local development stack with:

```bash
composer dev
```

This starts:

- Laravel local server
- Queue listener
- Laravel Pail log viewer
- Vite dev server

## Useful Commands

```bash
php artisan test --compact
php artisan migrate
php artisan migrate:fresh --seed
npm run dev
npm run build
npm run lint
npm run format
```

## Local Login

The database seeder creates a local admin account:

- Email: `admin@admin.com`
- Password: `123456`

## Notes

- The default local database is SQLite using `database/database.sqlite`
- If frontend changes are not showing up, run `composer dev` or `npm run build`
