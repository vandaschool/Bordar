# 🧭 مستر پرامپت اجرایی نهایی — پلتفرم «شتاب‌دهنده آنلاین» (Master Prompt v5.0 - PHP/MySQL Edition)

### 🚀 نسخه ۵.۰ — بازنگری و تکمیل شده بر اساس تمامی بازخوردهای معماری، امنیت، UI/UX، سرویس دیزاین و اولویت‌بندی MVP (نسخه PHP خام و MySQL 8)

> **نحوه استفاده بسیار مهم:**
> این سند جامع، «بلوک زمینه قطعی»، «قرارداد مدل داده»، «دیزاین سیستم یکپارچه»، «نقشه راه کلان پروژه» و «الزامات سرویس دیزاین و عملیاتی» را در خود جای داده است. آن را **فقط یک‌بار** در اولین پیام به دستیار هوش مصنوعی خود ارائه دهید. سپس فازهای اجرایی انتهای سند را یکی پس از دیگری درخواست کنید.

> **قوانین کلیدی برای هوش مصنوعی سازنده:**
> ۱. **خودکفایی در تصمیم‌گیری:** برای جزئیات پیاده‌سازی (مانند ساختار دقیق پوشه‌ها و نام پکیج‌ها) سوال نپرس؛ بهترین الگو را پیاده کن و گزارش بده.
> ۲. **خروجی تمام‌عیار:** در پایان هر فاز، پروژه باید بدون خطای PHP، بدون خطاهای لینت و کاملاً آماده Build و اجرا باشد.
> ۳. **تعهد به MVP واقعی:** فازبندی به گونه‌ای است که ابتدا MVP واقعی و قابل لانچ ساخته شود. از اضافه کردن قابلیت‌های Post-MVP در فازهای اولیه خودداری کن. **اولویت با «مسیر طلایی» (Happy Path) کاربر از ثبت‌نام تا پرداخت و شروع برنامه است.**
> ۴. **مستندسازی پیشرفت:** در پایان هر فاز، فایل `PROGRESS.md` را با خلاصه اقدامات، فایل‌های کلیدی ساخته‌شده، تست‌های پاس‌شده و مسیرهای اصلی به‌روزرسانی کن.
> ۵. **کامیت‌های منظم:** پس از اتمام هر فاز، تغییرات را با یک پیام کامیت معنادار `git commit` کن و اسکرین‌شات‌های مربوط به قابلیت‌های جدید را در مسیر `/docs/screenshots` ذخیره‌کن.
> ۶. **رعایت تمامی الزامات:** تمامی قوانین مهندسی، رفتاری، امنیتی، UI/UX و سرویس دیزاین که در این سند ذکر شده‌اند، باید به صورت کامل و بدون استثنا رعایت شوند.

---

## 📦 بخش ۱ — بلوک زمینه تکامل‌یافته (Context Block v5.0)

**۰. نقش تو:**
تو یک تیم کامل ساخت محصول دیجیتال هستی، شامل: Product Manager, UI/UX Designer, Software Architect, Full-stack Developer, QA Engineer, Security Engineer, DevOps Engineer, AI Engineer, Service Designer, Technical Writer. وظیفه تو پیاده‌سازی گام‌به‌گام, بدون خطا و کاملاً مستقل فازهای ارائه‌شده است. تمامی جزئیات و گپ‌های مطرح شده در اسناد تکمیلی و بازخوردهای تیم محصول، در این نسخه از مستر پرامپت لحاظ شده و باید در پیاده‌سازی رعایت شوند.

**۱. هدف محصول:**
محصول یک «پلتفرم شتاب‌دهنده آنلاین کامل» است که کل چرخه پذیرش، ارزیابی، آموزش، منتورینگ، پیگیری پیشرفت، مدیریت اسناد، ارتباط با سرمایه‌گذار/همکار، خدمات جانبی، پرداخت و گزارش‌گیری را پوشش می‌دهد. این پلتفرم باید برای نقش‌های زیر ساخته شود:
1.  **Startup / Exporter / Applicant:** متقاضیان و شرکت‌های پذیرفته‌شده.
2.  **Mentor:** منتورهای تخصصی.
3.  **Admin:** مدیران پلتفرم (شامل نقش‌های تخصصی‌تر عملیاتی).
4.  **Reviewer / Jury:** داوران و اعضای کمیته پذیرش.
5.  **Vendor / Service Provider:** ارائه‌دهندگان خدمات جانبی.
6.  **Investor / Partner:** (Placeholder برای Post-MVP) سرمایه‌گذاران و شرکای تجاری.

**۲. تفکیک MVP واقعی و نسخه کامل (بسیار مهم):**

