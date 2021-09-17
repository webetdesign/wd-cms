<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Security\Voter\ManageContentVoter;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use Knp\Menu\ItemInterface as MenuItemInterface;
use function in_array;

final class CmsMenuAdmin extends AbstractAdmin
{
    protected TemplateProvider $pageProvider;
    private   EntityManager $em;

    /**
     * CmsMenuAdmin constructor.
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param EntityManager $em
     * @param TemplateProvider $pageProvider
     */
    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        TemplateProvider $pageProvider
    ) {
        $this->em           = $em;
        $this->pageProvider = $pageProvider;
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * @inheritDoc
     */
    public function configureActionButtons(array $list, string $action, ?object $object = null): array
    {

        if (in_array($action, ['tree'], true)
            && $this->getChild('cms.admin.cms_menu_item')->hasRoute('create')
        ) {
            $list['addItem'] = [
                'template' => '@WebEtDesignCms/admin/menu/actionButtons/button_create_item.html.twig',
            ];
        }

        if (in_array($action, ['tree'], true)
            && $this->getChild('cms.admin.cms_menu_item')->hasRoute('create')
        ) {
            $list['editMenu'] = [
                'template' => '@WebEtDesignCms/admin/menu/actionButtons/button_edit_menu.html.twig',
            ];
        }

        if (in_array($action, ['tree'], true)
            && $this->hasRoute('generateFromPage') && $this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT)
        ) {
            $list['generateFromPage'] = [
                'template' => "@WebEtDesignCms/admin/menu/actionButtons/button_create_form_arbo.html.twig",
            ];
        }


        return $list;

    }


    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move/{id}');

        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('tree', 'tree/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('create', 'create/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('generateFromPage', 'generateFromPage/{id}', ['id' => null],
            ['id' => '\d*']);

    }

    protected function configureSideMenu(
        MenuItemInterface $menu,
        $action,
        AdminInterface $childAdmin = null
    ) {
        $admin   = $this->isChild() ? $this->getParent() : $this;

        if (!$childAdmin && in_array($action, ['list', 'tree'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->getRequest()->attributes->get('id');
                    $menu->addChild(
                        $site->__toString(),
                        [
                            'uri'        => $admin->generateUrl('tree', ['id' => $site->getId()]),
                            'attributes' => ['class' => $active ? 'active' : ""]
                        ]
                    );
                }
            }
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('label')
            ->add('site');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        $listMapper
            ->add('id')
            ->add('name')
            ->add('lft')
            ->add('lvl')
            ->add('rgt')
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

        $formMapper->getFormBuilder()->setAction($this->generateUrl('create',
            ['id' => $this->getRequest()->attributes->get('id')]));

        $formMapper
            ->with('Configuration')
            ->add('label', null, [
                'label' => 'Nom',
            ])
            ->add('code');

        // end configuration
        $formMapper->end();

    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('name');
    }


    public function configureQuery(ProxyQueryInterface $proxyQuery): ProxyQueryInterface
    {
        //        $qb = $this->em->createQueryBuilder();
        //
        //        $qb
        //            ->select(['m', 's'])
        //            ->from('CmsMenuItem', 'm')
        //            ->leftJoin('m.site', 's')
        //            ->andWhere(
        //                $qb->expr()->eq('m.site', $this->getRequest()->get('id'))
        //            )
        //            ->getQuery()->getResult();
        //
        //        $qb = $this->em->createQueryBuilder();
        //
        //        $qb
        //            ->select(['PARTIAL m.{id}', 'p', 'r'])
        //            ->from('CmsMenuItem', 'm')
        //            ->leftJoin('m.page', 'p')
        //            ->leftJoin('p.route', 'r')
        //            ->andWhere(
        //                $qb->expr()->eq('m.site', $this->getRequest()->get('id'))
        //            )
        //            ->getQuery()->getResult();
        //
        //        $qb = $this->em->createQueryBuilder();
        //
        //        $qb
        //            ->select(['PARTIAL m.{id}', 'c'])
        //            ->from('CmsMenuItem', 'm')
        //            ->leftJoin('m.children', 'c')
        //            ->andWhere(
        //                $qb->expr()->eq('m.site', $this->getRequest()->get('id'))
        //            )
        //            ->getQuery()->getResult();

        return $proxyQuery;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
