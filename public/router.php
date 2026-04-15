<?php

if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $url;

    if (is_file($file)) {
        return false;
    }
}

$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/../vendor/autoload_runtime.php';

return function (array $context) {
    $installed = (string) (
        $_SERVER['APP_INSTALLED']
        ?? $_ENV['APP_INSTALLED']
        ?? getenv('APP_INSTALLED')
        ?? '0'
    );

    if ($installed !== '1') {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('./main/install/index.php');
    }

    return new \Chamilo\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
