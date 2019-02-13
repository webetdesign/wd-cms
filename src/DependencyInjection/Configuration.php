<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/01/2019
 * Time: 16:27
 */

namespace WebEtDesign\CmsBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('web_et_design_cms');

        $rootNode
            ->children()
                ->arrayNode('admin')
                    ->children()
                        ->arrayNode('configuration')->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('class')
                                    ->children()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsContentAdmin')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsMenuAdmin')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsPageAdmin')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsRouteAdmin')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('controller')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsContentAdminController')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsMenuAdminController')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsPageAdminController')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsRouteAdminController')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('entity')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsContent')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsMenu')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsPage')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsRoute')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('class')
                    ->children()
                        ->scalarNode('user')->cannotBeEmpty()->end()
                        ->scalarNode('media')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('pages')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('controller')
                                ->defaultValue('WebEtDesign\\CmsBundle\\Controller\\CmsController')
//                                ->treatNullLike('WebEtDesign\\CmsBundle\\Controller\\CmsController')
                            ->end()
                            ->scalarNode('action')
                                ->defaultValue('index')
//                                ->treatNullLike('index')
                            ->end()
                            ->scalarNode('template')
                                ->defaultValue('integration/index.html.twig')
                                ->treatNullLike('integration/index.html.twig')
                            ->end()
                            ->arrayNode('contents')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('label')->cannotBeEmpty()->end()
                                        ->scalarNode('type')
                                            ->isRequired()
                                            ->validate()
                                                ->ifNotInArray(['TEXT', 'TEXTAREA', 'WYSYWYG', 'MEDIA'])
                                                ->thenInvalid('Invalid type %s')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
