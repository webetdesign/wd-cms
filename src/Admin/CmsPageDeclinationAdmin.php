<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Form\CmsContentsType;
use WebEtDesign\CmsBundle\Form\CmsRouteParamsType;
use WebEtDesign\CmsBundle\Form\Content\AdminCmsBlockCollectionType;
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

    protected ?bool         $globalVarsEnable;

    protected ?string $parentAssociationMapping = 'page';
    protected array   $datagridValues           = [
        '_page'       => 1,
        '_sort_order' => 'ASC',
        '_sort_by'    => 'position',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PageFactory $pageFactory,
        private readonly ParameterBagInterface $parameterBag)
    {
        $this->globalVarsEnable = false; // TODO $globalVarsDefinition['enable'];
        parent::__construct();
    }


    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
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
            "@WebEtDesignCms/admin/form/cms_block.html.twig",
        ], $this->blockFormThemesManager->getThemes()));

        /** @var CmsPageDeclination $object */
        $object = $this->getSubject();
        if (!$object) { //For Batch action delete
            return;
        }
        $route = $object->getPage()->getRoute();

        $pageConfig = $this->pageFactory->get($object->getPage()->getTemplate());

        //region Général
        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => 'header_none']);

        $formMapper
            ->add('title', null, ['label' => 'Title']);

        $formMapper->add('params', CmsRouteParamsType::class, [
            'config' => $pageConfig,
            'route'  => $route,
            'object' => $object,
            'label'  => 'Parametre de l\'url de la page : ' . $route->getPath() . ', ( ' . $object->getPath() . ' )'
        ]);

        $formMapper
            ->end()// End form group
            ->end()// End tab
        ;
        // endregion

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
            ->with('', [
                'box_class' => 'header_none',
                'class'     => $this->globalVarsEnable ? 'col-xs-9' : 'col-xs-12'
            ])
            ->add('contents', AdminCmsBlockCollectionType::class, [
                'templateFactory' => $this->pageFactory,
            ])
            ->end();
        $this->addGlobalVarsHelp($formMapper, $object->getPage(), $this->globalVarsEnable, true);
        $formMapper
            ->end();
        //endregion
    }


}
