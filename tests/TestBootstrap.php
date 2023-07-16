<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('FroshPlatformTemplateMail')
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Frosh\\TemplateMail\\Tests\\', __DIR__);
