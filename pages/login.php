<?php
session_start();
require_once __DIR__ . "/../config/db.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Receive form data
    $rawEmail = $_POST["email"] ?? "";
    $rawPassword = $_POST["password"] ?? "";
    $email = is_string($rawEmail) ? trim($rawEmail) : "";
    $password = is_string($rawPassword) ? $rawPassword : "";

    // 2. Check empty fields
    if ($email === "" || $password === "") {
        $errors[] = "Email and password are required";
    }

    // 3. Validate email format
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // 4. Search for user in database
    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, password_hash
             FROM users
             WHERE email = ?"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = "Invalid email or password";
        } elseif (!password_verify($password, $user["password_hash"])) {
            $errors[] = "Invalid email or password";
        } else {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            header("Location:dashboard.php");
            exit;
        }
    }
}

?>

<?php require_once __DIR__ . "/../includes/layout.php"; sh_head("Sign In", "../", "auth-page"); ?>

<main id="main" class="relative grid min-h-screen lg:grid-cols-2">
    <aside class="relative hidden overflow-hidden border-r border-white/10 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <canvas class="hero-canvas" data-scene="auth" aria-hidden="true"></canvas>
        <a class="relative flex items-center gap-3 font-display text-xl font-bold" href="../index.php">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-violet-400 to-blue-500 shadow-lg shadow-violet-500/30"><i class="fa-solid fa-star-of-life text-sm"></i></span>
            StudentHub
        </a>
        <div class="relative max-w-md">
            <span class="chip" data-intro>Welcome back</span>
            <h1 class="font-display mt-4 text-5xl font-bold leading-[1.05] tracking-tight" data-intro>Keep your <span class="gradient-text">momentum.</span></h1>
            <p class="mt-5 text-slate-300" data-intro>Every learning session brings you closer to your goals. Pick up where you left off.</p>
            <ul class="mt-8 space-y-3 text-slate-300" data-intro>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Your progress matters</li>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Small steps, big results</li>
                <li class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-cyan-300"></i>Keep building your future</li>
            </ul>
        </div>
        <p class="relative text-sm text-slate-500">© 2026 StudentHub. Keep learning.</p>
    </aside>
    <section class="relative grid place-items-center px-5 py-12">
        <a class="absolute left-5 top-5 flex items-center gap-2 font-display font-bold lg:hidden" href="../index.php"><i class="fa-solid fa-star-of-life text-violet-300"></i>StudentHub</a>
        <div class="tilt-wrap w-full max-w-md">

            <div class="glass-strong relative rounded-3xl p-8 shadow-2xl shadow-violet-900/30 sm:p-10" data-tilt="7">
                <div class="tilt-shine"></div>
                <div class="tilt-pop">
                    <span class="chip">Your learning space</span>
                    <h2 class="font-display mt-3 text-3xl font-bold">Welcome back</h2>
                    <p class="mt-1 text-slate-400">Sign in to continue your journey.</p>

                    <!-- Display validation errors -->
                    <?php foreach ($errors as $error): ?>
                        <p class="alert alert-err mt-5" role="alert"><i class="fa-solid fa-triangle-exclamation mt-0.5"></i><span><?= htmlspecialchars($error) ?></span></p>
                    <?php endforeach; ?>

                    <!-- Login form (same field names as before: email, password) -->
                    <form class="mt-7 space-y-4" method="post" action="login.php" novalidate>
                        <div class="fl">
                            <input id="email" name="email" type="email" autocomplete="email" placeholder=" " value="<?= htmlspecialchars(is_string($_POST["email"] ?? null) ? $_POST["email"] : "", ENT_QUOTES, "UTF-8") ?>">
                            <label for="email">Email Address</label>
                        </div>
                        <div class="fl">
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder=" ">
                            <label for="password">Password</label>
                        </div>
                        <button class="btn-primary w-full" type="submit">Sign In <i class="fa-solid fa-arrow-right"></i></button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-400">New to StudentHub? <a class="font-semibold text-cyan-300 hover:underline" href="signup.php">Create an account</a></p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php sh_scripts(["hero"]); ?>
