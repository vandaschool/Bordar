# بردار (Bordar) — پلتفرم شتاب‌دهنده آنلاین

پلتفرمی برای مدیریت کامل چرخه پذیرش، ارزیابی، آموزش، منتورینگ و پرداخت شرکت‌های صادراتی. این پلتفرم با **PHP 8 خام** و **MySQL 8 / MariaDB** و بدون فریم‌ورک سنگین، برای سازگاری با هاست اشتراکی ساخته شده است.

هر ۶ فاز تعریف‌شده در سند مستر پرامپت تکمیل شده‌اند: احراز هویت و امنیت پایه، مدیریت شرکت/کوهورت/درخواست، داوری و مخزن اسناد، پرداخت و Onboarding، مدیریت وظایف/منتورینگ/آموزش/پشتیبانی، و در نهایت بهینه‌سازی/امنیت/مستندسازی/آماده‌سازی استقرار. شرح کامل هر فاز (اقدامات، باگ‌های رفع‌شده، تست‌ها، مسیرها) در [`PROGRESS.md`](PROGRESS.md) ثبت شده است.

## امکانات

| حوزه | امکانات |
|---|---|
| احراز هویت | ثبت‌نام، تایید ایمیل با OTP، ورود/خروج، بازیابی رمز، **2FA اجباری (TOTP)** برای ادمین، محدودسازی نرخ تلاش‌های ورود ناموفق |
| چندمستأجری (Multi-tenancy) | ایزوله‌سازی کامل داده هر شرکت (`Core\Tenant`, `Model::findScoped/allScoped`) |
| شرکت و کوهورت | پروفایل شرکت با انتخاب‌گر HS Code، مدیریت کوهورت توسط ادمین با مهلت ثبت‌نام |
| درخواست پذیرش | فرم چندبخشی با Progressive Disclosure، Auto-save AJAX، نسخه‌بندی فرم |
| داوری | تخصیص داور، امتیازدهی Draft/Lock، تشخیص تعارض منافع (COI)، هشدار اختلاف امتیاز، شفاف‌سازی سه‌جانبه، Manual Override |
| مخزن اسناد | رمزنگاری AES-256 at-rest، اعتبارسنجی Magic Number (نه فقط پسوند/MIME)، لینک دانلود امضاشده زمان‌دار (HMAC-SHA256)، نسخه‌بندی سند |
| پرداخت | درگاه زرین‌پال (REST v4)، فیش واریز بانکی دستی، پرداخت اقساطی، Idempotency کامل |
| اعلان‌ها | Communication Matrix مرکزی (IN_APP / EMAIL / SMS) |
| Onboarding | Welcome Sequence، Guided Tour، چک‌لیست هفته اول |
| مدیریت وظایف | برد کانبان با Drag-and-Drop (دسکتاپ) و تب سوییچ‌شونده (موبایل) |
| منتورینگ | رزرو جلسه Timezone-safe، جلوگیری از Race Condition در سطح دیتابیس، ثبت خلاصه/Action Plan |
| آموزش (LMS) | دوره/درس متصل به کوهورت، انواع محتوای VIDEO/TEXT/QUIZ/EXTERNAL_LINK |
| پشتیبانی | تیکتینگ با یادداشت داخلی تیم، چرخه وضعیت خودکار |
| بومی‌سازی | تمام رابط کاربری فارسی/RTL، تاریخ شمسی (`Lib\DateConverter`)، موبایل-اول |

## پیش‌نیازها

- PHP >= 8.1 با افزونه‌های `pdo_mysql`, `mbstring`, `openssl`
- MySQL 8.x یا MariaDB 10.11+
- Composer

## نصب

```bash
composer install
cp .env.example .env
# مقادیر DB_* و سایر متغیرها را در .env مطابق محیط خود تنظیم کنید (جدول کامل در بخش پیکربندی)

composer migrate   # اجرای migrationها
composer seed       # ایجاد نقش‌ها و کاربر ادمین اولیه
```

## اجرا (محیط توسعه)

```bash
php -S 127.0.0.1:8080 -t public public/router-dev.php
```

