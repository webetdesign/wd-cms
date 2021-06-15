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
use Sonata\Doctrine\Mapper\Builder\OptionsBuilder;
use Sonata\Doctrine\Mapper\DoctrineCollector;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use WebEtDesign\CmsBundle\Entity\AbstractCmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsRoute;

class WebEtDesignCmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, $configs);

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
        $container->setParameter('wd_cms.custom_contents_form_themes',
            $config['customContentsFormThemes']);

        $container->setParameter('wd_cms.vars', $config['cms']['vars']);

        $container->setParameter('wd_cms.menu', $config['menu']);
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
