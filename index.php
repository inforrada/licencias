<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/src/Auth.php';

if (Auth::check()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