سپس به آدرس `http://127.0.0.1:8080` مراجعه کنید. برای استقرار روی هاست اشتراکی، محتویات پوشه `public/` باید document root سایت باشد (فایل `router-dev.php` فقط برای سرور توسعه PHP است و روی Apache/`.htaccess` استفاده نمی‌شود). راهنمای کامل استقرار در [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## اجرای تست‌ها

```bash
composer test
```

این دستور یک دیتابیس تست جدا (`bordar_test`، طبق `DB_TEST_DATABASE` در `.env`) را migrate کرده و سپس PHPUnit را اجرا می‌کند.

## پیکربندی (`.env`)

| متغیر | توضیح |
|---|---|
| `APP_ENV`, `APP_DEBUG` | در production باید `APP_ENV=production` و `APP_DEBUG=false` باشد |
| `APP_KEY` | کلید رمزنگاری فایل‌ها (AES-256)؛ **پیش از استقرار باید یک مقدار تصادفی قوی تولید شود** — تغییر آن پس از آپلود فایل واقعی باعث غیرقابل بازیابی شدن فایل‌های رمزنگاری‌شده می‌شود |
| `DB_*` | اتصال دیتابیس اصلی و تست |
| `SESSION_LIFETIME`, `SESSION_NAME` | تنظیمات session |
| `STORAGE_PATH` | مسیر ذخیره فایل‌های رمزنگاری‌شده و ایمیل/پیامک شبیه‌سازی‌شده — باید **خارج از `public/`** و با دسترسی نوشتن برای PHP باشد |
| `MAIL_*` | تنظیمات SMTP واقعی؛ در نبود مقدار، `Lib\Mailer` ایمیل را در `STORAGE_PATH/mail` می‌نویسد (فقط توسعه) |
| `ZARINPAL_MERCHANT_ID`, `ZARINPAL_SANDBOX` | باید پیش از استقرار واقعی به مقدار merchant واقعی و `false` تنظیم شود |
| `SMS_API_KEY`, `SMS_SENDER` | در نبود مقدار، `Lib\SmsSender` در `STORAGE_PATH/sms` می‌نویسد (فقط توسعه) |
| `ADMIN_SEED_EMAIL`, `ADMIN_SEED_PASSWORD` | ایمیل/رمز ادمین اولیه هنگام seed؛ **باید پیش از استقرار production تنظیم یا بلافاصله بعد از seed تغییر کند** |

## معماری

- `public/` — Front controller (`index.php`) و فایل‌های استاتیک
- `src/Core/` — Router، Controller/Model پایه، Session، Auth، Tenant، Middleware، ErrorHandler، AuditLog، Notifier
- `src/Config/` — تنظیمات دیتابیس و اپلیکیشن
- `src/Features/{Feature}/` — هر قابلیت با ساختار Controllers/Models/Services/Views/Middleware مستقل (Feature-Sliced Design): `Auth`, `Company`, `Cohort`, `Application`, `Review`, `Document`, `Payment`, `Onboarding`, `Task`, `Mentor`, `LMS`, `SupportTicket`
- `src/Lib/` — ابزارهای مشترک: `Sanitizer`, `Validator`, `DateConverter` (تاریخ شمسی)، `TwoFactor` (TOTP)، `Mailer`, `FileEncryptor`, `FileUploader`, `SignedUrl`, `ZarinpalClient`, `SqlSplitter`
- `src/Database/Migrations/` — اسکریپت‌های SQL نسخه‌بندی‌شده (اجرا با `src/Database/migrate.php`)
- `tests/Unit`, `tests/Integration` — تست‌های PHPUnit

## نقش‌ها و دسترسی‌ها (RBAC)

نقش‌ها در جدول `roles` با ستون `permissions` (JSON) تعریف می‌شوند و توسط `Core\Auth::can()`/middlewareهای نقش-محور اعمال می‌شوند.

| نقش | دسترسی خلاصه |
|---|---|
| Admin | دسترسی کامل (`*`) به تمام بخش‌ها شامل مدیریت کوهورت/داور/پرداخت/آموزش/پشتیبانی؛ الزام 2FA |
| Applicant | مشاهده/ویرایش درخواست و اسناد خودش، مشاهده وضعیت پرداخت، رزرو جلسه منتورینگ، ثبت/مشاهده تیکت خودش |
| Mentor | مشاهده درخواست/اسناد تخصیص‌یافته، مدیریت زمان‌های در دسترس، مشاهده/پاسخ به صف مشترک تیکت |
| Reviewer | مشاهده درخواست/اسناد تخصیص‌یافته، امتیازدهی |
| Vendor / Investor | نقش‌های رزروشده برای توسعه آینده (بدون دسترسی فعال در MVP) |

## امنیت

- تمام کوئری‌های دیتابیس با PDO Prepared Statements (بدون هیچ query خام با ورودی کاربر)
- CSRF token روی همه فرم‌های POST/PUT (`Session::csrfToken` + `Controller::requireCsrf`)
- خروجی HTML همیشه با `View::e()` escape می‌شود؛ لینک‌های ذخیره‌شده (ویدیو/لینک خارجی LMS) فقط با scheme معتبر `http(s)` پذیرفته می‌شوند
- محدودسازی نرخ ورود: پس از تلاش‌های ناموفق مکرر از یک IP، ورود موقتاً مسدود می‌شود (بدون افشای اینکه ایمیل ثبت‌نام شده یا نه)
- رمزنگاری فایل At-Rest (AES-256-CBC) + اعتبارسنجی Magic Number فایل آپلودی + لینک دانلود امضاشده و زمان‌دار
- 2FA اجباری (TOTP) برای نقش Admin
- Soft Delete روی جداول اصلی؛ Audit Log کامل برای رویدادهای حساس

## کاربر ادمین پیش‌فرض (فقط محیط توسعه)

```
ایمیل: admin@bordar.local
رمز عبور: Admin@12345
```

> ورود ادمین نیازمند فعال‌سازی احراز هویت دومرحله‌ای (2FA/TOTP) در همان اولین ورود است. پیش از استقرار production این مقادیر را با `ADMIN_SEED_EMAIL`/`ADMIN_SEED_PASSWORD` در `.env` تغییر دهید یا بلافاصله بعد از seed رمز را عوض کنید.

## مستندات بیشتر

- [`PROGRESS.md`](PROGRESS.md) — شرح کامل هر فاز توسعه (اقدامات، باگ‌های رفع‌شده، تست‌ها، مسیرهای API)
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — راهنمای استقرار روی هاست اشتراکی
