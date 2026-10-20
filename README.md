# StudentHub 🎓

منصة تسجيل كورسات وتعلّم للطلاب — PHP + MySQL مع واجهة حديثة (Tailwind CSS · GSAP · Three.js · Chart.js).

> Student course registration & learning platform — PHP / MySQL with an immersive frontend.

## المزايا
- تسجيل حساب وتسجيل دخول (جلسات + `password_hash` + حماية CSRF)
- كتالوج كورسات، تسجيل الطالب في الكورسات، لوحة تحكم للطالب مع رسومات بيانية
- لوحة إدارة: إضافة/تعديل/حذف الكورسات، وإدارة صلاحيات المستخدمين (admin / student)

## المتطلبات
- PHP 8.0 أو أحدث (Docker يستخدم PHP 8.2) مع `pdo_mysql` و `mbstring`
- MySQL 5.7+ أو MariaDB 10.3+
- اتصال إنترنت (المكتبات تُحمَّل من CDN)

## التشغيل السريع (3 طرق — اختر واحدة)

### 1) XAMPP (الأسهل على ويندوز)
1. انسخ مجلد المشروع إلى `C:\xampp\htdocs\StudentHub`
2. شغّل **Apache** و **MySQL** من لوحة XAMPP
3. افتح محليًا فقط: `http://localhost/StudentHub/setup.php` واملأ النموذج (على XAMPP: المستخدم `root` وكلمة المرور فارغة)
4. بعد النجاح **احذف ملف `setup.php`** ثم افتح `http://localhost/StudentHub/`

### 2) سيرفر PHP المدمج (بدون Apache)
```bash
./start.sh        # لينكس / ماك
start.bat         # ويندوز (انقر مرتين)
```
ثم أكمل المعالج في `http://localhost:8000/setup.php` (يلزم تشغيل MySQL).

أو من الطرفية بدون متصفح:
```bash
php setup.php
```
لا تكتب كلمات المرور في وسائط الأوامر أو سجل الطرفية.

### 3) Docker / AWS EC2 (تجربة محلية آمنة)
```bash
cp .env.example .env       # غيّر كلمات المرور في .env قبل التشغيل
chmod 600 .env             # لينكس فقط
docker compose config -q
docker compose up -d --build
docker compose exec web php setup.php
```
اتبع التعليمات التفاعلية لإنشاء أول Admin بدون تمرير كلمة مرور في سطر الأوامر.
سيظهر الموقع على `http://127.0.0.1:8080` **من داخل نفس السيرفر فقط**.
`setup.php` ممنوع من الوصول بالمتصفح في Docker، ولا تفتح منفذ 8080 للعامة.
راجع **`DEPLOY_AWS_AR.md`** لاختبار الوصول من AWS عبر Session Manager ثم تجهيز HTTPS.

## ماذا يفعل `setup.php`؟
يفحص المتطلبات ← ينشئ قاعدة البيانات والجداول من `database/schema.sql` ← (اختياري) يضيف كورسات تجريبية ← ينشئ أول حساب Admin ← يكتب `config/config.local.php` ← يقفل نفسه عبر `config/installed.lock`.

## هيكل المشروع
```text
StudentHub/
├── setup.php               # معالج التثبيت (ملف التشغيل الرئيسي)
├── index.php               # الصفحة الرئيسية
├── database/               # schema.sql + sample-data.sql
├── config/db.php           # الاتصال بقاعدة البيانات (env / config.local.php / افتراضي XAMPP)
├── includes/layout.php     # قالب الواجهة المشترك
├── assets/                 # css / js (theme.css, app.js, hero3d.js, dashboard.js)
├── pages/                  # باقي الصفحات ومعالجات النماذج
├── Dockerfile · docker-compose.yml · start.sh · start.bat
└── .gitignore
```

## رفع المشروع إلى GitHub
```bash
cd StudentHub
git init
git add .
git commit -m "Initial commit: StudentHub"
git branch -M main
git remote add origin https://github.com/<USERNAME>/StudentHub.git
git push -u origin main
```
> الملف `.gitignore` يمنع رفع `config/config.local.php` (فيه كلمة مرور قاعدة البيانات) و `installed.lock`.
> لا ترفع كلمات مرور حقيقية أبداً، ولا ملف `.env`. تحقق من `git status` قبل الرفع.

## ملاحظات
- أرقام التقدّم وساعات الدراسة في لوحة الطالب، ومحتوى الدروس/المدرّب/التقييمات في صفحة الكورس، **بيانات تجريبية** إلى أن تضيف جداولها (انظر تعليقات `PLACEHOLDER`).
- شروط التسجيل في النموذج: الاسم من 4 كلمات، الجوال 14 رقماً، كلمة المرور 8 أحرف على الأقل.


## ملاحظات أمنية للتجربة
- لا تستخدم `docker compose down -v` على قاعدة تحتوي بيانات تريد الاحتفاظ بها؛ الخيار `-v` يحذف الـVolume.
- لا تنشر Demo بلا HTTPS وتقييد وصول أو حسابات تجريبية.
- القيم المعروضة للتقدم الدراسي تجريبية وليست بيانات LMS حقيقية.
- ملفات التصميم الأصلية لم تُمس؛ راجع `SECURITY_FIXES_AR.md` لسجل الإصلاحات وحدود الاختبارات.
