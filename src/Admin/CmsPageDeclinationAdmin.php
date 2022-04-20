<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Form\CmsContentsType;
use WebEtDesign\CmsBundle\Form\CmsRouteParamsType;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Security\Voter\ManageContentVoter;
use WebEtDesign\CmsBundle\Utils\GlobalVarsAdminTrait;
use WebEtDesign\SeoBundle\Admin\SmoOpenGraphAdminTrait;
use WebEtDesign\SeoBundle\Admin\SmoTwitterAdminTrait;

final class CmsPageDeclinationAdmin extends AbstractAdmin
{
    use SmoOpenGraphAdminTrait;
    use SmoTwitterAdminTrait;
    use GlobalVarsAdminTrait;

    protected EntityManager $em;
    protected ?array        $pageConfig;
    protected ?bool         $globalVarsEnable;

    protected ?string $parentAssociationMapping = 'page';
    protected array   $datagridValues           = [
        '_page'       => 1,
        '_sort_order' => 'ASC',
        '_sort_by'    => 'position',
    ];

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        $pageConfig,
        $globalVarsDefinition,
        private BlockFormThemesManager $blockFormThemesManager
    ) {
        $this->em               = $em;
        $this->pageConfig       = $pageConfig;
        $this->globalVarsEnable = $globalVarsDefinition['enable'];

        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('title');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        $listMapper
            ->add('id')
            ->add('title')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $this->setFormTheme(array_merge($this->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_route_params.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
            '@WebEtDesignCms/customContent/sortable_collection_widget.html.twig',
            '@WebEtDesignCms/customContent/sortable_entity_widget.html.twig',
        ], $this->blockFormThemesManager->getThemes()));

        /** @var CmsPageDeclination $object */
        $object = $this->getSubject();
        if (!$object) { //For Batch action delete
            return;
        }
        $route  = $object->getPage()->getRoute();
        $config = $this->pageConfig[$object->getPage()->getTemplate()];

        //region Général
        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => '']);

        $formMapper
            ->add('title', null, ['label' => 'Title']);

        $formMapper->add('params', CmsRouteParamsType::class, [
            'config' => $config,
            'route'  => $route,
            'object' => $object,
            'label'  => 'Parametre de l\'url de la page : ' . $route->getPath() . ', ( ' . $object->getPath() . ' )'
        ]);

        $formMapper
            ->end()// End form group
            ->end()// End tab
        ;
        //endregion

        //region SEO
        $formMapper->tab('SEO');// The tab call is optional
        $this->addGlobalVarsHelp($formMapper, $object->getPage(), $this->globalVarsEnable);
        $formMapper
            ->with('Général', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('seo_title')
            ->add('seo_description')
            ->add('seo_keywords')
            ->end();
        $this->addFormFieldSmoOpenGraph($formMapper);
        $this->addFormFieldSmoTwitter($formMapper);
        $formMapper->end();
        //endregion

        //region Contenus
        $formMapper->tab('Contenus');
        $formMapper
            ->with('', ['box_class' => 'header_none', 'class' => $this->globalVarsEnable ? 'col-xs-9' : 'col-xs-12'])
            ->add('contents', CmsContentsType::class, [
                'label'        => false,
                'by_reference' => false,
                'role_admin'   => $this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT),
            ])
            ->end();
        $this->addGlobalVarsHelp($formMapper, $object->getPage(), $this->globalVarsEnable, true);
        $formMapper
            ->end();
        //endregion
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('title')
            ->add('seo_title')
            ->add('seo_description')
            ->add('seo_keywords')
            ->add('fb_title')
            ->add('fb_type')
            ->add('fb_url')
            ->add('fb_image')
            ->add('fb_description')
            ->add('fb_site_name')
            ->add('fb_admins')
            ->add('twitter_card')
            ->add('twitter_site')
            ->add('twitter_title')
            ->add('twitter_description')
            ->add('twitter_creator')
            ->add('twitter_image');
    }
}
