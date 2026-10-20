<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$errors = [];
$success = "";
if (!empty($_SESSION["signup_success"])) {
    $success = "Account created successfully!";
    unset($_SESSION["signup_success"]);
}
if (empty($_SESSION["signup_csrf"])) {
    $_SESSION["signup_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Array-shaped POST inputs must not cause PHP TypeError / HTTP 500.
    $str = static fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : "";
    $fullname = trim($str("full_name"));
    $email = trim($str("email"));
    $mobile = trim($str("mobile"));
    $password = $str("password");
    $confirmpassword = $str("confirm_password");
    $day = $str("day");
    $month = $str("month");
    $year = $str("year");

    $postedToken = $_POST["signup_csrf"] ?? null;
    if (!is_string($postedToken) || !hash_equals($_SESSION["signup_csrf"], $postedToken)) {
        $errors[] = "Invalid form token. Refresh this page and try again.";
    }

    if (
        $fullname === "" || $email === "" || $mobile === "" ||
        $password === "" || $confirmpassword === "" ||
        $day === "" || $month === "" || $year === ""
    ) {
        $errors[] = "All fields are required";
    }

    if ($fullname !== "" && mb_strlen($fullname, "UTF-8") > 100) {
        $errors[] = "Full name must be 100 characters or fewer";
    }
    if ($email !== "" && strlen($email) > 191) {
        $errors[] = "Email address is too long";
    }

    if ($fullname !== "") {
        $names = preg_split('/\s+/u', $fullname);
        if (count($names) !== 4) {
            $errors[] = "Full name must contain exactly 4 names";
        }
        if (!preg_match('/^[\p{L}\s]+$/u', $fullname)) {
            $errors[] = "Full name must contain letters only";
        }
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if ($mobile !== "" && !preg_match('/^[0-9]{14}$/', $mobile)) {
        $errors[] = "Mobile number must contain exactly 14 digits";
    }

    if ($password !== "" && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($password !== "" && $confirmpassword !== "" && $password !== $confirmpassword) {
        $errors[] = "Passwords do not match";
    }

    if ($day !== "" && $month !== "" && $year !== "") {
        if (
            !ctype_digit((string)$day) || !ctype_digit((string)$month) ||
            !ctype_digit((string)$year) ||
            strlen($year) !== 4 || (int)$year < 1900 ||
            !checkdate((int)$month, (int)$day, (int)$year)
        ) {
            $errors[] = "Please enter a valid date of birth";
        } elseif (sprintf("%04d-%02d-%02d", $year, $month, $day) > date("Y-m-d")) {
            $errors[] = "Date of birth cannot be in the future";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $errors[] = "Email is already registered";
            }

            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $dateOfBirth = sprintf("%04d-%02d-%02d", $year, $month, $day);

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                     (full_name, email, mobile, password_hash, date_of_birth)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $fullname,
                    $email,
                    $mobile,
                    $hashedPassword,
                    $dateOfBirth
                ]);
                $_SESSION["signup_success"] = true;
                header("Location:signup.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            if ((string) $e->getCode() === "23000") {
                $errors[] = "Email is already registered";
            } else {
                $errors[] = "Something went wrong. Please try again.";
                error_log($e->getMessage());
            }
        }
    }
}
?>
<?php require_once __DIR__ . "/../includes/layout.php"; sh_head("Create Account", "../", "auth-page"); ?>

