<?php

namespace Slinko\DoctrineOrm;

use Silex\Application;
use Silex\ExtensionInterface;
use Silex\Extension\DoctrineExtension;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ApcCache;

class Extension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app->register(new DoctrineExtension());

        $app['em'] = $app->share(function () use ($app) {
            return EntityManager::create($app['db'], $app['em.config'], $app['db.event_manager']);
        });

        $app['em.config'] = $app->share(function () use ($app) {
            $config = new Configuration();

            $config->setMetadataCacheImpl($app['em.cache']);
            $config->setQueryCacheImpl($app['em.cache']);

            if (isset($app['em.proxy_dir'])) {
                $config->setProxyDir($app['em.proxy_dir']);
            }

            if (isset($app['em.proxy_namespace'])) {
                $config->setProxyNamespace($app['em.proxy_namespace']);
            }

            if (isset($app['em.entities'])) {
                $chain = new DriverChain();
                foreach ($app['em.entities'] as $entity) {
                    $pathes = (array)$entity['path'];
                    $namespace = $entity['namespace'];
                    switch ($entity['type']) {
                        case 'annotation':
                            $reader = new AnnotationReader();
                            $reader->setAnnotationNamespaceAlias('Doctrine\\ORM\\Mapping\\', 'orm');
                            $chain->addDriver(new AnnotationDriver($reader, $pathes), $namespace);
                            break;
                        case 'yml':
                            $driver = new YamlDriver($pathes);
                            $driver->setFileExtension('.yml');
                            $chain->addDriver($driver, $namespace);
                            break;
                        case 'xml':
                            $chain->addDriver(new XmlDriver($pathes), $namespace);
                            break;
                        default:
                            throw new \InvalidArgumentException('"' . $entity['type'] . '" is not a recognized driver');
                            break;
                    }
                }
                $config->setMetadataDriverImpl($chain);
            }

            return $config;
        });

        $app['em.cache'] = $app->share(function () {
            return new ApcCache();
        });

        if (isset($app['db.orm.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\ORM', $app['db.orm.class_path']);
        }
    }
}