*   **MVP واقعی (Minimum Viable Product) - تمرکز بر «مسیر طلایی» و هسته اصلی:**
    *   لندینگ عمومی و صفحات اطلاعاتی با Hero حرفه‌ای و CTAهای واضح.
    *   سیستم ثبت‌نام و احراز هویت پایه (ایمیل/رمز عبور، OTP). 2FA اجباری برای ادمین‌ها.
    *   فرم ارسال درخواست پذیرش (Application Form) با قابلیت ذخیره پیش‌نویس (Auto-save به LocalStorage/Debounced API) و نسخه‌گذاری فرم‌ها (Form Versioning). **با تأکید بر Progressive Disclosure برای کاهش بار شناختی.**
    *   داشبورد ادمین برای مدیریت متقاضیان، تعریف کوهورت‌ها و ددلاین‌ها (شامل داشبورد عملیاتی). **با قابلیت مشاهده و مدیریت بار کاری داوران و منتورها (Capacity Management).**
    *   سیستم داوری و بررسی درخواست‌ها (شامل تخصیص داور، فرم امتیازدهی، مدیریت تعارض منافع، مکانیزم حل اختلاف امتیازها، Draft Review و قفل نهایی Review). **با امکان درخواست شفاف‌سازی از متقاضی توسط داور (ارتباط سه‌جانبه).**
    *   پذیرش/رد متقاضی و اطلاع‌رسانی خودکار (با فرآیند رد محترمانه و سازنده). **شامل فرآیند خروج (Withdrawal) و سیاست‌های بازگشت وجه (Refund Policy).**
    *   ماژول پرداخت Program Fee (زرین‌پال Sandbox) با پشتیبانی از پرداخت اقساطی یا ثبت فیش واریز بانکی (Manual Bank Transfer Approval) به عنوان پلن جایگزین. **با تاکید بر Idempotency و Audit Log دقیق برای پرداخت‌های دستی.**
    *   فرآیند Onboarding شرکت پذیرفته‌شده (شامل Welcome Sequence، Guided Tour، Checklist اولین ۷ روز).
    *   پروفایل شرکت (Company Profile) با امکان ویرایش کدهای کالایی (HS Codes) با کامپوننت Dropdown درختی و تعاملی.
    *   ارزیابی آمادگی اولیه (Assessment) و تولید Roadmap پایه (با نسخه‌گذاری Assessment).
    *   سیستم مدیریت وظایف (Task Management) به شکل برد کانبان (با Swipeable Tabs در موبایل).
    *   سیستم رزرو جلسات منتورینگ (Mentor Booking) با جلوگیری از Race Condition و مدیریت Timezone. **با UI بهینه برای موبایل (Time-slot Grid).**
    *   ثبت خلاصه جلسه و Action Plan توسط منتور.
    *   مرکز آموزش پایه (LMS) با چند درس نمونه (با ارتباط به cohortId). **(قابلیت‌های پیشرفته LMS به Post-MVP منتقل شد).**
    *   سیستم اعلان داخل پلتفرم (In-app Notifications) و چندکاناله (Email/SMS) با Communication Matrix دقیق.
    *   Audit Log جامع برای عملیات مهم (شامل login موفق/ناموفق، تغییر رمز، فعال/غیرفعال شدن 2FA، مشاهده/دانلود/حذف سند حساس، تغییر وضعیت application، تخصیص داور، ثبت/ویرایش Review).
    *   Document Vault پایه با رمزنگاری و کنترل دسترسی (AES-256 At-Rest، Signed URLs، تایید هدر فایل Magic Numbers، مدیریت تاریخ انقضا و نسخه‌بندی سند). **(اسکن ویروس به Post-MVP منتقل شد).**
    *   سیستم پشتیبانی داخلی (Help Desk) با تیکتینگ پایه (ثبت، مشاهده، پاسخ). **(SLA و Escalation Rules پیشرفته به Post-MVP منتقل شد).**
    *   قابلیت Manual Override برای ادمین در شرایط بحرانی.

*   **Post-MVP (قابلیت‌های پس از لانچ MVP):**
    *   Marketplace پیشرفته خدمات (RFQ, Order Management).
    *   CRM خارجی (یکپارچه‌سازی با ابزارهای ثالث).
    *   AI Assistant پیشرفته (تولید محتوا، تحلیل بازار).
    *   هوش بازار و گزارش‌های تحلیلی پیشرفته.
    *   داشبورد سرمایه‌گذار و ابزارهای ارتباطی.
    *   ماژول Demo Day و مدیریت رویدادها.
    *   PWA و قابلیت‌های آفلاین.
    *   چندزبانه شدن.
    *   بهینه‌سازی مقیاس‌پذیری و عملکرد.
    *   **اسکن ویروس برای فایل‌ها (ClamAV یا مشابه).**
    *   **SLA و Escalation Rules پیشرفته برای تیکتینگ.**
    *   **LMS پیشرفته (شامل آزمون‌ساز، گواهینامه و ...).**

