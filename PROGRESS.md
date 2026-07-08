# PROGRESS.md

## فاز ۳: سیستم داوری و مدیریت اسناد — ✅ تکمیل‌شده

تاریخ: 2026-07-08

### خلاصه اقدامات

- Migrationهای `010`..`012` برای `application_reviewers` (کلید ترکیبی application_id+reviewer_user_id، با فیلد اضافه `status ENUM('ASSIGNED','DRAFT','SUBMITTED')` برای پیاده‌سازی Draft/Lock)، `documents` (نسخه‌بندی زنجیره‌ای با `replaces_document_id`، رمزنگاری، checksum)، `application_clarifications` (ارتباط سه‌جانبه داور↔متقاضی).
- **رفع باگ زیرساختی مهم**: در `Core\View::renderRaw()`، پارامتر متد به اشتباه هم‌نام `$data` بود؛ چون چند ویو (فرم درخواست پذیرش، صفحه داور) دقیقاً کلیدی به نام `data` به `render()` پاس می‌دادند، `extract(..., EXTR_SKIP)` بازنویسی آن را رد می‌کرد و مقادیر واقعی فرم هرگز در HTML درج نمی‌شدند (فرم که در فاز ۲ ساخته شده بود از این باگ رنج می‌برد ولی چون فقط رفتار submit/autosave تست شده بود، نه محتوای واقعی فیلدها، کشف نشده بود). با تغییر نام پارامتر داخلی به `$viewData` رفع شد و با تست HTTP واقعی تایید شد که هم فرم متقاضی و هم صفحه داور اکنون مقادیر را نمایش می‌دهند.
- **سیستم داوری** (`src/Features/Review/`):
  - تخصیص داور توسط ادمین (`ReviewAdminController`) با نمایش بار کاری فعال هر داور (Capacity Management) و تبدیل خودکار وضعیت درخواست از `SUBMITTED` به `UNDER_REVIEW`.
  - امتیازدهی داور با Draft (`status=DRAFT`, قابل ویرایش) و قفل نهایی (`status=SUBMITTED`, غیرقابل ویرایش پس از ثبت — تایید شده با تست‌های Unit/Integration و E2E).
  - **تعارض منافع**: داور می‌تواند خود را از یک درخواست به‌عنوان COI علامت بزند؛ امتیاز و بار کاری او در آن درخواست از محاسبات میانگین/ظرفیت حذف می‌شود.
  - **مکانیزم حل اختلاف امتیاز**: `ReviewService::scoreSummary()` میانگین امتیازهای غیر-COI را محاسبه و در صورت اختلاف ≥ ۳ نمره بین داوران، پرچم هشدار (`hasVariance`) در پنل ادمین نمایش می‌دهد.
  - **ارتباط سه‌جانبه (Clarification)**: داور می‌تواند از متقاضی شفاف‌سازی بخواهد (وضعیت درخواست → `PENDING_INFO`)؛ متقاضی در همان صفحه فرم پاسخ می‌دهد و پس از پاسخ به همه سوالات باز، وضعیت خودکار به `UNDER_REVIEW` بازمی‌گردد.
  - **Manual Override**: ادمین در هر مرحله می‌تواند وضعیت نهایی درخواست را دستی تغییر دهد (با ثبت دلیل در Audit Log)؛ تصمیم ACCEPTED/REJECTED به‌صورت خودکار در جدول محوری `company_cohorts` همگام‌سازی می‌شود (این جدول پیش‌تر در فاز ۲ بدون استفاده مانده بود).
- **Document Vault** (`src/Features/Document/`):
  - رمزنگاری At-Rest با AES-256-CBC (`Lib\FileEncryptor`, IV تصادفی به‌ازای هر فایل) — تایید شد که بایت‌های ذخیره‌شده روی دیسک با محتوای اصلی فایل کاملاً متفاوتند.
  - **تایید هدر فایل (Magic Numbers)** در `Lib\FileUploader`: فرمت واقعی فایل (نه فقط پسوند/MIME اعلامی) با امضای باینری تطبیق داده می‌شود؛ یک فایل PHP مخرب با پسوند جعلی `.pdf` هم در تست واحد و هم در تست HTTP واقعی رد شد.
  - **Signed URLs**: هر لینک دانلود با HMAC-SHA256 امضا و زمان‌دار می‌شود (`Lib\SignedUrl`، پیش‌فرض ۵ دقیقه)؛ لینک دستکاری‌شده یا منقضی، حتی برای کاربر مجاز، ۴۰۳ برمی‌گرداند.
  - **کنترل دسترسی چندلایه** (`DocumentService::canAccess`، به‌صورت تابع خالص و مستقل از session برای تست‌پذیری کامل): مالک شرکت، داور تخصیص‌یافته (به‌جز در صورت COI)، یا ادمین — تایید شده با تست‌های Integration و همچنین سناریوی واقعی HTTP (شرکت نامرتبط → ۴۰۳).
  - نسخه‌بندی سند با زنجیره `replaces_document_id` + `version`؛ `Document::forCompany()` فقط آخرین نسخه هر سند را برمی‌گرداند.
  - مدیریت تاریخ انقضا (`expires_at`, نمایش شمسی، پرچم «منقضی‌شده» در UI).
