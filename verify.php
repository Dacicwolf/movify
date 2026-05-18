<?php
/**
 * Movify – Email Verification
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$email = $_SESSION['verify_email'] ?? '';
if (!$email) {
    redirect('register.php');
}

$error   = '';
$success = '';

if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Cerere invalidă.';
    } else {
        $code = post('code');
        if (verify_email($pdo, $email, $code)) {
            unset($_SESSION['verify_email']);
            flash('success', 'Contul a fost verificat! Poți să te autentifici.');
            redirect('login.php');
        }
        $error = 'Cod incorect. Verifică și încearcă din nou.';
    }
}

$pageTitle = 'Verificare email';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md bg-dark-800 rounded-2xl p-8 shadow-xl border border-gray-700">
        <h2 class="text-2xl font-bold text-center mb-2">Verificare email</h2>
        <p class="text-gray-400 text-center text-sm mb-6">
            Am trimis un cod de 6 cifre la <span class="text-primary-400"><?= h($email) ?></span>
        </p>

        <?php if (!empty($_SESSION['verify_code_hint']) && (SMTP_USER === '' || SMTP_PASS === '')): ?>
            <div class="mb-4 p-3 rounded-lg bg-yellow-900/40 border border-yellow-600 text-yellow-300 text-sm text-center">
                <strong>Dev mode</strong> – SMTP nu este configurat.<br>
                Codul tău de verificare: <span class="text-2xl font-mono font-bold tracking-widest text-white"><?= h($_SESSION['verify_code_hint']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="code" class="block text-sm font-medium text-gray-300 mb-1">Cod de verificare</label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}"
                       class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white text-center text-2xl tracking-[0.5em] placeholder-gray-500"
                       placeholder="000000" autocomplete="one-time-code">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Verifică
            </button>
        </form>

        <p class="text-center text-gray-400 text-sm mt-6">
            Nu ai primit codul?
            <a href="register.php" class="text-primary-400 hover:underline">Reînregistrează-te</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
