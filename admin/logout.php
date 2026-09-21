<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::logout();
Flash::info('Vous êtes déconnecté.');
admin_redirect('login.php');