- **Audit Log**: تمام رویدادهای کلیدی این فاز ثبت می‌شوند: `review.reviewer_assigned/unassigned`, `review.conflict_of_interest`, `review.submitted`, `review.clarification_requested/responded`, `application.manual_override`, `document.uploaded/downloaded/deleted`.
- ادغام UI: صفحه داور و صفحه ادمین هر دو لیست اسناد شرکت متقاضی را (با لینک دانلود امضاشده) نمایش می‌دهند تا داوری بدون جابه‌جایی بین بخش‌ها انجام شود.

### تست‌های پاس‌شده

- `composer test` → **85/85 passed, 151 assertions** (۳۸ تست جدید نسبت به فاز ۲: `FileEncryptorTest`, `FileUploaderTest`, `SignedUrlTest` در Unit؛ `ReviewServiceTest`, `DocumentServiceTest` در Integration — شامل رد فایل جعل‌شده، رمزگشایی صحیح، انقضای لینک امضاشده، انزوای دسترسی بین شرکت‌ها/داوران، قفل شدن امتیاز، تشخیص اختلاف امتیاز، همگام‌سازی company_cohorts).
- Lint: `php -l` روی همه فایل‌های `src/`, `public/`, `tests/` بدون خطا.
- تست End-to-End کامل روی HTTP واقعی با MariaDB: ادمین داور تخصیص می‌دهد (وضعیت → UNDER_REVIEW) → داور Draft ذخیره می‌کند → داور درخواست شفاف‌سازی می‌فرستد (وضعیت → PENDING_INFO) → متقاضی پاسخ می‌دهد (وضعیت → UNDER_REVIEW) → داور امتیاز نهایی را قفل می‌کند (تلاش دوباره برای ثبت مسدود شد) → ادمین Manual Override به ACCEPTED (company_cohorts هم‌زمان به‌روزرسانی شد) → آپلود PDF واقعی (رمزنگاری تایید شد با بررسی مستقیم بایت‌های دیسک) → آپلود فایل مخرب با پسوند جعلی رد شد → دانلود با لینک امضاشده محتوای صحیح را برگرداند → دسترسی شرکت نامرتبط با ۴۰۳ مسدود شد، دسترسی داور تخصیص‌یافته و ادمین مجاز بود.
- بررسی بصری موبایل (۳۹۰px) با اسکرین‌شات Playwright برای صفحه داور و مخزن اسناد؛ بدون اسکرول افقی.

### مسیرهای اصلی (فاز ۳)

| مسیر | توضیح |
|---|---|
| `GET,POST /admin/reviews`, `GET /admin/reviews/{id}` | صف داوری و جزئیات (ادمین) |
| `POST /admin/reviews/{id}/assign|unassign/{reviewerUserId}|override` | تخصیص/لغو/تصمیم نهایی |
| `GET /reviewer/queue`, `GET /reviewer/applications/{id}` | صف و جزئیات داور |
| `POST /reviewer/applications/{id}/draft|submit|conflict|clarify` | امتیازدهی/COI/شفاف‌سازی |
| `POST /applications/{id}/clarifications/{clarificationId}/respond` | پاسخ متقاضی به شفاف‌سازی |
| `GET,POST /documents`, `POST /documents/{id}/delete` | مخزن اسناد شرکت |
| `GET /documents/{id}/download` | دانلود با لینک امضاشده زمان‌دار |

### نکات برای فازهای بعدی

- ماژول پرداخت (فاز ۴) باید فرآیند Onboarding را بعد از `ACCEPTED` شدن شروع کند؛ فیلد `company_cohorts.status='ACCEPTED'` اکنون منبع قابل‌اتکایی برای این تصمیم است.
- سیستم اعلان چندکاناله (Email/SMS) هنوز پیاده نشده؛ رویدادهای مهم این فاز (تخصیص داور، درخواست شفاف‌سازی، تصمیم نهایی) فعلاً فقط از طریق Audit Log و تغییر وضعیت قابل مشاهده‌اند، نه اعلان فعال به کاربر.
- `documents.status` (PENDING/APPROVED/REJECTED) هنوز توسط هیچ UI‌ای تغییر داده نمی‌شود؛ اگر تایید صریح سند توسط ادمین/داور لازم شود باید در فاز بعد اضافه شود.

