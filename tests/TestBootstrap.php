<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$testBootstrapper = null;
if (is_readable('/opt/share/shopware/tests/TestBootstrapper.php')) {
    $testBootstrapper = require '/opt/share/shopware/tests/TestBootstrapper.php';
} else {
    $testBootstrapper = new TestBootstrapper();
}

return $testBootstrapper
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addActivePlugins('FroshPlatformTemplateMail')
    ->bootstrap()
    ->getClassLoader();