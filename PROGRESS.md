# PROGRESS.md

## فاز ۱: راه‌اندازی پروژه و سیستم احراز هویت پایه — ✅ تکمیل‌شده

تاریخ: 2026-07-08

### خلاصه اقدامات

- اسکلت پروژه مطابق ساختار Feature-Sliced بخش ۳ سند مستر پرامپت ایجاد شد (`public/`, `src/Config`, `src/Core`, `src/Database`, `src/Features`, `src/Lib`, `tests/`).
- Composer با `vlucas/phpdotenv` (متغیرهای محیطی)، `morilog/jalali` (تبدیل تاریخ شمسی) و `phpunit/phpunit` (تست) پیکربندی شد.
- هسته فریم‌ورک سفارشی نوشته شد: `Router` (پشتیبانی از پارامتر مسیر، گروه‌بندی، Middleware، method override)، `Controller`/`Model` پایه (با Soft Delete و UUID خودکار)، `View` (رندر با Layout)، `Session` (کوکی امن، چرخش session id، CSRF token)، `Auth` (RBAC مبتنی بر جدول `roles.permissions`)، `Middleware` پایه، `Request`/`Response`، `ErrorHandler` سراسری (JSON یا صفحه خطا بسته به نوع درخواست).
- دیتابیس: Migrationهای SQL نسخه‌دار (`001`..`005`) برای `roles`, `users`, `user_sessions`, `otp_codes` (پشتیبانی OTP ایمیل)، `audit_logs`. یک migration runner ساده (`src/Database/migrate.php`) با جدول ردیابی `migrations` نوشته شد. Seeder نقش‌های پیش‌فرض (Admin با `*`، Applicant، Mentor، Reviewer، Vendor، Investor طبق ماتریس RBAC بخش ۷) و یک کاربر Admin اولیه ایجاد می‌کند.
- سیستم احراز هویت کامل در `src/Features/Auth/`:
  - ثبت‌نام با `password_hash` (bcrypt)، اعتبارسنجی سرور با `Lib\Validator` (کلاس ولیدیشن مرکزی سفارشی).
  - تایید ایمیل با کد OTP ۶ رقمی (منقضی‌شونده، حداکثر ۵ تلاش، هش‌شده در دیتابیس). در نبود SMTP واقعی، `Lib\Mailer` ایمیل را در `STORAGE_PATH/mail` می‌نویسد و در `APP_DEBUG=true` کد را برای تست به صفحه flash می‌کند.
  - ورود/خروج با session امن (rotation دوره‌ای session id، کوکی HttpOnly/SameSite).
  - فراموشی/بازیابی رمز عبور با همان مکانیزم OTP.
  - **2FA اجباری برای نقش Admin**: پیاده‌سازی کامل TOTP (RFC 6238) بدون وابستگی خارجی در `Lib\TwoFactor`. اولین ورود ادمین به `/2fa/setup` هدایت می‌شود (نمایش secret + otpauth URI)، ورودهای بعدی به `/2fa/verify`. `AdminMiddleware` دسترسی به مسیرهای مدیریتی را تا زمانی که `2fa_passed` در session ثبت نشده مسدود می‌کند.
  - Middlewareهای `GuestMiddleware`, `AuthMiddleware`, `AdminMiddleware`.
  - محافظت CSRF روی همه فرم‌های POST (`Session::csrfToken` + `Controller::requireCsrf`).
  - Sanitization ورودی در لایه `Request`/`Sanitizer` (حذف کاراکترهای کنترلی، escape خروجی با `View::e`).
  - Audit Log برای: ثبت‌نام، تایید ایمیل، تغییر رمز، فعال‌سازی 2FA، ورود موفق/ناموفق (`Core\AuditLog`).
