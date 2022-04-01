<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/01/2019
 * Time: 15:34
 */

namespace WebEtDesign\CmsBundle\DependencyInjection;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\Doctrine\Mapper\Builder\ColumnDefinitionBuilder;
use Sonata\Doctrine\Mapper\DoctrineCollector;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Attribute\AsCmsPageTemplate;
use WebEtDesign\CmsBundle\Attribute\AsCmsSharedBlock;
use WebEtDesign\CmsBundle\Entity\AbstractCmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Factory\SharedBlockFactory;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;

class WebEtDesignCmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $this->configureClass($config, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');
        $loader->load('admin.yaml');
        $loader->load('command.yaml');
        $loader->load('customContent.yaml');
        $loader->load('listener.yaml');
        $loader->load('menu.yaml');
        $loader->load('form.yaml');

        $this->registerDoctrineMapping($config);

        // TODO : work for autowired configuration
        $container->setParameter('wd_cms.menu.icon_set', []);
        $container->setParameter('wd_cms.cms', $config['cms']);
        $container->setParameter('wd_cms.cms.multisite',
            $config['cms']['multilingual'] || $config['cms']['multisite'] ? true : false);
        $container->setParameter('wd_cms.cms.multilingual', $config['cms']['multilingual']);
        $container->setParameter('wd_cms.cms.declination', $config['cms']['declination']);
        $container->setParameter('wd_cms.cms.page_extension', $config['cms']['page_extension']);
        $container->setParameter('wd_cms.templates', $config['pages']);
        $container->setParameter('wd_cms.shared_block', $config['sharedBlock']);
        $container->setParameter('wd_cms.custom_contents', $config['customContents']);

        $container->setParameter('wd_cms.vars', $config['cms']['vars']);

        $container->setParameter('wd_cms.menu', $config['menu']);

        $container->getDefinition(BlockFormThemesManager::class)
            ->addMethodCall('addThemes', [$config['customContentsFormThemes']]);

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['PrestaSitemapBundle'])) {
            $loader->load('sitemap.yaml');
        }

        if (method_exists($container, 'registerAttributeForAutoconfiguration')) {
            $container->registerAttributeForAutoconfiguration(AsCmsBlock::class,
                static function (ChildDefinition $definition, AsCmsBlock $attribute) {
                    $definition->addTag('wd_cms.block', array_filter([
                        'key'       => $attribute->name,
                        'formTheme' => $attribute->formTheme
                    ]));
                }
            );

            $container->registerAttributeForAutoconfiguration(AsCmsPageTemplate::class,
                static function (ChildDefinition $definition, AsCmsPageTemplate $attribute) {
                    $definition->addTag('wd_cms.page_template', array_filter([
                        'key' => $attribute->code,
                    ]));
                }
            );

            $container->registerAttributeForAutoconfiguration(AsCmsSharedBlock::class,
                static function (ChildDefinition $definition, AsCmsSharedBlock $attribute) {
                    $definition->addTag('wd_cms.shared_block', array_filter([
                        'key' => $attribute->code,
                    ]));
                }
            );
        }

        $container->getDefinition(BlockFactory::class)->setArguments([
            new ServiceLocatorArgument(new TaggedIteratorArgument('wd_cms.block', 'key', null, true)),
        ]);

        $container->getDefinition(PageFactory::class)->setArguments([
            new ServiceLocatorArgument(new TaggedIteratorArgument('wd_cms.page_template', 'key',
                null, true)),
            []
        ]);

        $container->getDefinition(SharedBlockFactory::class)->setArguments([
            new ServiceLocatorArgument(new TaggedIteratorArgument('wd_cms.shared_block', 'key',
                null, true)),
            []
        ]);
    }

    /**
     * @param array $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function configureClass($config, ContainerBuilder $container)
    {
        // manager configuration
        $container->setParameter('wd_cms.admin.content.user', $config['class']['user']);
    }

    public function getAlias()
    {
        return 'web_et_design_cms';
    }

    private function registerDoctrineMapping($config)
    {
        $collector = DoctrineCollector::getInstance();

        $collector->addInheritanceType(AbstractCmsRoute::class,
            ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
        $collector->addDiscriminator(AbstractCmsRoute::class, 'base', CmsRoute::class);
        $collector->addDiscriminatorColumn(AbstractCmsRoute::class,
            ColumnDefinitionBuilder::create()
                ->add('name', 'discr')
                ->add('type', 'string'));
        if ($config['admin']['configuration']['entity']['route'] !== CmsRoute::class) {
            $collector->addDiscriminator(AbstractCmsRoute::class, 'override',
                $config['admin']['configuration']['entity']['route']);
        }
    }
}
