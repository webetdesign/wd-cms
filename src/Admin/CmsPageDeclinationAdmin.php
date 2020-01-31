<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use App\Entity\Product\Brand;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Form\CmsContentsType;
use WebEtDesign\CmsBundle\Form\CmsRouteParamsType;
use WebEtDesign\CmsBundle\Utils\GlobalVarsAdminTrait;
use WebEtDesign\CmsBundle\Utils\SmoFacebookAdminTrait;
use WebEtDesign\CmsBundle\Utils\SmoTwitterAdminTrait;

final class CmsPageDeclinationAdmin extends AbstractAdmin
{
    use SmoTwitterAdminTrait;
    use SmoFacebookAdminTrait;
    use GlobalVarsAdminTrait;

    protected $em;
    protected $pageConfig;
    protected $globalVarsEnable;

    protected $parentAssociationMapping = 'page';
    protected $datagridValues           = [
        '_page'       => 1,
        '_sort_order' => 'ASC',
        '_sort_by'    => 'position',
    ];

    public function __construct(string $code, string $class, string $baseControllerName, EntityManager $em, $pageConfig, $globalVarsDefinition)
    {
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
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('id')
            ->add('title')
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $roleAdmin = $this->canManageContent();
        $this->setFormTheme(array_merge($this->getFormTheme(), [
            '@WebEtDesignCms/form/cms_global_vars_type.html.twig',
            '@WebEtDesignCms/form/cms_route_params.html.twig',
            '@WebEtDesignCms/form/cms_contents_type.html.twig',
        ]));

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

        //region SEO
        $formMapper->tab('SEO');// The tab call is optional
        $this->addGlobalVarsHelp($formMapper, $object->getPage(), $this->globalVarsEnable);
        $formMapper
            ->with('Général', ['class' => 'col-xs-12 col-md-4', 'box_class' => ''])
            ->add('seo_title')
            ->add('seo_description')
            ->add('seo_keywords')
            ->end();
        $this->addFormFieldSmoFacebook($formMapper);
        $this->addFormFieldSmoTwitter($formMapper);
        $formMapper->end();
        //endregion
        //endregion

        //region Contenus
        $formMapper->tab('Contenus');
        $formMapper
            ->with('', ['box_class' => 'header_none', 'class' => $this->globalVarsEnable ? 'col-xs-9' : 'col-xs-12'])
            ->add('contents', CmsContentsType::class, [
                'label'        => false,
                'by_reference' => false,
                'role_admin'   => $roleAdmin,
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


    protected function canManageContent()
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
