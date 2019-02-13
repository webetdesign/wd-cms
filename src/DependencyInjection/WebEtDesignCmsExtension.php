<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 22/01/2019
 * Time: 15:34
 */

namespace WebEtDesign\CmsBundle\DependencyInjection;


use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\EasyExtendsBundle\Mapper\DoctrineCollector;
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
        $this->configureAdmin($config, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $this->registerDoctrineMapping($config);

        // TODO : work for autowired configuration
        $container->setParameter('wd_cms.templates', $config['pages']);
    }

    /**
     * @param array $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function configureClass($config, ContainerBuilder $container)
    {
        // manager configuration
        $container->setParameter('wd_cms.admin.content.user', $config['class']['user']);
        $container->setParameter('wd_cms.admin.content.media', $config['class']['media']);
    }

    public function configureAdmin($config, ContainerBuilder $container)
    {
        $container->setParameter('wd_cms.admin.config.class.content', $config['admin']['configuration']['class']['content']);
        $container->setParameter('wd_cms.admin.config.class.menu', $config['admin']['configuration']['class']['menu']);
        $container->setParameter('wd_cms.admin.config.class.page', $config['admin']['configuration']['class']['page']);
        $container->setParameter('wd_cms.admin.config.class.route', $config['admin']['configuration']['class']['route']);

        $container->setParameter('wd_cms.admin.config.controller.content', $config['admin']['configuration']['controller']['content']);
        $container->setParameter('wd_cms.admin.config.controller.menu', $config['admin']['configuration']['controller']['menu']);
        $container->setParameter('wd_cms.admin.config.controller.page', $config['admin']['configuration']['controller']['page']);
        $container->setParameter('wd_cms.admin.config.controller.route', $config['admin']['configuration']['controller']['route']);

        $container->setParameter('wd_cms.admin.config.entity.content', $config['admin']['configuration']['entity']['content']);
        $container->setParameter('wd_cms.admin.config.entity.menu', $config['admin']['configuration']['entity']['menu']);
        $container->setParameter('wd_cms.admin.config.entity.page', $config['admin']['configuration']['entity']['page']);
        $container->setParameter('wd_cms.admin.config.entity.route', $config['admin']['configuration']['entity']['route']);
    }

    public function getAlias()
    {
        return 'web_et_design_cms';
    }

    private function registerDoctrineMapping($config)
    {
        $collector = DoctrineCollector::getInstance();

        if ($config['admin']['configuration']['entity']['route'] !== CmsRoute::class) {
            $collector->addInheritanceType(AbstractCmsRoute::class, ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
            $collector->addDiscriminator(AbstractCmsRoute::class, 'routeOverride', $config['admin']['configuration']['entity']['route']);
            $collector->addDiscriminator(AbstractCmsRoute::class, 'origin', CmsRoute::class);
            $collector->addDiscriminatorColumn(AbstractCmsRoute::class, [
                'name' => 'discr',
                'type' => 'string'
            ]);
        }

        $collector->addAssociation(CmsContent::class, 'mapManyToOne', [
            'fieldName'     => 'media',
            'targetEntity'  => $config['class']['media'],
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => null,
            'joinColumns'   => [
                [
                    'name'                 => 'media_id',
                    'referencedColumnName' => 'id',
                ],
            ],
            'orphanRemoval' => false,
        ]);

    }
}
