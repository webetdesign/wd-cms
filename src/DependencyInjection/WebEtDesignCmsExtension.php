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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use WebEtDesign\CmsBundle\Entity\AbstractCmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentSlider;
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
        $container->setParameter('wd_cms.shared_block', $config['sharedBlock']);
        $container->setParameter('wd_cms.custom_contents', $config['customContents']);
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
        $container->setParameter('wd_cms.admin.config.class.content_slider', $config['admin']['configuration']['class']['content_slider']);
        $container->setParameter('wd_cms.admin.config.class.menu', $config['admin']['configuration']['class']['menu']);
        $container->setParameter('wd_cms.admin.config.class.page', $config['admin']['configuration']['class']['page']);
        $container->setParameter('wd_cms.admin.config.class.route', $config['admin']['configuration']['class']['route']);
        $container->setParameter('wd_cms.admin.config.class.site', $config['admin']['configuration']['class']['site']);

        $container->setParameter('wd_cms.admin.config.controller.content', $config['admin']['configuration']['controller']['content']);
        $container->setParameter('wd_cms.admin.config.controller.content_slider', $config['admin']['configuration']['controller']['content_slider']);
        $container->setParameter('wd_cms.admin.config.controller.menu', $config['admin']['configuration']['controller']['menu']);
        $container->setParameter('wd_cms.admin.config.controller.page', $config['admin']['configuration']['controller']['page']);
        $container->setParameter('wd_cms.admin.config.controller.route', $config['admin']['configuration']['controller']['route']);
        $container->setParameter('wd_cms.admin.config.controller.site', $config['admin']['configuration']['controller']['site']);

        $container->setParameter('wd_cms.admin.config.entity.content', $config['admin']['configuration']['entity']['content']);
        $container->setParameter('wd_cms.admin.config.entity.content_slider', $config['admin']['configuration']['entity']['content_slider']);
        $container->setParameter('wd_cms.admin.config.entity.menu', $config['admin']['configuration']['entity']['menu']);
        $container->setParameter('wd_cms.admin.config.entity.page', $config['admin']['configuration']['entity']['page']);
        $container->setParameter('wd_cms.admin.config.entity.route', $config['admin']['configuration']['entity']['route']);
        $container->setParameter('wd_cms.admin.config.entity.site', $config['admin']['configuration']['entity']['site']);
    }

    public function getAlias()
    {
        return 'web_et_design_cms';
    }

    private function registerDoctrineMapping($config)
    {
        $collector = DoctrineCollector::getInstance();

            $collector->addInheritanceType(AbstractCmsRoute::class, ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
            $collector->addDiscriminator(AbstractCmsRoute::class, 'base', CmsRoute::class);
            $collector->addDiscriminatorColumn(AbstractCmsRoute::class, [
                'name' => 'discr',
                'type' => 'string'
            ]);
        if ($config['admin']['configuration']['entity']['route'] !== CmsRoute::class) {
            $collector->addDiscriminator(AbstractCmsRoute::class, 'override', $config['admin']['configuration']['entity']['route']);
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

        $collector->addAssociation(CmsContentSlider::class, 'mapManyToOne', [
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
