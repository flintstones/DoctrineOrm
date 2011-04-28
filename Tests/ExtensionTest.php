<?php

namespace Slinko\DoctrineOrm\Tests;

use PHPUnit_Framework_TestCase;
use Silex\Application;
use Slinko\DoctrineOrm\Extension as DoctrineOrmExtension;

class ExtensionTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $ormOptions = array(
            'em.proxy_dir' => __DIR__,
            'em.proxy_namespace' => __NAMESPACE__,
            'em.entities' => array(
                array('type' => 'annotation', 'path' => __DIR__, 'namespace' => 'annotation'),
                array('type' => 'yml', 'path' => __DIR__, 'namespace' => 'yml'),
                array('type' => 'xml', 'path' => __DIR__, 'namespace' => 'xml'),
            ),
        );

        $app->register(new DoctrineOrmExtension(), $ormOptions);

        $this->assertInstanceOf('Doctrine\\ORM\\EntityManager', $app['em']);
        $this->assertInstanceOf('Doctrine\\ORM\\Configuration', $app['em.config']);
        $this->assertInstanceOf('Doctrine\\Common\\Cache\\ApcCache', $app['em.cache']);

        $this->assertInstanceOf('Doctrine\\Common\\Cache\\ApcCache', $app['em.config']->getMetadataCacheImpl());
        $this->assertInstanceOf('Doctrine\\Common\\Cache\\ApcCache', $app['em.config']->getQueryCacheImpl());

        $this->assertInstanceOf('Doctrine\\ORM\\Mapping\\Driver\\DriverChain', $app['em.config']->getMetadataDriverImpl());
        $drivers = $app['em.config']->getMetadataDriverImpl()->getDrivers();
        $this->assertEquals(3, count($drivers));

        $this->assertArrayHasKey('annotation', $drivers);
        $this->assertInstanceOf('Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver', $drivers['annotation']);

        $this->assertArrayHasKey('yml', $drivers);
        $this->assertInstanceOf('Doctrine\\ORM\\Mapping\\Driver\\YamlDriver', $drivers['yml']);

        $this->assertArrayHasKey('xml', $drivers);
        $this->assertInstanceOf('Doctrine\\ORM\\Mapping\\Driver\\XmlDriver', $drivers['xml']);

        $paths = $drivers['xml']->getPaths();
        $this->assertEquals(1, count($paths));
        $this->assertEquals($ormOptions['em.entities'][0]['path'], array_shift($paths));

        $this->assertEquals($ormOptions['em.proxy_dir'], $app['em.config']->getProxyDir());
        $this->assertEquals($ormOptions['em.proxy_namespace'], $app['em.config']->getProxyNamespace());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterWithWrongEntities()
    {
        $app = new Application();

        $ormOptions = array(
            'em.entities' => array(
                array('type' => 'wrong-type', 'path' => __DIR__, 'namespace' => 'annotation'),
            ),
        );

        $app->register(new DoctrineOrmExtension(), $ormOptions);

        $app['em.config'];
    }

    public function testRegisterNamespace()
    {
        $app = new Application();

        $ormOptions = array(
            'db.orm.class_path' => __DIR__
        );

        $app->register(new DoctrineOrmExtension(), $ormOptions);

        $namespaces = $app['autoloader']->getNamespaces();

        $this->assertEquals(1, count($namespaces));
        $this->assertArrayHasKey('Doctrine\\ORM', $namespaces);
        $this->assertEquals(1, count($namespaces['Doctrine\\ORM']));
        $this->assertEquals(__DIR__, array_shift($namespaces['Doctrine\\ORM']));
    }
}
