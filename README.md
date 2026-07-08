# بردار (Bordar) — پلتفرم شتاب‌دهنده آنلاین

پلتفرمی برای مدیریت کامل چرخه پذیرش، ارزیابی، آموزش، منتورینگ و پرداخت شرکت‌های صادراتی. این نسخه با **PHP 8 خام** و **MySQL 8 / MariaDB** و بدون فریم‌ورک سنگین، برای سازگاری با هاست اشتراکی ساخته شده است.

## پیش‌نیازها

- PHP >= 8.1 با افزونه‌های `pdo_mysql`, `mbstring`, `openssl`
- MySQL 8.x یا MariaDB 10.11+
- Composer

## نصب

```bash
composer install
cp .env.example .env
# مقادیر DB_* را در .env مطابق دیتابیس خود تنظیم کنید

composer migrate   # اجرای migrationها
composer seed       # ایجاد نقش‌ها و کاربر ادمین اولیه
```

## اجرا (محیط توسعه)

```bash
php -S 127.0.0.1:8080 -t public public/router-dev.php
```

سپس به آدرس `http://127.0.0.1:8080` مراجعه کنید. برای استقرار روی هاست اشتراکی، محتویات پوشه `public/` باید document root سایت باشد (فایل `router-dev.php` فقط برای سرور توسعه PHP است و روی Apache/`.htaccess` استفاده نمی‌شود).

## اجرای تست‌ها

```bash
composer test
```

این دستور یک دیتابیس تست جدا (`bordar_test`، طبق `DB_TEST_DATABASE` در `.env`) را migrate کرده و سپس PHPUnit را اجرا می‌کند.

## معماری

- `public/` — Front controller (`index.php`) و فایل‌های استاتیک
- `src/Core/` — Router، Controller/Model پایه، Session، Auth، Middleware، ErrorHandler
- `src/Config/` — تنظیمات دیتابیس و اپلیکیشن
- `src/Features/{Feature}/` — هر قابلیت با ساختار Controllers/Models/Services/Views/Middleware مستقل (Feature-Sliced Design)
- `src/Lib/` — ابزارهای مشترک: `Sanitizer`, `Validator`, `DateConverter` (تاریخ شمسی)، `TwoFactor` (TOTP)، `Mailer`
- `src/Database/Migrations/` — اسکریپت‌های SQL نسخه‌بندی‌شده
- `tests/Unit`, `tests/Integration` — تست‌های PHPUnit

## فاز ۱ — راه‌اندازی و احراز هویت پایه

جزئیات کامل در [`PROGRESS.md`](PROGRESS.md).

کاربر ادمین پیش‌فرض (فقط محیط توسعه):

```
ایمیل: admin@bordar.local
رمز عبور: Admin@12345
```

> ورود ادمین نیازمند فعال‌سازی احراز هویت دومرحله‌ای (2FA/TOTP) در همان اولین ورود است.