**۳. قوانین مهندسی و رفتاری غیرقابل تغییر (تضمین صفر شدن باگ):**
1.  **قوانین بومی‌سازی (RTL & Calendar):** زبان پیش‌فرض فرانت فارسی و راست‌به‌چپ است. تمامی تاریخ‌ها در لایه نمایش شمسی (با استفاده از کتابخانه‌های PHP/JavaScript مانند `jdf.php` یا `PersianDate.js`) و در دیتابیس الزاماً به صورت UTC/ISO-String ذخیره می‌شوند. اعداد انگلیسی در فیلدهای دیتای عددی یا متون لاتین حتی در محیط RTL باید جهت چپ‌به‌راست (LTR) داشته باشند. الزام به wrapper واحد برای تمام لایه‌ها (API, DB, UI) برای مدیریت تاریخ شمسی.
2.  **استراتژی موبایل (Mobile-First):** هر صفحه یا کامپوننتی که توسعه داده می‌شود باید در عرض موبایل (375px) بدون اسکرول افقیِ ناخواسته بازطراحی شود. جداول عریض در دسکتاپ، باید در موبایل تبدیل به Card Listهای تعاملی شوند. قبل از پیاده‌سازی هر قابلیت، دیاگرام Wireflow (با Mermaid) در `PRODUCT_UX_ARCH.md` تولید شود. تست استرس ریسپانسیو کامپوننت‌ها در ابعاد دقیق موبایل (375px) و تبلت (768px) الزامی است.
3.  **کنترل فرانت و دیتابیس:** واکشی دیتای سرور صرفاً با AJAX (با استفاده از Fetch API یا jQuery) و مدیریت UI State با Vanilla JavaScript یا یک کتابخانه سبک انجام شود. هرگونه دگرگونی در پایگاه داده باید با اسکریپت‌های SQL Migration و نام‌گذاری دقیق همراه باشد. استفاده از ابزارهای ORM پیچیده که با هاست اشتراکی سازگار نیستند، ممنوع است. ارتباط با دیتابیس باید از طریق PDO در PHP انجام شود.
4.  **مدیریت متغیرها و فایل‌ها:** فایل‌های محلی در محیط توسعه خارج از روت پروژه (مانند `/tmp/bordar-storage`) ریخته شوند. اسناد حساس تجاری صادرکنندگان در حالت سکون (At-Rest) باید با الگوریتم AES-256 رمزنگاری شوند (پیاده‌سازی در PHP). بررسی متغیرهای محیطی (Env Vars)؛ هیچ متغیری نباید در سورس کد لو برود و همگی باید در فایل `.env` (با استفاده از `phpdotenv` یا مکانیزم مشابه) مدیریت و ولیدیت شوند.
5.  **معماری Multi-Tenancy:** تخصیص فیلتر بر اساس `cohortId` و `companyId` باید در قالب یک میان‌افزار (Middleware) یا کلاس‌های پایه (Base Classes) در PHP اجباری شود تا خطای انسانی صادرکننده نتواند به دیتای صادرکننده دیگر دسترسی یابد. الزام به نوشتن **Custom PHP Middleware/Classes** که همه queryها را به‌صورت خودکار با `companyId` و `cohortId` فیلتر کند + تست‌های واحد برای جلوگیری از leak.
6.  **پایداری و Resilience:** برای سرویس‌های خارجی (AI, Payment, Storage) از الگوی Circuit Breaker استفاده شود (پیاده‌سازی در PHP با استفاده از یک کلاس سفارشی یا کتابخانه‌های موجود).
7.  **امنیت فایل:** در بخش بارگذاری اسناد تجاری صادرکنندگان (Document Vault)، صرفاً تایید پسوند فایل کافی نیست. هوش مصنوعی باید مکانیزم **تایید هدر فایل (Magic Numbers)** را پیاده کند (پیاده‌سازی در PHP). **(اسکن ویروس به Post-MVP منتقل شد).**
8.  **قانون پایداری داده (Soft Delete):** حذف فیزیکی (Hard Delete) در جداول اصلی (User, Company, Document, Application, Task, Payment) مطلقاً ممنوع است. تمام این جداول باید دارای فیلد `deletedAt DATETIME NULL` باشند و در PHP به صورت خودکار در queryها فیلتر شوند.
9.  **قانون مدیریت زمان جهانی:** تمام فیلدهای دیتابیس UTC هستند. در لایه فرانت‌اند، نمایش زمان جلسات منتورینگ باید بر اساس لایه Timezone ذخیره شده در پروفایل کاربر و با ابزار جلالي کنترل شود تا تداخل (Overlap) در ساعت‌های مختلف کشورها رخ ندهد.
10. **پلن جایگزین پرداخت:** علاوه بر درگاه آنلاین، امکان آپلود تصویر فیش واریزی (Manual Bank Transfer) همراه با فیلدهای شماره پیگیری و تاریخ برای تایید دستی توسط ادمین در فاز ۴ الزامی است. **با تاکید بر Idempotency و Audit Log دقیق برای جلوگیری از خطای انسانی.**
11. **Input Sanitization در لایه بک‌اند:** استفاده از توابع داخلی PHP مانند `filter_var`, `htmlspecialchars` یا یک کتابخانه معتبر برای ضد XSS در لایه میان‌افزار بک‌اند اجبار شوند.
12. **مدیریت Error Handling یکپارچه:** تعریف `Global Error Handler` در PHP، کلاس‌های خطای سفارشی و فرمت استاندارد برای پاسخ‌های خطا (JSON). بررسی عدم وجود هاردکدینگ (Hardcoding) در پیام‌های خطا و لودینگ‌ها (استفاده الزامی از سیستم کامپوننت‌های توست و اسکلتون دیزاین سیستم).
13. **Validation Schemas مرکزی و Type Generation:** استفاده از کلاس‌های ولیدیشن سفارشی در PHP برای اعتبارسنجی ورودی‌ها و داده‌ها.
14. **Testing Pyramid و الزامات QA:** الزام به نوشتن **Unit (PHPUnit)** + **Integration** + **E2E (Playwright/Selenium)** برای مسیرهای حیاتی (Application submission, Booking, Payment webhook). حداقل coverage 70% برای منطق هسته.
15. **Auth & Authorization:** جزئیات دقیق RBAC + Permission Checking (مثلاً `can("document:read", companyId)`) + Session Strategy (JWT یا Database Session) پیاده‌سازی شده در PHP.
16. **Payment (Zarinpal) Flow:** پیاده‌سازی کامل جریان پرداخت زرین‌پال با استفاده از SDK PHP.

**۴. معماری سیستم (System Architecture v5.0 - PHP/MySQL Edition):**

*   **Frontend:**
    *   HTML5, CSS3 (Tailwind CSS یا Bootstrap برای سرعت توسعه), Vanilla JavaScript (یا یک کتابخانه سبک مانند Alpine.js/jQuery برای تعاملات پیچیده‌تر).
    *   AJAX برای ارتباط با APIهای بک‌اند.
    *   مدیریت وضعیت UI با Vanilla JS یا کتابخانه‌های سبک.
    *   عدم استفاده از فریم‌ورک‌های SPA سنگین مانند React/Vue/Angular برای سازگاری با هاست اشتراکی و PHP خام.

*   **Backend:**
    *   **PHP 8.x خام:** بدون استفاده از فریم‌ورک‌های MVC سنگین مانند Laravel/Symfony برای حفظ سادگی و سازگاری با هاست اشتراکی. معماری باید بر پایه کلاس‌ها و توابع سازمان‌یافته باشد.
    *   **Routing:** پیاده‌سازی سیستم Routing سفارشی برای مدیریت URLها و نگاشت آن‌ها به کنترلرهای PHP.
    *   **Controllers:** کلاس‌های PHP برای مدیریت درخواست‌ها و پاسخ‌ها.
    *   **Models:** کلاس‌های PHP برای تعامل با دیتابیس (PDO) و نگاشت داده‌ها به آبجکت‌ها (یک ORM سبک سفارشی یا Data Mapper Pattern).
    *   **Services/Business Logic:** کلاس‌های PHP برای پیاده‌سازی منطق کسب‌وکار.
    *   **Middleware:** کلاس‌های PHP برای احراز هویت، مجوزدهی، Multi-tenancy و Sanitization.
    *   **API:** RESTful API با پاسخ‌های JSON.

