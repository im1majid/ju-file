# Ju File — منصة أدوات الملفات والمشاركة السريعة (Laravel 12)

تطبيق ويب متكامل مبني بإطار **Laravel 12 (PHP 8.3) + MySQL + Blade + Bootstrap 5 RTL**، يوفّر صفحة متخصصة لكل نوع ملف مع عرض الأدوات الممكنة لهذا النوع تحديداً، بالإضافة إلى ميزة تحويل أي ملف إلى **رابط عام خارجي (URL) ورمز QR** للمشاركة الفورية.

---

## هيكلة المشروع (MVC)

- **`app/Http/Controllers/`**:
  - `HomeController.php`: الصفحة الرئيسية والإحصائيات الحية من قاعدة البيانات.
  - `ToolController.php`: صفحات الأدوات حسب النوع (`/image`, `/video`, `/audio`, `/document`) وواجهتا المعالجة وتسجيل العمليات (`/api/process`, `/api/log`).
  - `ShareController.php`: رفع الملفات والمشاركة عبر رابط خارجي + QR (`/share`, `/f/{token}`, `/download/{token}`, التعديل).
  - `AuthController.php`: التسجيل، تسجيل الدخول، وتسجيل الخروج عبر جلسات Laravel.
  - `DashboardController.php`: لوحة ملفاتي (بحث، تصفية، ترتيب، حذف) وسجل العمليات.
  - `AccountController.php`: تعديل الاسم، تغيير كلمة المرور، وحذف الحساب.
- **`app/Models/`**: `User`, `Share`, `Operation` (Eloquent ORM).
- **`app/Services/`**:
  - `ToolCatalog.php`: تعريف جميع الأقسام والأدوات وقواعد التحقق.
  - `ExternalHostService.php`: رفع الملفات لاستضافة عامة (`x0.at` / `uguu.se` / `tmpfiles.org`) لإرجاع رابط مشاركة خارجي.
  - `FileProcessing/`: `ImageProcessor.php` (GD)، `TextProcessor.php`، `ZipProcessor.php`.
- **`resources/views/`**: قوالب Blade (`layouts/app.blade.php`، مكوّنات `components/share-box.blade.php` و `components/tool-field.blade.php`، وصفحات التطبيق).
- **`database/migrations/`**: جداول `users`, `shares`, `operations`.
- **`routes/web.php`**: تعريف جميع المسارات النظيفة + التوافق مع الروابط السابقة.
- **`public/assets/`**: ملفات CSS (`style.css`) و JS (`app.js`, `media.js`, `pdf.js`) ومكتبات `vendor`.

---

## طريقة التشغيل محلياً

1. شغّل الملف [`start.bat`](start.bat) (يشغّل MySQL، ينفّذ `php artisan migrate --force`، ويفتح الموقع على `http://localhost:8000`).
2. أو يدوياً:
   ```bash
   php artisan migrate --force
   php -d upload_max_filesize=60M -d post_max_size=65M -d memory_limit=512M artisan serve --host=127.0.0.1 --port=8000
   ```