<main id="main" class="relative grid min-h-screen lg:grid-cols-2">
    <aside class="relative hidden overflow-hidden border-r border-white/10 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <canvas class="hero-canvas" data-scene="auth" aria-hidden="true"></canvas>
        <a class="relative flex items-center gap-3 font-display text-xl font-bold" href="../index.php">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-violet-400 to-blue-500 shadow-lg shadow-violet-500/30"><i class="fa-solid fa-star-of-life text-sm"></i></span>
            StudentHub
        </a>
        <div class="relative max-w-md">
            <span class="chip" data-intro>Your learning space</span>
            <h1 class="font-display mt-4 text-5xl font-bold leading-[1.05] tracking-tight" data-intro>Learn. Grow. <span class="gradient-text">Succeed.</span></h1>
            <p class="mt-5 text-slate-300" data-intro>Build skills, stay motivated, and turn your goals into real progress.</p>
            <ul class="mt-8 space-y-3 text-slate-300" data-intro>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Track your learning journey</li>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Stay organized and inspired</li>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Celebrate every achievement</li>
            </ul>
        </div>
        <p class="relative text-sm text-slate-500">© 2026 StudentHub. Keep learning.</p>
    </aside>
    <section class="relative grid place-items-center px-5 py-12">
        <a class="absolute left-5 top-5 flex items-center gap-2 font-display font-bold lg:hidden" href="../index.php"><i class="fa-solid fa-star-of-life text-violet-300"></i>StudentHub</a>
        <div class="tilt-wrap w-full max-w-md">

            <div class="glass-strong relative rounded-3xl p-8 shadow-2xl shadow-violet-900/30 sm:p-10" data-tilt="5">
                <div class="tilt-shine"></div>
                <div class="tilt-pop">
                    <span class="chip">Get started</span>
                    <h2 class="font-display mt-3 text-3xl font-bold">Create your account</h2>
                    <p class="mt-1 text-slate-400">Your learning journey begins here.</p>

                    <?php foreach ($errors as $error): ?>
                        <p class="alert alert-err mt-5" role="alert"><i class="fa-solid fa-triangle-exclamation mt-0.5"></i><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></p>
                    <?php endforeach; ?>

                    <?php if ($success !== ""): ?>
                        <p class="alert alert-ok mt-5" role="status"><i class="fa-solid fa-circle-check mt-0.5"></i><span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a class="font-semibold underline" href="login.php">Sign in now</a></span></p>
                    <?php endif; ?>

                    <!-- Same field names as before: full_name, email, mobile, password, confirm_password, day, month, year -->
                    <form class="mt-7 space-y-4" method="post" action="signup.php" novalidate>
                        <input type="hidden" name="signup_csrf" value="<?= htmlspecialchars($_SESSION["signup_csrf"], ENT_QUOTES, "UTF-8") ?>">
                        <div class="fl">
                            <input id="full_name" name="full_name" type="text" placeholder=" " autocomplete="name" value="<?= htmlspecialchars(is_string($_POST["full_name"] ?? null) ? $_POST["full_name"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="full_name">Full Name (four names)</label>
                        </div>
                        <div class="fl">
                            <input id="email" name="email" type="email" placeholder=" " autocomplete="email" value="<?= htmlspecialchars(is_string($_POST["email"] ?? null) ? $_POST["email"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="email">Email Address</label>
                        </div>
                        <div class="fl">
                            <input id="mobile" name="mobile" type="text" placeholder=" " inputmode="numeric" maxlength="14" autocomplete="tel" value="<?= htmlspecialchars(is_string($_POST["mobile"] ?? null) ? $_POST["mobile"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="mobile">Mobile Number (14 digits)</label>
                        </div>
                        <div class="fl">
                            <input id="password" name="password" type="password" autocomplete="new-password" placeholder=" ">
                            <label for="password">Password</label>
                        </div>
                        <div class="fl">
                            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder=" ">
                            <label for="confirm_password">Confirm Password</label>
                        </div>
                        <fieldset>
                            <legend class="mb-2 text-sm font-medium text-slate-300">Date of Birth</legend>
                            <div class="grid grid-cols-3 gap-3">
                        <div class="fl">
                            <input id="day" name="day" type="number" placeholder=" " min="1" max="31" value="<?= htmlspecialchars(is_string($_POST["day"] ?? null) ? $_POST["day"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="day">Day</label>
                        </div>
                        <div class="fl">
                            <input id="month" name="month" type="number" placeholder=" " min="1" max="12" value="<?= htmlspecialchars(is_string($_POST["month"] ?? null) ? $_POST["month"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="month">Month</label>
                        </div>
                        <div class="fl">
                            <input id="year" name="year" type="number" placeholder=" " min="1900" value="<?= htmlspecialchars(is_string($_POST["year"] ?? null) ? $_POST["year"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="year">Year</label>
                        </div>
                            </div>
                        </fieldset>
                        <button class="btn-primary w-full" type="submit">Create Account <i class="fa-solid fa-arrow-right"></i></button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-400">Already have an account? <a class="font-semibold text-cyan-300 hover:underline" href="login.php">Sign in</a></p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php sh_scripts(["hero"]); ?>