### کامیت

تغییرات این فاز طی یک کامیت با پیام مرتبط ثبت و به شاخه `claude/untitled-session-s0fr4t` push شد.

---

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

- `Lib\Mailer` فعلاً یک stub است (می‌نویسد به `storage/mail`)؛ اتصال SMTP واقعی هنگام استقرار باید در `.env` تنظیم و در صورت نیاز کلاینت SMTP واقعی جایگزین شود.
- کاربر ادمین seed‌شده رمز پیش‌فرض دارد؛ پیش از استقرار production باید `ADMIN_SEED_EMAIL`/`ADMIN_SEED_PASSWORD` در `.env` تنظیم یا بلافاصله بعد از seed رمز عوض شود.

### کامیت

تغییرات این فاز طی یک کامیت با پیام مرتبط ثبت و به شاخه `claude/untitled-session-s0fr4t` push شد.

---

## فاز ۲: مدیریت شرکت‌ها، کوهورت‌ها و فرم درخواست پذیرش — ✅ تکمیل‌شده

تاریخ: 2026-07-08

### خلاصه اقدامات

- Migrationهای `006`..`009` برای `companies` (با ستون اضافه `hs_codes JSON` برای پوشش قابلیت MVP «ویرایش کدهای کالایی» که در schema بخش ۵ نیامده بود)، `cohorts`، `company_cohorts`، `applications`.
- **زیرساخت Multi-Tenancy**: `Core\Tenant` شرکت متعلق به کاربر لاگین‌شده را (بر مبنای `companies.owner_user_id`) resolve و کش می‌کند؛ `Core\Model::findScoped()/allScoped()` امکان کوئری تضمین‌شده در محدوده یک `company_id` را فراهم می‌کنند (حتی اگر id رکورد لو برود، بدون تطابق company_id چیزی برنمی‌گردد)؛ `RequireCompanyMiddleware` قبل از دسترسی به مسیرهای Application، وجود پروفایل شرکت را برای نقش‌های غیر Admin اجباری می‌کند.
- **Company** (`src/Features/Company/`): ایجاد/مشاهده/ویرایش پروفایل شرکت (یک شرکت به ازای هر owner)، کامپوننت Dropdown درختی و تعاملی برای HS Codes (`Lib\HsCodes` + `public/assets/js/hs-tree.js`: جستجوی زنده، انتخاب گروهی هر فصل، بدون وابستگی خارجی).
- **Cohort** (`src/Features/Cohort/`): CRUD کامل توسط ادمین (`AdminMiddleware`) با تاریخ‌های شمسی در UI (`DateConverter::fromJalali/toJalaliDate`) و ذخیره UTC در دیتابیس؛ `Cohort::openForApplications()` کوهورت‌های در دسترس برای ثبت درخواست (وضعیت UPCOMING/ACTIVE و قبل از مهلت ثبت‌نام) را برمی‌گرداند.
- **Application** (`src/Features/Application/`):
  - فرم با **Progressive Disclosure** (بخش‌های تاشوی `<details>`: معرفی کسب‌وکار، بازار هدف، تیم، اهداف).
  - **Auto-save با AJAX** (`public/assets/js/application-autosave.js`، debounce 1200ms، ارسال JSON با هدر `X-CSRF-Token`) که فقط فیلدهای تغییریافته را merge می‌کند و placeholder فیلدهای دیگر را دست‌نخورده نگه می‌دارد.
  - **Form Versioning**: ستون `applications.version` با هر autosave افزایش می‌یابد؛ کلید `_form_version` داخل JSON `data` نسخه schema فرم را برای سازگاری با تغییرات آینده فرم ثبت می‌کند.
  - اعتبارسنجی سرور فیلدهای الزامی پیش از ارسال نهایی (`ApplicationService::missingFields/submit`)؛ UI هم به‌صورت Real-time (بدون رفرش صفحه) دکمه ارسال را بر اساس تکمیل فیلدها نمایش/مخفی می‌کند.
  - پس از `SUBMITTED`، فرم read-only می‌شود (نه در سرور قابل autosave و نه در UI قابل ویرایش).
  - صفحه لیست درخواست‌های هر شرکت (`/applications`) با نمایش وضعیت و آخرین ویرایش (تاریخ شمسی).
