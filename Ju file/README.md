# Ju File — منصة أدوات الملفات والمشاركة السريعة (URL & QR)

تطبيق ويب متكامل مبني بـ **PHP 8 + MySQL (PDO) + Bootstrap 5 RTL** يوفّر صفحة متخصصة لكل نوع ملف مع عرض الأدوات الممكنة لهذا النوع تحديداً، بالإضافة إلى ميزة تحويل أي ملف إلى **رابط قصير (URL) أو رمز QR** للمشاركة الفورية.

---

## المزايا والأقسام حسب نوع الملف

| القسم | الصفحة | الأدوات المتاحة لهذا النوع تحديداً |
|---|---|---|
| **الصور** | `image.php` | تصغير الحجم، تغيير الأبعاد، قص تفاعلي (Crop)، تدوير وقلب، تحويل الصيغة (`JPG / PNG / WebP / GIF / BMP`)، فلاتر لونية |
| **الفيديو** | `video.php` | تصغير الحجم (MP4)، قص المدة الزمنية، اقتصاص أبعاد الإطار (`9:16`، `1:1`، `16:9`...)، تحويل الصيغة (`MP4 / WebM / MOV / MKV / AVI / GIF`)، استخراج الصوت، كتم الصوت |
| **الصوت** | `audio.php` | تحويل الصيغة (`MP3 / WAV / OGG / M4A / FLAC`)، تصغير الحجم، قص المدة، تعديل وموازنة مستوى الصوت |
| **المستندات والملفات الكتابية** | `document.php` | دمج PDF، استخراج صفحات، حذف صفحات، تدوير PDF، صور إلى PDF، تحويل نصي (`CSV ⇄ JSON`، نص إلى HTML، تنسيق JSON)، تنظيف النص، بحث واستبدال، إصلاح الترميز العربي (`UTF-8`)، إحصائيات النص، ضغط إلى `ZIP` |
| **المشاركة السريعة** | `share.php` و `f.php` | رفع أي ملف (أو تحويل نتيجة أي أداة مباشرة) إلى رابط قصير + رمز QR قابل للنسخ والتنزيل، مع خيار كلمة مرور ومدة صلاحية |

---

## طريقة التشغيل (XAMPP / Laragon / WAMP)

1. انسخ مجلد المشروع إلى `htdocs` (أو شغّل خادم PHP المدمج من داخل المجلد).
2. تأكد من تفعيل الامتدادات `pdo_mysql`, `gd`, `zip`, `mbstring`, `fileinfo` في ملف `php.ini`.
3. شغّل **MySQL** ثم استورد قاعدة البيانات من الملف [`database.sql`](database.sql) عبر phpMyAdmin أو الأمر:
   ```bash
   mysql -u root < database.sql
   ```
4. عدّل بيانات الاتصال في [`config.php`](config.php) إذا كانت مختلفة عن الإعدادات الافتراضية (`root` بدون كلمة مرور).
5. افتح الموقع في المتصفح (أو شغّله عبر `php -S localhost:8000`).

---

## مطابقة متطلبات التقييم (Application Requirements)

- **Home Page (`index.php`)**: صفحة رئيسية متجاوبة تحتوي على شريط تنقل (`header.php`)، مقدمة واضحة، بطاقات اختيار نوع الملف، إحصائيات حية، وتذييل (`footer.php`).
- **Form Handling — CLO 1.2 (`share.php`, `register.php`, `login.php`, `share_edit.php`, `account.php`, `api/process.php`)**: نماذج متعددة مع تحقق كامل من المدخلات على الخادم (`validate_fields` والتحقق المخصص) وعرض رسائل الخطأ والنجاح بوضوح.
- **Database Integration (`includes/db.php`, `database.sql`)**:
  - اتصال MySQL عبر `PDO` مع الاستعلامات المجهّزة.
  - عمليات الإضافة (`INSERT` للمستخدمين والروابط وسجل العمليات)، التحديث (`UPDATE` لتعديل الروابط، عداد التنزيلات، وتحديث الحساب)، والحذف (`DELETE` لحذف الروابط، مسح السجل، وحذف الحساب).
  - تصفية واسترجاع البيانات (`SELECT` مع البحث بالاسم، التصفية حسب نوع الملف وحالة الصلاحية، والترتيب في `dashboard.php`).
- **Security — Ch. 7 (`includes/init.php`, `includes/functions.php`)**:
  - استعلامات مجهّزة حقيقية (`PDO::ATTR_EMULATE_PREPARES => false`).
  - هروب جميع المخرجات عبر دالة `e()` (`htmlspecialchars`).
  - حماية جميع نماذج `POST` برمز CSRF (`csrf_field()` / `verify_csrf()`).
  - تجزئة كلمات المرور للمستخدمين ولحماية الروابط عبر `password_hash()` و `password_verify()`.
- **Authentication (`register.php`, `login.php`, `logout.php`, `account.php`)**: نظام حسابات كامل يعتمد على جلسات PHP مع `session_regenerate_id(true)`.
- **Files and Reusable Code — Ch. 4 (`includes/*`, `download.php`, `api/process.php`)**: رفع ومعالجة وتنزيل الملفات بأمان، مع فصل الكود المشترك عبر `require` / `require_once` (`init.php`, `header.php`, `footer.php`, `components.php`, `tool_page.php`).
- **Dynamic Data Display (`index.php`, `dashboard.php`, `share.php`, `f.php`)**: عرض الإحصائيات، قائمة الملفات، وسجل العمليات مباشرة من قاعدة البيانات.
- **Interface — CLO 2.1, 2.2 (`assets/css/style.css`, `assets/js/app.js`)**: تصميم بسيط جداً ومتجاوب بـ Bootstrap 5 RTL مع معاينة فورية، سحب وإفلات، قص تفاعلي، وتوليد فوري لرموز QR.