*   **Database:**
    *   **MySQL 8.x:** استفاده از قابلیت‌های جدید MySQL 8.x (مانند JSON functions, CTEs) در صورت نیاز.
    *   **Schema Design:** طراحی شمای دیتابیس بهینه با ایندکس‌های مناسب برای عملکرد بالا.
    *   **Migrations:** اسکریپت‌های SQL برای مدیریت تغییرات شمای دیتابیس.

*   **Hosting Environment:**
    *   **Shared Hosting Compatibility:** تمامی پیاده‌سازی‌ها باید با محدودیت‌های هاست اشتراکی (مانند عدم دسترسی به SSH برای برخی دستورات، محدودیت‌های حافظه و CPU) سازگار باشند.
    *   **File Structure:** ساختار فایل‌ها باید به گونه‌ای باشد که به راحتی در هاست اشتراکی قابل استقرار باشد (معمولاً با یک پوشه `public_html` یا `www`).

**۵. مدل داده (Data Model - MySQL 8.x Schema):**

این بخش مدل داده‌ای را که قبلاً با Prisma تعریف شده بود، به شمای MySQL 8.x تبدیل می‌کند. تمامی فیلدها، روابط و محدودیت‌ها باید در اسکریپت‌های SQL Migration تعریف شوند.

```sql
-- User Management
CREATE TABLE `users` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255),
    `last_name` VARCHAR(255),
    `phone_number` VARCHAR(20) UNIQUE,
    `profile_picture_url` VARCHAR(2048),
    `role_id` VARCHAR(36) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `email_verified_at` DATETIME NULL,
    `phone_verified_at` DATETIME NULL,
    `two_factor_secret` VARCHAR(255) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
);

CREATE TABLE `roles` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `permissions` JSON NOT NULL, -- Store permissions as JSON array
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `user_sessions` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) NOT NULL,
    `session_token` VARCHAR(255) UNIQUE NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Company Management
CREATE TABLE `companies` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) NOT NULL,
    `registration_number` VARCHAR(255) UNIQUE,
    `owner_user_id` VARCHAR(36) NOT NULL,
    `industry` VARCHAR(255),
    `country` VARCHAR(255),
    `city` VARCHAR(255),
    `website` VARCHAR(2048),
    `description` TEXT,
    `status` ENUM('ACTIVE', 'INACTIVE', 'PENDING') DEFAULT 'PENDING',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_user_id`) REFERENCES `users`(`id`)
);

-- Cohort Management
CREATE TABLE `cohorts` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `application_deadline` DATETIME,
    `status` ENUM('UPCOMING', 'ACTIVE', 'COMPLETED', 'ARCHIVED') DEFAULT 'UPCOMING',
    `description` TEXT,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `company_cohorts` (
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `status` ENUM('APPLIED', 'ACCEPTED', 'REJECTED', 'WITHDRAWN', 'COMPLETED') DEFAULT 'APPLIED',
    `joined_at` DATETIME,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`company_id`, `cohort_id`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`)
);

-- Application Management
CREATE TABLE `applications` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `status` ENUM('DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED', 'PENDING_INFO') DEFAULT 'DRAFT',
    `data` JSON NOT NULL, -- Store form data as JSON
    `version` INT DEFAULT 1,
    `submitted_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`)
);

CREATE TABLE `application_reviewers` (
    `application_id` VARCHAR(36) NOT NULL,
    `reviewer_user_id` VARCHAR(36) NOT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `score` DECIMAL(5, 2) NULL,
    `feedback` TEXT NULL,
    `conflict_of_interest` BOOLEAN DEFAULT FALSE,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`application_id`, `reviewer_user_id`),
    FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`),
    FOREIGN KEY (`reviewer_user_id`) REFERENCES `users`(`id`)
);

-- Document Management
CREATE TABLE `documents` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `company_id` VARCHAR(36) NOT NULL,
    `uploaded_by_user_id` VARCHAR(36) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `file_path` VARCHAR(2048) NOT NULL, -- Encrypted path or storage key
    `is_encrypted` BOOLEAN DEFAULT TRUE,
    `encryption_key_id` VARCHAR(36) NULL, -- If using key management service
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    `expires_at` DATETIME NULL,
    `version` INT DEFAULT 1,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users`(`id`)
);

-- Task Management
CREATE TABLE `tasks` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `company_id` VARCHAR(36) NOT NULL,
    `assigned_to_user_id` VARCHAR(36) NULL,
    `status` ENUM('TODO', 'IN_PROGRESS', 'DONE', 'BLOCKED') DEFAULT 'TODO',
    `due_date` DATETIME NULL,
    `priority` ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users`(`id`)
);

-- Mentoring Sessions
CREATE TABLE `mentors` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) UNIQUE NOT NULL,
    `bio` TEXT,
    `expertise` JSON, -- Store array of expertise as JSON
    `availability` JSON, -- Store availability slots as JSON
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `mentor_sessions` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `mentor_id` VARCHAR(36) NOT NULL,
    `company_id` VARCHAR(36) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('SCHEDULED', 'COMPLETED', 'CANCELED', 'RESCHEDULED') DEFAULT 'SCHEDULED',
    `agenda` TEXT,
    `summary` TEXT,
    `action_plan` TEXT,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`mentor_id`) REFERENCES `mentors`(`id`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
);

