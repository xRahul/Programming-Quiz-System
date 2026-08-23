<?php

declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'debug');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

define('SITE_NAME', 'Programming Quiz System');
define('SITE_LOGO', 'img/header.jpg');
define('FOOTER_HTML', '');
