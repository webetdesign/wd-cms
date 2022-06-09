<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\ItemInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;


final class CmsSiteAdmin extends AbstractAdmin
{
    protected ?bool $isMultilingual;
    protected ?bool $isMultisite;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $code,
        string $class,
        protected RouterInterface $router,
        private EntityManagerInterface $em,
        string $baseControllerName,
        $cmsConfig
    ) {
        $this->isMultisite    = $cmsConfig['multisite'];
        $this->isMultilingual = $cmsConfig['multilingual'];

        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
        $collection->remove('show');
    }


    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host');
    }

    protected function configureTabMenu(
        ItemInterface $menu,
        string $action,
        ?AdminInterface $childAdmin = null
    ): void {
        $admin = $this->isChild() ? $this->getParent() : $this;

        switch (true) {
            case $childAdmin instanceof CmsPageAdmin:
                $routeName = 'cms.admin.cms_page.tree';
                break;
            case $childAdmin instanceof CmsSharedBlockAdmin:
                $routeName = 'cms.admin.cms_shared_block.list';
                break;
            case $childAdmin instanceof CmsMenuAdmin:
                $routeName = 'cms.admin.cms_menu.tree';
                break;
            default:
                return;
        }


        if ($action == 'list' || $action == 'tree') {
            $groups = [];

            foreach ($this->em->getRepository(CmsSite::class)->findAll() as $site) {
                $key = !empty($site->getTemplateFilter()) ? $site->getTemplateFilter() : 'standalone';
                if (!isset($groups)) {
                    $groups[$key] = [];
                }
                $groups[$key][] = $site;
            }


            $activeId = $this->getRequest()->attributes->get('id');
            if (sizeof($groups) > 1) {
                foreach ($groups as $k => $sites) {
                    if (sizeof($sites) === 1 || $k === 'standalone') {
                        foreach ($sites as $site) {
                            $active = $site->getId() == $activeId;

                            $menu->addChild(
                                $site->__toString(),
                                [
                                    'uri'        => $admin->generateUrl($routeName,
                                        ['id' => $site->getId()]),
                                    'attributes' => ['class' => $active ? 'active' : ""]
                                ]
                            );
                        }

                    } else {

                        $child   = $menu->addChild($sites[0]->getLabel());
                        $classes = 'dropdown ';

                        foreach ($sites as $site) {
                            $active = $site->getId() == $activeId;

                            if ($active) {
                                $classes .= 'active';
                            }

                            $child->addChild(
                                $site->__toString(),
                                [
                                    'uri'        => $admin->generateUrl($routeName,
                                        ['id' => $site->getId()]),
                                    'attributes' => ['class' => $active ? 'active' : ""]
                                ]
                            );
                        }
                        $child->setAttribute('dropdown', true);
                        $child->setAttribute('class', $classes);
                    }
                }
            }
        }

        if ($childAdmin instanceof CmsPageAdmin && $action == 'edit') {
            $route = $this->router->generate('admin_webetdesign_cms_cmssite_cmspage_cmspagedeclination_list', [
                'id' => $this->getSubject()->getId(),
                'childId' => 9
            ]);
            $menu->addChild('Déclinaison',[
                'uri' => $route,
            ]);
        }
    }


    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        $listMapper
            ->add('id')
            ->add('label')
            ->add('host', null, [
                'template'            => '@WebEtDesignCms/admin/site/list__host_field.html.twig',
                'MULTISITE_LOCALHOST' => isset($_ENV['MULTISITE_LOCALHOST'])
                    && filter_var($_ENV['MULTISITE_LOCALHOST'], FILTER_VALIDATE_BOOLEAN)
            ])
            ->add('visible')
            ->add('default');
        if ($this->isMultilingual) {
            $listMapper
                ->add('locale')
                ->add('hostMultilingual');
        }
        $listMapper
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit'          => [],
                    'delete'        => [],
                    'pages'         => [
                        'template' => '@WebEtDesignCms/admin/site/list__action_pages.html.twig'
                    ],
                    'shared_blocks' => [
                        'template' => '@WebEtDesignCms/admin/site/list__action_shared_blocks.html.twig'
                    ],
                    'menus'         => [
                        'template' => '@WebEtDesignCms/admin/site/list__action_menus.html.twig'
                    ],
                ],
            ]);

    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('label')
            ->add('default', null, [
                'help' => "Site associé par défaut lorsque l'on crée une page"
            ]);
        if ($this->isMultisite) {
            $MULTISITE_LOCALHOST = isset($_ENV['MULTISITE_LOCALHOST'])
                && filter_var($_ENV['MULTISITE_LOCALHOST'], FILTER_VALIDATE_BOOLEAN);

            if ($MULTISITE_LOCALHOST) {
                $formMapper->add('localhost');
            } else {
                $formMapper->add('host');
            }

            $formMapper->add('templateFilter', null, [
                'help' => 'Technique'
            ]);
        }
        if ($this->isMultilingual) {
            $formMapper
                ->add('visible')
                ->add('locale')
                ->add('hostMultilingual', null, [
                    'help' => "Dans un contexte multilingue, cocher cette case permet de gérer la langue avec l’extension du domaine sans préfixé la route <br>
                        sans prefix : monsite.fr <br> avec prefix : monsite.com/fr"
                ])
                ->add('flagIcon', null, [
                    'help' => "<a href='https://www.countryflags.io' target='_blank'>Code du drapeau</a> ex: fr => <img src='https://www.countryflags.io/fr/flat/32.png' alt='fr'>"
                ]);
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host');
    }
}
