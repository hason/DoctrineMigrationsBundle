<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function constant;
use function in_array;
use function is_string;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

/**
 * DoctrineMigrationsExtension configuration structure.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder The config tree builder
     */
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('doctrine_migrations', 'array');

        $organizeMigrationModes = $this->getOrganizeMigrationsModes();

        $rootNode
            ->children()
                ->scalarNode('dir_name')->defaultValue('%kernel.root_dir%/DoctrineMigrations')->cannotBeEmpty()->end()
                ->scalarNode('namespace')->defaultValue('Application\Migrations')->cannotBeEmpty()->end()
                ->scalarNode('table_name')->defaultValue('migration_versions')->cannotBeEmpty()->end()
                ->scalarNode('column_name')->defaultValue('version')->end()
                ->scalarNode('column_length')->defaultValue(255)->end()
                ->scalarNode('executed_at_column_name')->defaultValue('executed_at')->end()
                ->scalarNode('all_or_nothing')->defaultValue(false)->end()
                ->scalarNode('name')->defaultValue('Application Migrations')->end()
                ->scalarNode('custom_template')->defaultValue(null)->end()
                ->scalarNode('organize_migrations')->defaultValue(false)
                    ->info('Organize migrations mode. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false')
                    ->validate()
                        ->ifTrue(function ($v) use ($organizeMigrationModes) {
                            if ($v === false) {
                                return false;
                            }

                            if (is_string($v) && in_array(strtoupper($v), $organizeMigrationModes)) {
                                return false;
                            }

                            return true;
                        })
                        ->thenInvalid('Invalid organize migrations mode value %s')
                    ->end()
                    ->validate()
                        ->ifString()
                            ->then(function ($v) {
                                return constant('Doctrine\Migrations\Configuration\Configuration::VERSIONS_ORGANIZATION_' . strtoupper($v));
                            })
                        ->end()
                    ->end()
            ->end()
        ;

        return $treeBuilder;
    }


    /**
     * Find organize migrations modes for their names
     *
     * @return string[]
     */
    private function getOrganizeMigrationsModes() : array
    {
        $constPrefix = 'VERSIONS_ORGANIZATION_';
        $prefixLen   = strlen($constPrefix);
        $refClass    = new \ReflectionClass('Doctrine\Migrations\Configuration\Configuration');
        $constsArray = $refClass->getConstants();
        $namesArray  = [];

        foreach ($constsArray as $key => $value) {
            if (strpos($key, $constPrefix) !== 0) {
                continue;
            }

            $namesArray[] = substr($key, $prefixLen);
        }

        return $namesArray;
    }
}
