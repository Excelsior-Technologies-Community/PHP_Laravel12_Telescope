# PHP_Laravel12_Telescope

<p align="center">
    <a href="https://laravel.com/docs/12.x">
        <img src="https://img.shields.io/badge/Laravel-12-red" alt="Laravel 12">
    </a>
    <a href="https://www.php.net/releases/8.3/en.php">
        <img src="https://img.shields.io/badge/PHP-8.3-blue" alt="PHP 8.3">
    </a>
    <a href="https://github.com/laravel/telescope">
        <img src="https://img.shields.io/badge/Laravel-Telescope-orange" alt="Laravel Telescope">
    </a>
    <a href="#">
        <img src="https://img.shields.io/badge/Status-Active-success" alt="Project Status">
    </a>
</p>


## Overview

PHP_Laravel12_Telescope is a comprehensive, production-ready guide for integrating Laravel Telescope into a Laravel 12 application. This documentation is designed for developers who want deep visibility into application behavior while maintaining security, performance, and clean logs.

The guide walks you through the entire lifecycle—from creating a fresh Laravel 12 project to installing and configuring Telescope, handling common issues like Service Worker (sw.js) noise, understanding empty Telescope screens, and applying best practices for production environments.

---

## Features

* Laravel 12 fresh project setup
* Laravel Telescope installation and configuration
* Local vs Production environment handling
* Sensitive data protection
* Clean Telescope dashboard (noise-free)
* Service Worker (`sw.js`) fix
* Secure access using Gates
* Production best practices

---

## Folder Structure 

```text
laravel12-app/
├── app/
│   └── Providers/
│       └── TelescopeServiceProvider.php
├── config/
│   └── telescope.php
├── public/
│   └── sw.js
├── routes/
│   ├── web.php
│   └── api.php
├── .env
└── composer.json
```

---

## 1. System Requirements

* PHP 8.3 or higher
* Composer (latest version)
* MySQL / MariaDB
* Node.js (optional, for frontend assets)

---

## 2. Create a New Laravel 12 Project

Create a fresh Laravel project:

```bash
composer create-project laravel/laravel laravel12-app
```

Start the development server:

```bash
php artisan serve
```

Open in browser:

```
http://127.0.0.1:8000
```

---

## 3. Database Configuration

Edit the `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

Create the database:

```sql
CREATE DATABASE laravel;
```

---

## 4. Install Laravel Telescope

Install Telescope using Composer:

```bash
composer require laravel/telescope
```

Publish Telescope assets and configuration:

```bash
php artisan telescope:install
```

Run database migrations:

```bash
php artisan migrate
```

---

## 5. Enable Telescope

Add this to `.env`:

```env
TELESCOPE_ENABLED=true
```

Verify `config/telescope.php`:

```php
'enabled' => env('TELESCOPE_ENABLED', true),
```

---

## 6. Telescope Service Provider Code

**File:** `app/Providers/TelescopeServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Optional dark mode
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {

            /* Ignore service worker request (/sw.js) */
            if (
                $entry->type === 'request' &&
                isset($entry->content['uri']) &&
                $entry->content['uri'] === 'sw.js'
            ) {
                return false;
            }

            /* LOCAL: log everything */
            if ($isLocal) {
                return true;
            }

            /* NON‑LOCAL: log only important entries */
            return
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    /**
     * Hide sensitive request data.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Telescope access gate (non‑local environments).
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return isset($user->email)
                && $user->email === 'admin@example.com';
        });
    }
}
```

---

## 7. Access Telescope Dashboard

Open the Telescope dashboard:

```
http://127.0.0.1:8000/telescope
```
<img width="1514" height="910" alt="Screenshot 2026-01-22 164704" src="https://github.com/user-attachments/assets/56408726-0892-4b12-9a07-45a6b97bbde9" />

<img width="1104" height="891" alt="Screenshot 2026-01-22 164646" src="https://github.com/user-attachments/assets/71c8b909-09eb-4036-99a2-9f09d77cc510" />


---

## 8. Clear Cache After Setup

Always run these commands after configuration changes:

```bash
php artisan optimize:clear
php artisan config:clear
```

---

## 9. Service Worker (`sw.js`) Handling (Important)

Sometimes Telescope shows repeated `GET /sw.js` requests with `404` status. This is **not a Laravel or Telescope bug**.

### Why this happens

* Modern browsers cache Service Workers
* A Service Worker was previously registered (PWA, Firebase, OneSignal, etc.)
* The browser keeps requesting `/sw.js` automatically

### Recommended Fix

Create a dummy service worker file.

**File path:** `public/sw.js`

```js
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('Service Worker activated');
});
```

This ensures:

* `/sw.js` returns **200 OK**
* Browser stops retrying
* Telescope remains clean

### Clear Browser Cache (One Time)

1. Open DevTools (`F12`)
2. Go to **Application → Service Workers**
3. Click **Unregister**
4. Hard refresh (`Ctrl + Shift + R`)

---

## 10. Understanding Empty Telescope Screen

If you see:

> "We didn’t find anything – just empty space"

This means:

* No errors or failed requests occurred
* Telescope filters are working correctly

To test, open a non‑existing route:

```
http://127.0.0.1:8000/invalid-route
```
<img width="1621" height="910" alt="Screenshot 2026-01-22 170454" src="https://github.com/user-attachments/assets/ddfe7d44-a634-4bf0-8848-a040976721e3" />


The request will appear in Telescope.

---

## 11. Production Best Practices

* Enable Telescope only in **local or staging** environments
* Disable Telescope in production

`.env (production)`:

```env
TELESCOPE_ENABLED=false
```

* Restrict access using the `gate()` method
* Never expose `/telescope` publicly

---

