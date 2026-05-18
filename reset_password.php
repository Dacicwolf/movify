<?php
/**
 * Movify – Reset Password (via token link)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$token   = get_param('token');
$error   = '';
$success = false;

if (!$token) {
    redirect('forgot_password.php');
}

if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Cerere invalidă.';
    } else {
        $result = reset_password($pdo, post('token'), post('password'));
        if ($result['ok']) {
            flash('success', 'Parola a fost resetată cu succes. Autentifică-te.');
            redirect('login.php');
        }
        $error = $result['error'];
    }
}

$pageTitle = 'Resetare parolă';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md bg-dark-800 rounded-2xl p-8 shadow-xl border border-gray-700">
        <h2 class="text-2xl font-bold text-center mb-6">Resetare parolă</h2>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Parolă nouă</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500"
                       placeholder="Minim 8 caractere">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Resetează parola
            </button>
        </form>

        <p class="text-center text-gray-400 text-sm mt-6">
            <a href="login.php" class="text-primary-400 hover:underline">Înapoi la autentificare</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
