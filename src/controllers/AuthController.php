<?php
namespace App\controllers;

use App\Auth;

class AuthController
{
    public static function loginForm(): void
    {
        if (Auth::isLoggedIn()) {
            redirect('/');
        }
        renderWithLayout('login', [], __('login'));
    }

    public static function login(): void
    {
        if (Auth::isLoggedIn()) {
            redirect('/');
        }

        requireCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            flash('danger', __('login_error', ['error' => 'Username and password required.']));
            redirect('/login');
        }

        // Simple rate limiting
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['login_last_attempt'] ?? 0;

        if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
            flash('danger', __('login_error', ['error' => 'Too many attempts. Please wait 5 minutes.']));
            redirect('/login');
        }

        $result = Auth::login($username, $password);

        if ($result['success']) {
            // Reset rate limit
            unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
            session_regenerate_id(true);
            flash('success', __('login_success', ['username' => Auth::currentUser()['username']]));
            redirect('/');
        } else {
            $_SESSION['login_attempts'] = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            flash('danger', __('login_error', ['error' => $result['error']]));
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        Auth::logout();
        flash('info', __('logged_out'));
        redirect('/');
    }
}