-- Payments
CREATE TABLE `payments` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'IRR',
    `status` ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') DEFAULT 'PENDING',
    `payment_gateway` VARCHAR(255),
    `transaction_id` VARCHAR(255) UNIQUE,
    `invoice_id` VARCHAR(36) NULL,
    `paid_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    `manual_receipt_image_id` VARCHAR(36) NULL,
    `payment_intent_id` VARCHAR(255) UNIQUE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`manual_receipt_image_id`) REFERENCES `images`(`id`)
);

CREATE TABLE `invoices` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `company_id` VARCHAR(36) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'IRR',
    `issue_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `due_date` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `images` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `file_path` VARCHAR(2048) NOT NULL,
    `uploaded_by_id` VARCHAR(36) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploaded_by_id`) REFERENCES `users`(`id`)
);

-- Communication & Notifications
CREATE TABLE `notifications` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) NOT NULL,
    `type` ENUM('APPLICATION_STATUS_UPDATE', 'MENTOR_SESSION_REMINDER', 'TASK_ASSIGNED', 'PAYMENT_DUE', 'DOCUMENT_EXPIRED', 'SYSTEM_ALERT', 'SUPPORT_TICKET_UPDATE', 'GENERAL_ANNOUNCEMENT', 'REVIEW_CLARIFICATION_REQUEST') NOT NULL,
    `channel` ENUM('IN_APP', 'EMAIL', 'SMS') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `link` VARCHAR(2048),
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `communications` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `sender_id` VARCHAR(36) NOT NULL,
    `receiver_id` VARCHAR(36) NOT NULL,
    `subject` VARCHAR(255),
    `body` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `parent_id` VARCHAR(36) NULL,
    `attachments` JSON, -- Store array of file paths/IDs as JSON
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`parent_id`) REFERENCES `communications`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

-- Audit Log
CREATE TABLE `audit_logs` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(255) NOT NULL,
    `entity_id` VARCHAR(36) NOT NULL,
    `old_value` JSON,
    `new_value` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- LMS (Learning Management System)
CREATE TABLE `courses` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `cohort_id` VARCHAR(36) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `lessons` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `course_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `type` ENUM('VIDEO', 'TEXT', 'QUIZ', 'EXTERNAL_LINK') NOT NULL,
    `content` JSON NOT NULL, -- URL for video, text content, quiz data as JSON
    `order` INT NOT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

-- Events
CREATE TABLE `events` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `type` ENUM('WEBINAR', 'WORKSHOP', 'DEMO_DAY', 'MEETUP', 'OTHER') DEFAULT 'OTHER',
    `cohort_id` VARCHAR(36) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `event_attendees` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `event_id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- Support Tickets
CREATE TABLE `support_tickets` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'ESCALATED', 'PENDING_USER_RESPONSE') DEFAULT 'OPEN',
    `priority` ENUM('LOW', 'MEDIUM', 'HIGH', 'URGENT') DEFAULT 'MEDIUM',
    `category` ENUM('REGISTRATION', 'PAYMENT', 'APPLICATION_FORM', 'DOCUMENTS', 'MENTORING_SESSION', 'ACCESS_ISSUE', 'TECHNICAL_ISSUE', 'REVIEW_APPEAL', 'OTHER') DEFAULT 'OTHER',
    `assigned_to_id` VARCHAR(36) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`assigned_to_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `support_ticket_messages` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `ticket_id` VARCHAR(36) NOT NULL,
    `sender_id` VARCHAR(36) NOT NULL,
    `message` TEXT NOT NULL,
    `is_internal` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`),
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`)
);

-- Admin Configurable Settings
CREATE TABLE `system_configs` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `key` VARCHAR(255) UNIQUE NOT NULL,
    `value` JSON NOT NULL,
    `description` TEXT,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`updated_by_id`) REFERENCES `users`(`id`)
);

