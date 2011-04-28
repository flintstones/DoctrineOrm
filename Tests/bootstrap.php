<?php

require_once __DIR__.'/../vendor/silex/autoload.php';

$loader->registerNamespaces(array(
    'Doctrine\\Common' => __DIR__ . '/../vendor/doctrine-common/lib',
    'Doctrine\\DBAL' => __DIR__ . '/../vendor/doctrine-dbal/lib',
    'Doctrine\\ORM' => __DIR__ . '/../vendor/doctrine/lib'
));

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Flintstones\\DoctrineOrm\\')) {
        $path = implode('/', array_slice(explode('\\', $class), 2)).'.php';
        require_once __DIR__.'/../'.$path;
        return true;
    }
});

