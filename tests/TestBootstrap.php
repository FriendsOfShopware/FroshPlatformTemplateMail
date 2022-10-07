<?php declare(strict_types=1);

use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

function getProjectDir(): string
{
    if (isset($_SERVER['PROJECT_ROOT']) && \file_exists($_SERVER['PROJECT_ROOT'])) {
        return $_SERVER['PROJECT_ROOT'];
    }
    if (isset($_ENV['PROJECT_ROOT']) && \file_exists($_ENV['PROJECT_ROOT'])) {
        return $_ENV['PROJECT_ROOT'];
    }

    $rootDir = dirname(__DIR__, 2);
    $dir = $rootDir;
    while (!\file_exists($dir . '/.env')) {
        if ($dir === \dirname($dir)) {
            return $rootDir;
        }
        $dir = \dirname($dir);
    }

    return $dir;
}

$testProjectDir = getProjectDir();

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require $testProjectDir . '/vendor/autoload.php';
KernelLifecycleManager::prepare($loader);
$loader->addPsr4('Frosh\\TemplateMail\\Tests\\', dirname(__DIR__) . '/tests');

if (class_exists('\Shopware\Core\Kernel')) {
    $_SERVER['KERNEL_CLASS'] = '\Shopware\Core\Kernel';
} else if (class_exists('\Shopware\Development\Kernel')) {
    $_SERVER['KERNEL_CLASS'] = '\Shopware\Development\Kernel';
} else if(class_exists('Shopware\Production\Kernel')) {
    $_SERVER['KERNEL_CLASS'] = 'Shopware\Production\Kernel';
} else {
    throw new \RuntimeException('Cannot detect kernel class by own.');
}

(new Dotenv(true))->load($testProjectDir . '/.env');

$dbUrl = \getenv('DATABASE_URL');
if ($dbUrl !== false) {
    \putenv('DATABASE_URL=' . $dbUrl . '_test');
}

// Creates DB connection
KernelLifecycleManager::bootKernel();
