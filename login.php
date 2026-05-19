<?php
/**
 * Movify – Login
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error   = '';
$success = flash('success');

if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Cerere invalidă.';
    } else {
        $result = login_user($pdo, post('email'), post('password'));
        if ($result['ok']) {
            redirect('dashboard.php');
        }
        $error = $result['error'];

        if (!empty($result['needs_verify'])) {
            $_SESSION['verify_email'] = trim(strtolower(post('email')));
            redirect('verify.php');
        }
    }
}

$pageTitle = 'Autentificare';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md bg-dark-800 rounded-2xl p-8 shadow-xl border border-gray-700">
        <h2 class="text-2xl font-bold text-center mb-6">Autentificare</h2>

        <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-lg bg-green-900/40 border border-green-700 text-green-300 text-sm">
                <?= h($success) ?>
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
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= h(post('email')) ?>"
                       class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500"
                       placeholder="exemplu@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Parolă</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500"
                       placeholder="Introdu parola">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Autentificare
            </button>
        </form>

        <div class="flex justify-between text-sm mt-6 text-gray-400">
            <a href="<?= url('forgot_password.php') ?>" class="hover:text-primary-400 transition">Am uitat parola</a>
            <a href="<?= url('register.php') ?>" class="hover:text-primary-400 transition">Creează cont</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
