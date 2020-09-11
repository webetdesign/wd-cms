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
use WebEtDesign\CmsBundle\Entity\CmsContentHasSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsContentSlider;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsSite;

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
        $loader->load('admin.yaml');
        $loader->load('command.yaml');
        $loader->load('customContent.yaml');
        $loader->load('listener.yaml');
        $loader->load('menu.yaml');
        $loader->load('provider.yaml');
        $loader->load('form.yaml');

        $this->registerDoctrineMapping($config);

        // TODO : work for autowired configuration
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
        $container->setParameter('wd_cms.admin.content.media', $config['class']['media']);
    }

    public function configureAdmin($config, ContainerBuilder $container)
    {
        $container->setParameter('wd_cms.admin.config.class.content',
            $config['admin']['configuration']['class']['content']);
        $container->setParameter('wd_cms.admin.config.class.content_slider',
            $config['admin']['configuration']['class']['content_slider']);
        $container->setParameter('wd_cms.admin.config.class.menu',
            $config['admin']['configuration']['class']['menu']);
        $container->setParameter('wd_cms.admin.config.class.page',
            $config['admin']['configuration']['class']['page']);
        $container->setParameter('wd_cms.admin.config.class.route',
            $config['admin']['configuration']['class']['route']);
        $container->setParameter('wd_cms.admin.config.class.site',
            $config['admin']['configuration']['class']['site']);

        $container->setParameter('wd_cms.admin.config.controller.content',
            $config['admin']['configuration']['controller']['content']);
        $container->setParameter('wd_cms.admin.config.controller.content_slider',
            $config['admin']['configuration']['controller']['content_slider']);
        $container->setParameter('wd_cms.admin.config.controller.menu',
            $config['admin']['configuration']['controller']['menu']);
        $container->setParameter('wd_cms.admin.config.controller.page',
            $config['admin']['configuration']['controller']['page']);
        $container->setParameter('wd_cms.admin.config.controller.route',
            $config['admin']['configuration']['controller']['route']);
        $container->setParameter('wd_cms.admin.config.controller.site',
            $config['admin']['configuration']['controller']['site']);

        $container->setParameter('wd_cms.admin.config.entity.content',
            $config['admin']['configuration']['entity']['content']);
        $container->setParameter('wd_cms.admin.config.entity.content_slider',
            $config['admin']['configuration']['entity']['content_slider']);
        $container->setParameter('wd_cms.admin.config.entity.menu',
            $config['admin']['configuration']['entity']['menu']);
        $container->setParameter('wd_cms.admin.config.entity.page',
            $config['admin']['configuration']['entity']['page']);
        $container->setParameter('wd_cms.admin.config.entity.route',
            $config['admin']['configuration']['entity']['route']);
        $container->setParameter('wd_cms.admin.config.entity.site',
            $config['admin']['configuration']['entity']['site']);
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
        $collector->addDiscriminatorColumn(AbstractCmsRoute::class, [
            'name' => 'discr',
            'type' => 'string'
        ]);
        if ($config['admin']['configuration']['entity']['route'] !== CmsRoute::class) {
            $collector->addDiscriminator(AbstractCmsRoute::class, 'override',
                $config['admin']['configuration']['entity']['route']);
        }

        $this->addCmsPageMapping($collector, $config);
        $this->addCmsPageDeclinationMapping($collector, $config);
        $this->addCmsSiteMapping($collector, $config);
        $this->addCmsContentHasSharedBlockMapping($collector, $config);
        $this->addCmsContentSliderMapping($collector, $config);
        $this->addCmsSharedBlockMapping($collector, $config);
        $this->addCmsContentMapping($collector, $config);
        $this->addAbstractCmsRouteMapping($collector, $config);
    }

    protected function addCmsPageMapping(DoctrineCollector $collector, $config)
    {
        //        $collector->addAssociation(CmsPage::class, 'mapOneToMany', [
        //            'fieldName'     => 'contents',
        //            'targetEntity'  => $config['admin']['configuration']['entity']['content'],
        //            'cascade'       => [
        //                "remove",
        //                "persist"
        //            ],
        //            'mappedBy'      => 'page',
        //            'inversedBy'    => null,
        //            'orphanRemoval' => false,
        //            'orderBy'       => [
        //                "position" => "ASC"
        //            ]
        //        ]);

        $collector->addAssociation(CmsPage::class, 'mapOneToMany', [
            'fieldName'     => 'declinations',
            'targetEntity'  => CmsPageDeclination::class,
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'page',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsPage::class, 'mapManyToMany', [
            'fieldName'    => 'crossSitePages',
            'targetEntity' => $config['admin']['configuration']['entity']['page'],
            'cascade'      => [],
            'joinTable'    => [
                'name'               => 'cms__page_has_page',
                'joinColumns'        => [
                    'page_id' => [
                        'name'                 => 'page_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ]
                ],
                'inverseJoinColumns' => [
                    'associated_page_id' => [
                        'name'                 => 'associated_page_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ],
                ]
            ]
        ]);

        $collector->addAssociation(CmsPage::class, 'mapOneToOne', [
            'fieldName'     => 'route',
            'targetEntity'  => $config['admin']['configuration']['entity']['route'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'joinColumns'   => [
                [
                    'name'                 => 'route_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ],
            ],
            'mappedBy'      => null,
            'inversedBy'    => 'page',
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsPage::class, 'mapManyToOne', [
            'fieldName'     => 'site',
            'targetEntity'  => $config['admin']['configuration']['entity']['site'],
            'cascade'       => [],
            'mappedBy'      => null,
            'joinColumns'   => [
                [
                    'name'                 => 'site_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ],
            ],
            'inversedBy'    => 'pages',
            'orphanRemoval' => false,
        ]);
    }

    protected function addCmsPageDeclinationMapping(DoctrineCollector $collector, $config)
    {
        $collector->addAssociation(CmsPageDeclination::class, 'mapManyToOne', [
            'fieldName'     => 'page',
            'targetEntity'  => $config['admin']['configuration']['entity']['page'],
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => 'declinations',
            'joinColumns'   => [
                [
                    'name'                 => 'page_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ],
            ],
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsPageDeclination::class, 'mapOneToMany', [
            'fieldName'     => 'contents',
            'targetEntity'  => $config['admin']['configuration']['entity']['content'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'declination',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);
    }

    protected function addCmsSiteMapping(DoctrineCollector $collector, $config)
    {
        $collector->addAssociation(CmsSite::class, 'mapOneToMany', [
            'fieldName'     => 'pages',
            'targetEntity'  => $config['admin']['configuration']['entity']['page'],
            'cascade'       => [
                'persist',
                'remove'
            ],
            'mappedBy'      => 'site',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsSite::class, 'mapOneToMany', [
            'fieldName'     => 'sharedBlocks',
            'targetEntity'  => $config['admin']['configuration']['entity']['shared_block'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'site',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);
    }

    protected function addCmsContentHasSharedBlockMapping(DoctrineCollector $collector, $config)
    {

    }

    protected function addCmsContentSliderMapping(DoctrineCollector $collector, $config)
    {
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

        $collector->addAssociation(CmsContentSlider::class, 'mapManyToOne', [
            'fieldName'     => 'content',
            'targetEntity'  => $config['admin']['configuration']['entity']['content'],
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => "sliders",
            'joinColumns'   => [
                [
                    'name'                 => 'content_id',
                    'referencedColumnName' => 'id',
                ],
            ],
            'orphanRemoval' => false,
        ]);
    }

    protected function addCmsSharedBlockMapping(DoctrineCollector $collector, $config)
    {
        $collector->addAssociation(CmsSharedBlock::class, 'mapOneToMany', [
            'fieldName'     => 'contents',
            'targetEntity'  => $config['admin']['configuration']['entity']['content'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'sharedBlockParent',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsSharedBlock::class, 'mapOneToMany', [
            'fieldName'     => 'contentList',
            'targetEntity'  => $config['admin']['configuration']['entity']['cms_content_has_shared_block'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'sharedBlock',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsSharedBlock::class, 'mapManyToOne', [
            'fieldName'     => 'site',
            'targetEntity'  => $config['admin']['configuration']['entity']['site'],
            'cascade'       => [
            ],
            'inversedBy'    => 'sharedBlocks',
            'mappedBy'      => null,
            'joinColumns'   => [
                [
                    'name'                 => 'site_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'SET NULL'
                ],
            ],
            'orphanRemoval' => false,
        ]);
    }

    protected function addCmsContentMapping(DoctrineCollector $collector, $config)
    {
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

        $collector->addAssociation(CmsContent::class, 'mapManyToOne', [
            'fieldName'     => 'page',
            'targetEntity'  => $config['admin']['configuration']['entity']['page'],
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => 'contents',
            'joinColumns'   => [
                [
                    'name'                 => 'page_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ],
            ],
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsContent::class, 'mapManyToOne', [
            'fieldName'     => 'declination',
            'targetEntity'  => CmsPageDeclination::class,
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => 'contents',
            'joinColumns'   => [
                [
                    'name'                 => 'declination_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ],
            ],
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsContent::class, 'mapManyToOne', [
            'fieldName'     => 'sharedBlockParent',
            'targetEntity'  => $config['admin']['configuration']['entity']['shared_block'],
            'cascade'       => [
            ],
            'mappedBy'      => null,
            'inversedBy'    => 'contents',
            'joinColumns'   => [
                [
                    'name'                 => 'shared_block_parent_id',
                    'referencedColumnName' => 'id',
                ],
            ],
            'orphanRemoval' => false,
        ]);

        $collector->addAssociation(CmsContent::class, 'mapOneToMany', [
            'fieldName'     => 'sliders',
            'targetEntity'  => $config['admin']['configuration']['entity']['content_slider'],
            'cascade'       => [
                "remove",
                "persist"
            ],
            'mappedBy'      => 'content',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);
    }

    protected function addAbstractCmsRouteMapping(DoctrineCollector $collector, $config)
    {
        $collector->addAssociation(AbstractCmsRoute::class, 'mapOneToOne', [
            'fieldName'     => 'page',
            'targetEntity'  => $config['admin']['configuration']['entity']['page'],
            'cascade'       => [
                "remove"
            ],
            'joinColumns'   => [
            ],
            'mappedBy'      => 'route',
            'inversedBy'    => null,
            'orphanRemoval' => false,
        ]);
    }
}
