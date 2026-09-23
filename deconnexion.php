<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\ClientAuth;
use KooKin\Core\Flash;

ClientAuth::logout();
Flash::success('Vous êtes bien déconnecté. À très bientôt !');
redirect('/');