-- Vendor & Marketplace (Post-MVP Placeholders)
CREATE TABLE `vendors` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) UNIQUE NOT NULL,
    `profile` JSON,
    `services` JSON,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `leads` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `company_id` VARCHAR(36) NOT NULL,
    `vendor_id` VARCHAR(36) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `details` JSON,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`),
    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
);

-- General Settings
CREATE TABLE `settings` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `value` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🌳 بخش ۳ — ساختار پروژه و سازماندهی کد (Project Structure v5.0 - PHP/MySQL Edition)

هوش مصنوعی موظف است ساختار پروژه را بر اساس الگوی **Feature-Sliced Design (FSD)** و با رعایت قوانین زیر پیاده‌سازی کند. این ساختار، مقیاس‌پذیری، نگهداری و توسعه‌پذیری پروژه را تضمین می‌کند. ساختار زیر برای یک پروژه PHP خام با رویکرد ماژولار و سازگار با هاست اشتراکی پیشنهاد می‌شود:

```
├── .github/                       # GitHub Actions CI/CD workflows (if applicable)
├── .vscode/                       # VSCode settings and recommendations
├── public/                        # Publicly accessible files (index.php, assets, .htaccess)
│   ├── index.php                  # Front controller for all requests
│   ├── assets/                    # CSS, JS, images, fonts
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── .htaccess                  # URL Rewriting for clean URLs
├── src/                           # All application source code
│   ├── Config/                    # Application configurations (database, constants, etc.)
│   │   ├── Database.php           # Database connection (PDO)
│   │   ├── App.php                # General application settings
│   │   └── Constants.php          # Global constants
│   ├── Core/                      # Core framework components
│   │   ├── Router.php             # Custom URL Router
│   │   ├── Controller.php         # Base Controller class
│   │   ├── Model.php              # Base Model class (for basic CRUD or Data Mapper)
│   │   ├── View.php               # View rendering logic
│   │   ├── Middleware.php         # Base Middleware class
│   │   ├── Session.php            # Session management
│   │   └── Auth.php               # Authentication logic
│   ├── Database/                  # Database related files
│   │   ├── Migrations/            # SQL migration scripts
│   │   └── Seeders/               # Database seeders (for initial data)
│   ├── Features/                  # Feature-sliced modules (domain-driven)
│   │   ├── Auth/                  # Authentication & Authorization logic
│   │   │   ├── Controllers/       # Auth related controllers
│   │   │   ├── Models/            # Auth related models
│   │   │   ├── Services/          # Auth specific business logic
│   │   │   ├── Views/             # Auth related UI templates
│   │   │   └── Middleware/        # Auth specific middleware
│   │   ├── Application/           # Application submission & management
│   │   ├── Cohort/                # Cohort management
│   │   ├── Document/              # Document management & vault
│   │   ├── Mentor/                # Mentor profiles & booking
│   │   ├── Payment/               # Payment processing
│   │   ├── Review/                # Application review process
│   │   ├── Task/                  # Task management
│   │   ├── User/                  # User profiles & management
│   │   └── ...                    # Other domain features
│   ├── Lib/                       # Utility functions, helpers, external integrations
│   │   ├── Helpers.php            # General utility functions
│   │   ├── DateConverter.php      # Jalaali date conversion
│   │   ├── FileUploader.php       # Secure file upload with Magic Numbers
│   │   ├── Sanitizer.php          # Input sanitization
│   │   ├── ZarinpalClient.php     # Zarinpal API client
│   │   ├── S3Client.php           # S3/MinIO client (if used)
│   │   └── ...
│   ├── Templates/                 # Global UI templates (header, footer, layouts)
│   ├── Views/                     # General views (error pages, etc.)
│   └── bootstrap.php              # Application initialization and autoloader
├── vendor/                        # Composer dependencies (e.g., phpdotenv, PHPUnit)
├── tests/                         # Unit, Integration, E2E tests
│   ├── Unit/
│   ├── Integration/
│   └── E2E/
├── .env                           # Environment variables
├── .env.example                   # Example environment variables
├── composer.json                  # Composer configuration
├── composer.lock                  # Composer lock file
├── README.md                      # Project README
└── PROGRESS.md                    # Progress documentation
```

**توضیحات ساختار پروژه:**

*   **`public/`**: این پوشه نقطه ورود تمام درخواست‌ها است. فایل `index.php` به عنوان Front Controller عمل کرده و تمام درخواست‌ها را از طریق Router به کنترلرهای مناسب هدایت می‌کند. فایل `.htaccess` برای بازنویسی URLها و ایجاد آدرس‌های تمیز (Clean URLs) استفاده می‌شود.
*   **`src/`**: کد اصلی برنامه در این پوشه قرار دارد.
    *   **`Config/`**: تنظیمات اصلی برنامه مانند اطلاعات دیتابیس، تنظیمات اپلیکیشن و ثابت‌های سراسری.
    *   **`Core/`**: کامپوننت‌های اصلی فریم‌ورک مانند Router، Base Controller، Base Model، View Renderer، Middleware و کلاس‌های مدیریت Session و Authentication.
    *   **`Database/`**: اسکریپت‌های SQL برای Migrationها و Seeders.
    *   **`Features/`**: این پوشه شامل ماژول‌های Feature-Sliced Design است که هر قابلیت اصلی (مانند احراز هویت، مدیریت درخواست‌ها، مدیریت اسناد) را به صورت مستقل و با ساختار داخلی MVC (Controllers, Models, Views, Services, Middleware) سازماندهی می‌کند.
    *   **`Lib/`**: توابع کمکی، ابزارهای عمومی، و کلاینت‌های API برای سرویس‌های خارجی (مانند زرین‌پال، S3).
    *   **`Templates/` و `Views/`**: فایل‌های قالب HTML برای رندر کردن صفحات.
    *   **`bootstrap.php`**: فایل اولیه برای بارگذاری خودکار کلاس‌ها (Autoloader) و راه‌اندازی اولیه برنامه.
*   **`vendor/`**: وابستگی‌های Composer (مانند `phpdotenv` برای مدیریت متغیرهای محیطی، `phpunit` برای تست‌ها).
*   **`.env` و `.env.example`**: برای مدیریت متغیرهای محیطی حساس.

---

## 📊 بخش ۷ — ماتریس دسترسی و نقش‌ها (RBAC & Permission Matrix v5.0 - PHP/MySQL Edition)

هوش مصنوعی موظف است سیستم Role-Based Access Control (RBAC) را بر اساس ماتریس زیر پیاده‌سازی کند. `permissions` در مدل `Role` باید شامل resource/action دقیق باشد و در PHP با استفاده از یک کلاس `Auth` یا `PermissionManager` مدیریت شود.

| نقش             | مشاهده درخواست | ویرایش درخواست | مشاهده اسناد | تخصیص داور | ثبت امتیاز | مشاهده پرداخت | مدیریت کوهورت | مدیریت منتور | مدیریت کاربران | مدیریت تیکت |
|-----------------|-----------------|-----------------|---------------|-------------|------------|----------------|----------------|---------------|----------------|-------------|
| **Applicant**   | فقط خودش        | تا قبل از ارسال | فقط خودش      | خیر         | خیر        | فقط خودش       | خیر            | رزرو جلسات    | ویرایش پروفایل | ثبت و مشاهده |
| **Mentor**      | درخواست‌های تخصیص‌یافته | خیر             | اسناد شرکت‌های تخصیص‌یافته | خیر         | خیر        | خیر            | خیر            | مدیریت Availability | خیر            | مشاهده و پاسخ |
| **Reviewer**    | درخواست‌های تخصیص‌یافته | خیر             | اسناد درخواست‌های تخصیص‌یافته | خیر         | بله        | خیر            | خیر            | خیر            | خیر            | خیر         |
| **Admin**       | بله             | بله             | بله           | بله         | بله        | بله            | بله            | بله            | بله            | بله         |
| **Vendor**      | خیر             | خیر             | خیر           | خیر         | خیر        | خیر            | خیر            | خیر            | خیر            | خیر         |
| **Investor**    | (Post-MVP)      | (Post-MVP)      | (Post-MVP)    | (Post-MVP)  | (Post-MVP) | (Post-MVP)     | (Post-MVP)     | (Post-MVP)    | (Post-MVP)     | (Post-MVP)  |

**توضیحات RBAC در PHP:**

*   **جدول `roles`**: شامل `id`, `name`, `description` و یک فیلد `permissions` از نوع JSON برای ذخیره آرایه‌ای از رشته‌های مجوز (مثلاً `[
document:read
document:read", "application:submit"]).
*   **کلاس `Auth` یا `PermissionManager`**: این کلاس مسئول بررسی مجوزهای کاربر بر اساس نقش و دسترسی‌های تعریف‌شده در دیتابیس خواهد بود. متدهایی مانند `Auth::can('document:read', $companyId)` برای بررسی دسترسی‌ها استفاده خواهند شد.
*   **پیاده‌سازی در Middleware**: در لایه Middleware، قبل از اجرای کنترلرها، بررسی‌های دسترسی انجام می‌شود تا اطمینان حاصل شود که کاربر فعلی مجوز لازم برای انجام عملیات درخواستی را دارد.

---

## 🚀 فازهای اجرایی (Execution Phases - PHP/MySQL Edition)

**توجه:** هر فاز باید به صورت کامل و با رعایت تمامی قوانین و الزامات ذکر شده در این سند پیاده‌سازی شود. در پایان هر فاز، فایل `PROGRESS.md` به‌روزرسانی و تغییرات `git commit` شوند.

### فاز ۱: راه‌اندازی پروژه و سیستم احراز هویت پایه

**هدف:** ایجاد ساختار پروژه، راه‌اندازی دیتابیس و پیاده‌سازی سیستم ثبت‌نام، ورود و خروج کاربران.

**اقدامات:**
1.  **راه‌اندازی اولیه پروژه:**
    *   ایجاد ساختار پوشه‌ها مطابق با `بخش ۳ — ساختار پروژه و سازماندهی کد`.
    *   پیکربندی `public/index.php` به عنوان Front Controller و `public/.htaccess` برای Clean URLs.
    *   ایجاد `src/Config/Database.php` برای اتصال به MySQL 8 با PDO.
    *   ایجاد `src/Core/Router.php` برای مدیریت مسیرها.
    *   ایجاد `src/Core/Controller.php` و `src/Core/Model.php` به عنوان کلاس‌های پایه.
    *   ایجاد `src/bootstrap.php` برای Autoloader و راه‌اندازی اولیه.
    *   نصب `phpdotenv` با Composer برای مدیریت متغیرهای محیطی و ایجاد فایل‌های `.env` و `.env.example`.
2.  **پیاده‌سازی مدل داده پایه:**
    *   اجرای اسکریپت‌های SQL برای ایجاد جداول `roles`, `users`, `user_sessions` مطابق با `بخش ۵ — مدل داده`.
    *   ایجاد یک Role پیش‌فرض `Admin` با تمام مجوزها و یک کاربر `Admin` اولیه در Seeders.
3.  **سیستم احراز هویت:**
    *   پیاده‌سازی کنترلرها و مدل‌های مربوط به احراز هویت در `src/Features/Auth/`.
    *   صفحات ثبت‌نام، ورود، فراموشی رمز عبور و خروج.
    *   استفاده از هشینگ قوی برای رمز عبور (مانند `password_hash` در PHP).
    *   مدیریت Sessionها با `src/Core/Session.php`.
    *   پیاده‌سازی `src/Core/Auth.php` برای مدیریت وضعیت احراز هویت کاربر.
    *   اعتبارسنجی ورودی‌ها (Input Validation) در سمت سرور.
    *   پیاده‌سازی Middleware برای مسیرهای محافظت‌شده (Protected Routes).
4.  **بومی‌سازی پایه:**
    *   پیکربندی زبان پیش‌فرض فارسی و RTL در فرانت‌اند (CSS).
    *   نمایش تاریخ‌ها به صورت شمسی در UI (با استفاده از کتابخانه `jdf.php` یا مشابه).
5.  **تست‌ها:**
    *   نوشتن تست‌های Unit برای کلاس‌های Core و Auth (با PHPUnit).
    *   نوشتن تست‌های Integration برای جریان ثبت‌نام و ورود.

### فاز ۲: مدیریت شرکت‌ها، کوهورت‌ها و فرم درخواست پذیرش

**هدف:** پیاده‌سازی ماژول‌های مدیریت شرکت‌ها، کوهورت‌ها و فرم درخواست پذیرش با قابلیت ذخیره پیش‌نویس و نسخه‌گذاری.

**اقدامات:**
1.  **مدیریت شرکت‌ها:**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Company/`.
    *   صفحات ایجاد، مشاهده، ویرایش پروفایل شرکت.
    *   پیاده‌سازی Multi-Tenancy بر اساس `company_id` در Middleware یا Base Model.
2.  **مدیریت کوهورت‌ها:**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Cohort/`.
    *   صفحات ایجاد، مشاهده، ویرایش کوهورت‌ها توسط ادمین.
    *   قابلیت تعریف ددلاین برای هر کوهورت.
3.  **فرم درخواست پذیرش (Application Form):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Application/`.
    *   طراحی فرم با Progressive Disclosure.
    *   قابلیت ذخیره پیش‌نویس (Auto-save) با AJAX.
    *   ذخیره داده‌های فرم به صورت JSON در فیلد `data` جدول `applications`.
    *   پیاده‌سازی نسخه‌گذاری فرم‌ها (Form Versioning).
    *   صفحه مشاهده درخواست‌های ارسال شده توسط متقاضی.
4.  **تست‌ها:**
    *   تست‌های Unit و Integration برای ماژول‌های Company, Cohort و Application.
    *   تست E2E برای جریان ارسال درخواست پذیرش.

### فاز ۳: سیستم داوری و مدیریت اسناد

**هدف:** پیاده‌سازی سیستم داوری درخواست‌ها و ماژول مدیریت اسناد با رمزنگاری و کنترل دسترسی.

**اقدامات:**
1.  **سیستم داوری:**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Review/`.
    *   صفحه تخصیص داور به درخواست‌ها توسط ادمین.
    *   فرم امتیازدهی برای داوران.
    *   مدیریت تعارض منافع و مکانیزم حل اختلاف امتیازها.
    *   قابلیت Draft Review و قفل نهایی Review.
    *   امکان درخواست شفاف‌سازی از متقاضی توسط داور.
2.  **مدیریت اسناد (Document Vault):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Document/`.
    *   قابلیت بارگذاری اسناد توسط شرکت‌ها.
    *   رمزنگاری اسناد در حالت سکون (At-Rest) با AES-256 در PHP.
    *   پیاده‌سازی تایید هدر فایل (Magic Numbers) برای امنیت فایل.
    *   کنترل دسترسی به اسناد (Signed URLs یا مکانیزم مشابه).
    *   مدیریت تاریخ انقضا و نسخه‌بندی سند.
3.  **Audit Log:**
    *   پیاده‌سازی مکانیزم ثبت Audit Log برای عملیات مهم (مشاهده/دانلود/حذف سند حساس، تغییر وضعیت application، تخصیص داور، ثبت/ویرایش Review).
4.  **تست‌ها:**
    *   تست‌های Unit و Integration برای ماژول‌های Review و Document.
    *   تست E2E برای جریان داوری و بارگذاری/دانلود اسناد.

### فاز ۴: ماژول پرداخت و Onboarding

**هدف:** پیاده‌سازی ماژول پرداخت Program Fee و فرآیند Onboarding شرکت‌های پذیرفته‌شده.

**اقدامات:**
1.  **ماژول پرداخت:**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Payment/`.
    *   یکپارچه‌سازی با زرین‌پال Sandbox (با استفاده از SDK PHP یا پیاده‌سازی مستقیم API).
    *   پشتیبانی از پرداخت اقساطی.
    *   قابلیت ثبت فیش واریز بانکی (Manual Bank Transfer) با آپلود تصویر و فیلدهای شماره پیگیری و تاریخ.
    *   پیاده‌سازی Idempotency و Audit Log دقیق برای پرداخت‌ها.
2.  **فرآیند Onboarding:**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Onboarding/`.
    *   Welcome Sequence و Guided Tour (با استفاده از JavaScript).
    *   Checklist اولین ۷ روز برای شرکت‌های پذیرفته‌شده.
3.  **سیستم اعلان:**
    *   پیاده‌سازی سیستم اعلان داخل پلتفرم و چندکاناله (Email/SMS) با Communication Matrix دقیق.
4.  **تست‌ها:**
    *   تست‌های Unit و Integration برای ماژول Payment و Onboarding.
    *   تست E2E برای جریان پرداخت و Onboarding.

### فاز ۵: مدیریت وظایف، رزرو منتورینگ و مرکز آموزش پایه

**هدف:** پیاده‌سازی سیستم مدیریت وظایف، رزرو جلسات منتورینگ و مرکز آموزش پایه.

**اقدامات:**
1.  **سیستم مدیریت وظایف (Task Management):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Task/`.
    *   برد کانبان برای مدیریت وظایف (با استفاده از HTML/CSS/JS).
2.  **سیستم رزرو جلسات منتورینگ (Mentor Booking):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/Mentor/`.
    *   مدیریت Availability منتورها.
    *   جلوگیری از Race Condition در رزرو جلسات.
    *   مدیریت Timezone.
    *   UI بهینه برای موبایل (Time-slot Grid).
    *   ثبت خلاصه جلسه و Action Plan توسط منتور.
3.  **مرکز آموزش پایه (LMS):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/LMS/`.
    *   قابلیت افزودن چند درس نمونه.
    *   ارتباط دروس با `cohortId`.
4.  **سیستم پشتیبانی داخلی (Help Desk):**
    *   پیاده‌سازی کنترلرها و مدل‌ها در `src/Features/SupportTicket/`.
    *   قابلیت ثبت، مشاهده و پاسخ به تیکت‌ها.
5.  **تست‌ها:**
    *   تست‌های Unit و Integration برای ماژول‌های Task, Mentor, LMS و SupportTicket.
    *   تست E2E برای جریان رزرو منتورینگ.

### فاز ۶: تکمیل و بهینه‌سازی نهایی

**هدف:** انجام بهینه‌سازی‌های نهایی، رفع اشکالات و آماده‌سازی پروژه برای استقرار.

**اقدامات:**
1.  **بهینه‌سازی عملکرد:**
    *   بررسی و بهینه‌سازی کوئری‌های دیتابیس.
    *   بهینه‌سازی کدهای PHP و JavaScript.
    *   تنظیمات کشینگ (در صورت امکان در هاست اشتراکی).
2.  **امنیت:**
    *   انجام تست‌های امنیتی پایه (مانند بررسی XSS, SQL Injection).
    *   اطمینان از رعایت تمامی قوانین امنیتی ذکر شده در `بخش ۳ — قوانین مهندسی و رفتاری`.
3.  **مستندسازی:**
    *   تکمیل `README.md` پروژه.
    *   نوشتن مستندات فنی برای APIها و ماژول‌های اصلی.
4.  **استقرار:**
    *   آماده‌سازی پروژه برای استقرار در هاست اشتراکی.
    *   ارائه دستورالعمل‌های لازم برای نصب و راه‌اندازی.
5.  **تست نهایی:**
    *   اجرای تمامی تست‌های Unit, Integration و E2E.
    *   تست دستی تمامی قابلیت‌های MVP.

---

## 📝 مراجع

[1] PHP Official Documentation: [https://www.php.net/](https://www.php.net/)
[2] MySQL 8.0 Reference Manual: [https://dev.mysql.com/doc/refman/8.0/en/](https://dev.mysql.com/doc/refman/8.0/en/)
[3] PDO - PHP Data Objects: [https://www.php.net/manual/en/book.pdo.php](https://www.php.net/manual/en/book.pdo.php)
[4] Composer: [https://getcomposer.org/](https://getcomposer.org/)
[5] phpdotenv: [https://github.com/vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
[6] PHPUnit: [https://phpunit.de/](https://phpunit.de/)
[7] Zarinpal PHP SDK (Example): [https://github.com/ZarinPal-Lab/ZarinPal-PHP-SDK](https://github.com/ZarinPal-Lab/ZarinPal-PHP-SDK)
[8] JDF (Jalali Date Functions for PHP): [https://github.com/sallar/jdf](https://github.com/sallar/jdf)
[9] Tailwind CSS: [https://tailwindcss.com/](https://tailwindcss.com/)
[10] Bootstrap: [https://getbootstrap.com/](https://getbootstrap.com/)
[11] Alpine.js: [https://alpinejs.dev/](https://alpinejs.dev/)
[12] jQuery: [https://jquery.com/](https://jquery.com/)
