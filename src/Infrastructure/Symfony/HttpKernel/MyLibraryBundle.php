<?php

namespace App\MyLibrary\Infrastructure\Symfony\HttpKernel;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A single class which integrate `Bundle`, `Extension`, `Configuration` and also the `PrependExtension`
 * to configure your reusable library.
 */
class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface, PrependExtensionInterface
{
    public const ALIAS = 'my_library';

    public function getPath(): string
    {
        return \dirname(__DIR__, 4);
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    public function prepend(ContainerBuilder $container): void
    {
        // define other bundle configurations
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);

        // define our configuration tree

        return $treeBuilder;
    }

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load our services
    }
}
