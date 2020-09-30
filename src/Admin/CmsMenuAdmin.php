<?php

namespace WebEtDesign\CmsBundle\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Services\TemplateProvider;
use Knp\Menu\ItemInterface as MenuItemInterface;

final class CmsMenuAdmin extends AbstractAdmin
{
    protected $pageProvider;
    private   $em;

    public function __construct(string $code, string $class, string $baseControllerName, EntityManager $em, TemplateProvider $pageProvider)
    {
        $this->em           = $em;
        $this->pageProvider = $pageProvider;
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * @inheritDoc
     */
    public function configureActionButtons($action, $object = null)
    {
        $list = parent::configureActionButtons($action, $object);

        if (\in_array($action, ['tree'], true)
            && $this->getChild('cms.admin.cms_menu_item')->hasRoute('create')
        ) {
            $list['addItem'] = [
                'template' => '@WebEtDesignCms/admin/menu/actionButtons/button_create_item.html.twig',
            ];
        }

        if (\in_array($action, ['tree'], true)
            && $this->getChild('cms.admin.cms_menu_item')->hasRoute('create')
        ) {
            $list['editMenu'] = [
                'template' => '@WebEtDesignCms/admin/menu/actionButtons/button_edit_menu.html.twig',
            ];
        }

        if (\in_array($action, ['tree'], true)
            && $this->hasRoute('generateFromPage') && $this->canManageContent()
        ) {
            $list['generateFromPage'] = [
                'template' => "@WebEtDesignCms/admin/menu/actionButtons/button_create_form_arbo.html.twig",
            ];
        }


        return $list;

    }


    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move/{id}');

        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('tree', 'tree/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('create', 'create/{id}', ['id' => null], ['id' => '\d*']);
        $collection->add('generateFromPage', 'generateFromPage/{id}', ['id' => null], ['id' => '\d*']);

    }

    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        $admin   = $this->isChild() ? $this->getParent() : $this;
        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && in_array($action, ['list', 'tree'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->request->attributes->get('id');
                    $menu->addChild(
                        $site->__toString(),
                        ['uri' => $admin->generateUrl('tree', ['id' => $site->getId()]), 'attributes' => ['class' => $active ? 'active' : ""]]
                    );
                }
            }
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('label')
            ->add('site');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('id')
            ->add('name')
            ->add('lft')
            ->add('lvl')
            ->add('rgt')
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper->getFormBuilder()->setAction($this->generateUrl('create', ['id' => $this->request->attributes->get('id')]));

        $formMapper
            ->with('Configuration')
            ->add('label', null, [
                'label' => 'Nom',
            ])
            ->add('code');

        // end configuration
        $formMapper->end();

    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name');
    }

    protected function canManageContent()
    {
        /** @var User $user */
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }

    public function createQuery($context = 'list')
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

        /** @var QueryBuilder $query */
        $query = parent::createQuery($context);
        return $query;
    }

}
