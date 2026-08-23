<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/features/auth/queries.php';

auth_page_headers();
if (current_user() !== null) {
    header('Location: /');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $email       = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password    = (string) ($_POST['password'] ?? '');
    $old         = ['display_name' => $displayName, 'email' => $email];
    $pdo         = db();

    if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 60) {
        $errors[] = 'Display name must be 2–60 characters.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Enter a valid email address.';
    }
    if (mb_strlen($password) < 12) {
        $errors[] = 'Password must be at least 12 characters.';
    }
    if (strlen($password) > 72) {
        $errors[] = 'Password must be at most 72 bytes.';
    }

    if ($errors === []) {
        $existing = find_user_by_email($pdo, $email);
        if ($existing !== null) {
            // Anti-enumeration: same outward behavior as a fresh registration.
            send_app_mail($email, 'Your account already exists',
                "Someone tried to register this address. You already have an account — log in, or reset your password:\n" .
                config('APP_URL') . '/password/forgot');
        } else {
            $user = insert_user($pdo, $email, $displayName, password_hash($password, PASSWORD_DEFAULT), false);
            $token = bin2hex(random_bytes(32));
            insert_user_token($pdo, (int) $user['id'], 'email_verify', hash('sha256', $token), '7 days');
            send_app_mail($email, 'Verify your email',
                "Welcome to the study group! Verify your email:\n" .
                config('APP_URL') . '/verify?token=' . $token);
            log_activity($pdo, 'user_registered', [
                'actor_type' => 'user', 'actor_id' => (int) $user['id'], 'actor_label' => $displayName,
                'screen' => 'register', 'entity' => 'users', 'entity_id' => (int) $user['id'],
            ]);
        }
        flash_set('success', 'Check your email to finish setting up your account. You can log in now.');
        header('Location: /login');
        exit;
    }
}

$content = view('auth/register.php', ['errors' => $errors, 'old' => $old]);
echo view('auth/layout.php', ['title' => 'Register', 'content' => $content]);
