<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/01/2019
 * Time: 15:34
 */

namespace WebEtDesign\CmsBundle\DependencyInjection;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
use WebEtDesign\CmsBundle\Attribute\AsCmsConfiguration;
use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\Attribute\AsCmsShared;
use WebEtDesign\CmsBundle\Attribute\AsCmsTemplate;
use WebEtDesign\CmsBundle\Entity\AbstractCmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Enum\CmsVarsDelimiterEnum;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class WebEtDesignCmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $config['cms']['vars']['delimiter'] = CmsVarsDelimiterEnum::from($config['cms']['vars']['delimiter']);

        $this->configureClass($config, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');
        $loader->load('admin.yaml');
        $loader->load('command.yaml');
        $loader->load('listener.yaml');
        $loader->load('menu.yaml');
        $loader->load('form.yaml');

        $this->registerDoctrineMapping($config);

        // TODO : work for autowired configuration
        $container->setParameter('wd_cms.menu.icon_set', []);
        $container->setParameter('wd_cms.cms', $config['cms']);
        $container->setParameter('wd_cms.cms.multisite', $config['cms']['multilingual'] || $config['cms']['multisite'] ? true : false);
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

            $container->registerAttributeForAutoconfiguration(AsCmsPage::class,
                static function (ChildDefinition $definition, AsCmsPage $attribute) {
                    $definition->addTag('wd_cms.template', array_filter([
                        'key'  => $attribute->code,
                        'type' => $attribute->type,
                    ]));
                }
            );

            $container->registerAttributeForAutoconfiguration(AsCmsShared::class,
                static function (ChildDefinition $definition, AsCmsShared $attribute) {
                    $definition->addTag('wd_cms.template', array_filter([
                        'key'  => $attribute->code,
                        'type' => $attribute->type,
                    ]));
                }
            );

            $container->registerAttributeForAutoconfiguration(AsCmsTemplate::class,
                static function (ChildDefinition $definition, AsCmsTemplate $attribute) {
                    $definition->addTag('wd_cms.template', array_filter([
                        'key'  => $attribute->code,
                        'type' => $attribute->type,
                    ]));
                }
            );

            $container->registerAttributeForAutoconfiguration(AsCmsConfiguration::class,
                static function (ChildDefinition $definition, AsCmsConfiguration $attribute) {
                    $definition->addTag('wd_cms.configuration');
                }
            );
        }

        $container->getDefinition(BlockRegistry::class)->setArguments([
            new ServiceLocatorArgument(new TaggedIteratorArgument('wd_cms.block', 'key', null, true))
        ]);

        $container->getDefinition(TemplateRegistry::class)->setArguments([
            new ServiceLocatorArgument(new TaggedIteratorArgument('wd_cms.template', null, null, true))
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

    public function getAlias(): string
    {
        return 'web_et_design_cms';
    }

    private function registerDoctrineMapping($config)
    {
        $collector = DoctrineCollector::getInstance();

        $collector->addInheritanceType(AbstractCmsRoute::class,
            ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
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
