<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/01/2019
 * Time: 16:27
 */

namespace WebEtDesign\CmsBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Entity\CmsGlobalVarsDelimiterEnum;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('web_et_design_cms');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('cms')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_home_template')->defaultValue('HOME')->end()
                        ->scalarNode('multisite')->defaultValue(false)->end()
                        ->scalarNode('multilingual')->defaultValue(false)->end()
                        ->scalarNode('declination')->defaultValue(false)->end()
                        ->scalarNode('page_extension')->defaultValue(false)->end()
                        ->scalarNode('menuByPage')->defaultValue(false)->end()
                        ->arrayNode('vars')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('enable')->defaultFalse()->end()
                                ->scalarNode('global_service')->defaultNull()->end()
                                ->enumNode('delimiter')
                                    ->values(CmsGlobalVarsDelimiterEnum::getAvailableTypes())
                                    ->defaultValue(CmsGlobalVarsDelimiterEnum::DOUBLE_UNDERSCORE)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('security')->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('page')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('enable')->defaultValue(false)->end()
                                        ->arrayNode('roles')
                                            ->beforeNormalization()
                                                ->ifString()
                                                ->then(function ($v) { return [$v]; })
                                            ->end()
                                            ->scalarPrototype()->end()
                                            ->defaultValue([])
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        $rootNode
            ->children()
                ->arrayNode('admin')->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('configuration')->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('class')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsContentAdmin')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsMenuAdmin')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsPageAdmin')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsRouteAdmin')->end()
                                        ->scalarNode('site')->defaultValue('WebEtDesign\\CmsBundle\\Admin\\CmsSiteAdmin')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('controller')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsContentAdminController')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsMenuAdminController')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsPageAdminController')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsRouteAdminController')->end()
                                        ->scalarNode('site')->defaultValue('WebEtDesign\\CmsBundle\\Controller\\Admin\\CmsSiteAdminController')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('entity')->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('shared_block')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsSharedBlock')->end()
                                        ->scalarNode('cms_content_has_shared_block')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsContentHasSharedBlock')->end()
                                        ->scalarNode('content')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsContent')->end()
                                        ->scalarNode('menu')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsMenuItem')->end()
                                        ->scalarNode('page')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsPage')->end()
                                        ->scalarNode('route')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsRoute')->end()
                                        ->scalarNode('route_interface')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsRouteInterface')->end()
                                        ->scalarNode('site')->defaultValue('WebEtDesign\\CmsBundle\\Entity\\CmsSite')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        $rootNode
            ->children()
                ->arrayNode('class')
                    ->children()
                        ->scalarNode('user')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('menu')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('service')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('pages')->setDeprecated('WdCms', '3.0.0', 'web_et_design_cms.pages is deprecated')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('controller')
                                ->defaultValue('WebEtDesign\\CmsBundle\\Controller\\CmsController')
//                                ->treatNullLike('WebEtDesign\\CmsBundle\\Controller\\CmsController')
                            ->end()
                            ->arrayNode('params')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('default')->end()
                                        ->scalarNode('requirement')->end()
                                        ->scalarNode('entity')->end()
                                        ->scalarNode('property')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('route')->defaultValue(null)->end()
                            ->scalarNode('path')->defaultValue(null)->end()
                            ->scalarNode('action')
                                ->defaultValue('index')
//                                ->treatNullLike('index')
                            ->end()
//                            ->fixXmlConfig('driver')
                            ->arrayNode('methods')
                                ->scalarPrototype()
                                    ->validate()
                                        ->ifNotInArray([
                                            Request::METHOD_GET,
                                            Request::METHOD_POST,
                                            Request::METHOD_PATCH,
                                            Request::METHOD_PUT,
                                            Request::METHOD_PURGE,
                                            Request::METHOD_DELETE,
                                            Request::METHOD_CONNECT
                                        ])
                                        ->thenInvalid('Invalid type %s')
                                    ->end()
                                ->end()
                                ->defaultValue([Request::METHOD_GET])
                            ->end()
                            ->scalarNode('template')
                                ->defaultValue('integration/index.html.twig')
                                ->treatNullLike('integration/index.html.twig')
                            ->end()
                            ->arrayNode('association')
                                ->children()
                                    ->scalarNode('class')->cannotBeEmpty()->end()
                                    ->scalarNode('queryMethod')
                                        ->defaultValue('findAll')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('contents')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('code')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('label')->cannotBeEmpty()->end()
                                        ->scalarNode('type')->isRequired()->end()
                                        ->scalarNode('help')->defaultNull()->end()
                                        ->scalarNode('open')->defaultFalse()->end()
                                        ->arrayNode('options')->scalarPrototype()->defaultValue([])->end()->end()
                                        ->arrayNode('form_options')->scalarPrototype()->defaultValue([])->end()->end()
                                        ->arrayNode('settings')->scalarPrototype()->defaultValue([])->end()->end()
                                        ->arrayNode('blocks')->arrayPrototype()->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('entityVars')->defaultNull()->end()
                            ->scalarNode('disableRoute')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sharedBlock')->setDeprecated('WdCms', '3.0.0', 'web_et_design_cms.sharedBlock is deprecated')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('template')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('contents')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('code')->cannotBeEmpty()->end()
                                        ->scalarNode('label')->cannotBeEmpty()->end()
                                        ->scalarNode('type')->isRequired()->end()
                                        ->scalarNode('help')->defaultNull()->end()
                                        ->arrayNode('options')->prototype('variable')->defaultValue([])->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('customContents')->setDeprecated('WdCms', '3.0.0', 'web_et_design_cms.customContents is deprecated')
                    ->useAttributeAsKey('code')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('service')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('customContentsFormThemes')
                    ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
