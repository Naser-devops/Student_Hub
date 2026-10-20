# سجل تعديلات StudentHub — نسخة مستقلة عن الأصل

**المصدر:** `StudentHub(3).zip`، ولا تغييرات على المصدر الأصلي أو قاعدة بيانات المستخدم.

## ما تغير ولماذا

- `Dockerfile` + `docker/apache-security.conf`: Apache يرفض `/setup.php` والمجلدات الحساسة والملفات السرّية، بدون الاعتماد على `.htaccess`. صارت ملفات الويب root-owned وغير قابلة للكتابة بواسطة Apache.
- `docker/php-security.ini`: تعطيل عرض أخطاء PHP للزوار، وتفعيل HttpOnly وSameSite وstrict sessions في Docker، بدون إجبار Secure Cookie على HTTP الخاص بالتجربة المحلية.
- `docker-compose.yml`: استبدال كلمات المرور الثابتة بمتغيرات `.env`، إضافة MySQL healthcheck وشرط readiness وrestart policy، وربط HTTP على loopback فقط، مع إبقاء MySQL داخل شبكة Compose بدون `ports`.
- `.gitignore` و`.dockerignore` و`.env.example`: منع الأسرار والملفات الشخصية من الرفع والنسخ داخل صور Docker وتوضيح إعداد القيم.
- `setup.php`: لا تغيير كلمات مرور أو ترقية المستخدمين الموجودين عبر المعالج، ولا إنشاء Admin ثانٍ إذا يوجد واحد؛ الويب المحلي XAMPP فقط؛ المعالج Docker CLI، وفحص PHP 8.0+، وتجنب كتابة تكرار أسرار DB محليًا في Docker.
- `.htaccess`: تحسين دفاع XAMPP عند استخدام Apache مع AllowOverride.
- `config/db.php`: إخفاء رابط معالج التثبيت من صفحة خطأ قاعدة البيانات خارج localhost/XAMPP.
- `pages/login.php`: معالجة مدخلات POST غير النصية بدون TypeError. **لم يتغير SQL أو `password_verify()` أو أسماء الحقول أو Redirect الناجح.**
- `pages/signup.php`: إضافة CSRF لفورم التسجيل وFlash Success مرتبط بعملية إدخال حقيقية، مع معالجة malformed input، حدود أطوال متوافقة مع Schema، وحماية من تاريخ ميلاد غير صالح. بقيت أسماء الحقول والتصميم نفسه.
- `pages/dashboard.php`، `pages/my-courses.php`، `pages/update-profile.php`: تنظيف جلسات المستخدم المحذوف دون إظهار صفحة أو نجاح وهمي.
- `README.md` و`DEPLOY_AWS_AR.md`: تحديث خطوات التثبيت حتى لا تطلب زيارة installer غير آمن في Docker.

## ما لم يتغير

- كل ملفات `assets/` (CSS وJS والصور) **مطابقة byte-for-byte** للأصل.
- SQL Schema وعمليات CRUD وبيانات المستخدمين وطرق إنشاء Hash كلمات المرور لم تتغير.
- أسماء صفحات HTML وحقول Login ومسار نجاح Login لم تتغير.
- لا جدول جديد ولا Migration جديدة ولا نظام Rate Limit يحتاج `auth_rate_limits`.

## اختبارات التنفيذ وحدودها

- فحص صياغة ملفات PHP جميعها: PASS على PHP 8.4 (صورة Docker المستهدفة 8.2؛ تحتاج تجربة فعلية عليها).
- `node --check` على ملفات JavaScript: PASS.
- تحليل YAML: PASS.
- Apache config syntax: PASS، واختبار رفض ملفات حساسة عبر Apache محلي: PASS.
- مقارنة 12 ملف asset: PASS، بدون تغيير أي بايت.
- اختبارات Login وSignup باستخدام Fake DB مع `password_hash/password_verify`: PASS، **لكن هذه محاكاة ولا تُعد اختبارًا لـMySQL الحقيقي أو Docker**.
- Docker Build وPHP-MySQL end-to-end واختبارات بصرية من المتصفح: **NOT RUN** في بيئة الفحص المتاحة.

**قرار النشر:** لا تفتح الموقع للإنترنت قبل اختبار Docker/MySQL وLogin/Signup وAdmin على EC2، وضبط TLS/HTTPS. لا يمكن ضمان 100% خلو من الثغرات من المراجعة الساكنة.