- داشبورد بر اساس نقش کاربر میانبرهای مرتبط (مدیریت کوهورت برای Admin؛ پروفایل شرکت/درخواست‌ها برای Applicant) را نمایش می‌دهد.

### فایل‌های کلیدی ساخته‌شده

```
src/Database/Migrations/006..009_*.sql
src/Core/Tenant.php (+ Model::findScoped/allScoped)
src/Lib/HsCodes.php
public/assets/js/{hs-tree,application-autosave}.js
src/Features/Company/{Models/Company,Services/CompanyService,Controllers/CompanyController,Middleware/RequireCompanyMiddleware}.php
src/Features/Company/Views/{create,edit,show,_hs-tree}.php
src/Features/Cohort/{Models/Cohort,Services/CohortService,Controllers/CohortController}.php
src/Features/Cohort/Views/{admin-index,admin-form}.php
src/Features/Application/{Models/Application,Services/ApplicationService,Controllers/ApplicationController}.php
src/Features/Application/Views/{index,choose-cohort,form}.php
tests/Unit/HsCodesTest.php
tests/Integration/{CompanyServiceTest,CohortServiceTest,ApplicationServiceTest}.php
```

### تست‌های پاس‌شده

- `composer test` → **47/47 passed, 92 assertions** (۱۷ تست جدید نسبت به فاز ۱، شامل ایزوله‌سازی بین شرکت‌ها با `findScoped`، افزایش نسخه در autosave، رد autosave روی درخواست غیر-DRAFT، رد submit با فیلد ناقص).
- Lint: `php -l` روی همه فایل‌های `src/`, `public/`, `tests/` بدون خطا.
- تست End-to-End دستی روی HTTP واقعی: ثبت‌نام متقاضی → تکمیل پروفایل شرکت با انتخاب HS Code → ادمین کوهورت می‌سازد → متقاضی کوهورت باز را می‌بیند → شروع پیش‌نویس → دو فراخوانی AJAX autosave (نسخه ۲ سپس ۳) → دکمه ارسال به‌صورت خودکار (بدون رفرش) ظاهر شد → ارسال نهایی → صفحه read-only؛ آزمایش ایزولاسیون: متقاضی دیگر بدون شرکت با ریدایرکت به `/company/create` مسدود شد و پس از ساخت شرکت خودش، تلاش برای دیدن `applications/{id}` شرکت اول با ریدایرکت امن به `/applications` (نه دسترسی/افشای داده) مسدود شد.
- بررسی بصری موبایل (۳۹۰px) با اسکرین‌شات Playwright برای صفحه ویرایش شرکت (درخت HS Code) و لیست درخواست‌ها؛ بدون اسکرول افقی.

### مسیرهای اصلی (فاز ۲)

| مسیر | توضیح |
|---|---|
| `GET /company/create`, `POST /company` | ایجاد پروفایل شرکت |
| `GET /company`, `GET /company/edit`, `PUT /company` | مشاهده/ویرایش پروفایل شرکت |
| `GET,POST /admin/cohorts`, `GET /admin/cohorts/{id}/edit`, `PUT /admin/cohorts/{id}` | مدیریت کوهورت (ادمین) |
| `GET /applications`, `GET,POST /applications/start` | لیست و شروع درخواست |
| `GET /applications/{id}` | فرم/مشاهده درخواست |
| `POST /applications/{id}/autosave` | ذخیره خودکار (AJAX) |
| `POST /applications/{id}/submit` | ارسال نهایی |

### نکات برای فازهای بعدی

- جدول `application_reviewers` و کل سیستم داوری (فاز ۳) هنوز اضافه نشده‌اند.
- `company_cohorts` (وضعیت شرکت در هر کوهورت: APPLIED/ACCEPTED/...) هنوز در جریان استفاده نمی‌شود؛ فاز ۳ (پذیرش/رد) باید آن را همگام با تغییر وضعیت `applications` به‌روزرسانی کند.
- `Lib\HsCodes` فقط یک زیرمجموعه نمایشی از نامگذاری HS است (نه فهرست کامل رسمی)؛ در صورت نیاز به فهرست کامل، فقط این فایل باید جایگزین شود، کد صدا‌زننده تغییر نمی‌کند.
- مدل «یک شرکت به ازای هر owner» (بدون جدول کاربران-چندگانه شرکت) از schema بخش ۵ پیروی می‌کند؛ اگر فازهای بعد نیاز به چند کاربر روی یک شرکت داشته باشند، باید جدول واسط اضافه شود.

### کامیت

تغییرات این فاز طی یک کامیت با پیام مرتبط ثبت و به شاخه `claude/untitled-session-s0fr4t` push شد.