- بومی‌سازی پایه: کل UI فارسی/راست‌به‌چپ (`dir="rtl"`)؛ کلاس CSS `.ltr` برای ایزوله کردن اعداد/ایمیل/کد OTP از جهت RTL؛ `Lib\DateConverter` به‌عنوان wrapper واحد تبدیل UTC↔شمسی برای همه لایه‌ها (دیتابیس همیشه UTC، نمایش با تایم‌زون `Asia/Tehran`).
- CSS مستقل (بدون وابستگی به CDN) با تست استرس دستی در ابعاد موبایل ۳۹۰px (اسکرین‌شات‌های `docs/screenshots/`) — بدون اسکرول افقی ناخواسته.
- تست‌ها: ۳۰ تست PHPUnit (Unit: `TwoFactor`, `Validator`, `Sanitizer`, `DateConverter`, `Session`؛ Integration: `AuthServiceTest` روی دیتابیس تست جدا `bordar_test` — ثبت‌نام، صدور/تایید/عدم‌استفاده‌مجدد OTP، بازیابی رمز، Soft Delete). همه ۳۰ تست پاس (۵۳ assertion).
- تست End-to-End دستی روی HTTP واقعی (`php -S` + curl): ثبت‌نام → تایید ایمیل → داشبورد؛ ورود ادمین → اجبار 2FA setup → داشبورد؛ خروج → ورود مجدد ادمین → درخواست مستقیم 2FA verify (نه setup)؛ فراموشی/بازیابی رمز → ورود با رمز جدید؛ صفحه ۴۰۴؛ ریدایرکت GuestMiddleware برای کاربر لاگین‌شده.

### فایل‌های کلیدی ساخته‌شده

```
public/index.php, public/.htaccess, public/router-dev.php, public/assets/css/app.css
src/bootstrap.php, src/routes.php
src/Config/{App,Database,Constants}.php
src/Core/{Router,Controller,Model,View,Session,Auth,Middleware,Request,Response,ErrorHandler,AuditLog}.php
src/Lib/{Sanitizer,Validator,DateConverter,TwoFactor,Mailer}.php
src/Database/migrate.php
src/Database/Migrations/001..005_*.sql
src/Database/Seeders/run.php
src/Features/Auth/Models/{User,Role,OtpCode}.php
src/Features/Auth/Services/AuthService.php
src/Features/Auth/Controllers/{AuthController,TwoFactorController}.php
src/Features/Auth/Middleware/{GuestMiddleware,AuthMiddleware,AdminMiddleware}.php
src/Features/Auth/Views/{register,login,verify-email,forgot-password,reset-password,2fa-setup,2fa-verify}.php
src/Features/User/Controllers/DashboardController.php
src/Features/User/Views/dashboard.php
src/Views/{home,layout/layout,errors/404,errors/500}.php
tests/bootstrap.php, tests/setup-test-db.php
tests/Unit/{TwoFactorTest,ValidatorTest,SanitizerTest,DateConverterTest,SessionTest}.php
tests/Integration/AuthServiceTest.php
```

### تست‌های پاس‌شده

- `composer test` → **30/30 passed, 53 assertions**.
- Lint: `php -l` روی همه فایل‌های `src/`, `public/`, `tests/` بدون خطا.
- تست دستی مسیرهای اصلی HTTP (بالا) موفق.

### مسیرهای اصلی (فاز ۱)

| مسیر | توضیح |
|---|---|
| `GET /` | لندینگ عمومی |
| `GET,POST /register` | ثبت‌نام (نقش پیش‌فرض Applicant) |
| `GET,POST /verify-email`, `POST /verify-email/resend` | تایید ایمیل با OTP |
| `GET,POST /login`, `POST /logout` | ورود/خروج |
| `GET,POST /forgot-password`, `GET,POST /reset-password` | بازیابی رمز با OTP |
| `GET,POST /2fa/setup`, `GET,POST /2fa/verify` | 2FA اجباری ادمین |
| `GET /dashboard` | داشبورد کاربر (نیازمند ورود) |

### نکات برای فازهای بعدی

- جدول‌های `companies`, `cohorts`, `applications` و بقیه مدل داده (بخش ۵ سند) هنوز پیاده‌سازی نشده‌اند؛ فاز ۲ آن‌ها را اضافه می‌کند. Multi-tenancy middleware (`companyId`/`cohortId`) نیز از فاز ۲ به بعد پیاده می‌شود چون تا اینجا هیچ جدولی این ستون‌ها را ندارد.
- `Lib\Mailer` فعلاً یک stub است (می‌نویسد به `storage/mail`)؛ اتصال SMTP واقعی هنگام استقرار باید در `.env` تنظیم و در صورت نیاز کلاینت SMTP واقعی جایگزین شود.
- کاربر ادمین seed‌شده رمز پیش‌فرض دارد؛ پیش از استقرار production باید `ADMIN_SEED_EMAIL`/`ADMIN_SEED_PASSWORD` در `.env` تنظیم یا بلافاصله بعد از seed رمز عوض شود.

### کامیت

تغییرات این فاز طی یک کامیت با پیام مرتبط ثبت و به شاخه `claude/untitled-session-s0fr4t` push شد.
