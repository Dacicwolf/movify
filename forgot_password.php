<?php
/**
 * Movify – Forgot Password
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$sent  = false;
$error = '';

if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Cerere invalidă.';
    } else {
        create_password_reset($pdo, post('email'));
        $sent = true;
    }
}

$pageTitle = 'Recuperare parolă';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md bg-dark-800 rounded-2xl p-8 shadow-xl border border-gray-700">
        <h2 class="text-2xl font-bold text-center mb-2">Recuperare parolă</h2>
        <p class="text-gray-400 text-center text-sm mb-6">
            Introdu adresa de email pentru a primi link-ul de resetare.
        </p>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($sent): ?>
            <div class="mb-4 p-3 rounded-lg bg-green-900/40 border border-green-700 text-green-300 text-sm">
                Dacă adresa există, vei primi un email cu instrucțiuni de resetare.
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500"
                           placeholder="exemplu@email.com">
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                    Trimite link de resetare
                </button>
            </form>
        <?php endif; ?>

        <p class="text-center text-gray-400 text-sm mt-6">
            <a href="<?= url('login.php') ?>" class="text-primary-400 hover:underline">Înapoi la autentificare</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
