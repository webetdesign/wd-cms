<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\BlockTemplateType;
use WebEtDesign\CmsBundle\Form\Content\AdminCmsBlockCollectionType;
use WebEtDesign\CmsBundle\Manager\BlockFormThemesManager;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Security\Voter\ManageContentVoter;
use function count;
use function in_array;

final class CmsSharedBlockAdmin extends AbstractAdmin
{
    protected ?bool $isMultisite;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TemplateRegistry $templateRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly BlockFormThemesManager $blockFormThemesManager,
    ) {
        $this->isMultisite = $this->parameterBag->get('wd_cms.cms')['multisite'];
        parent::__construct();
    }


    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('site', null, ['show_filter' => false]);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        if ($this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT)) {
            $listMapper
                ->add('id')
                ->add('code');
        }

        $listMapper
            ->add('label')
            ->add('active')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('export')
            ->remove('show')
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $admin     = $this;
        $roleAdmin = $this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT);
        $object    = $this->getSubject();

        $admin->setFormTheme(array_merge($admin->getFormTheme(), [
            '@WebEtDesignCms/admin/form/dynamic_block.html.twig',
            '@WebEtDesignCms/admin/form/admin_cms_vars_section.html.twig',
            '@WebEtDesignCms/admin/form/cms_block.html.twig',
        ], $this->blockFormThemesManager->getThemes()));

        if ($this->isCurrentRoute('create') && $this->getRequest()->get('id') !== null) {
            $site = $this->em->getRepository(CmsSite::class)->find($this->getRequest()->get('id'));
            $object->setSite($site);
        } else {
            $site = $object->getSite();
        }

        // tab Général
        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => 'header_none'])
            ->add('site', ModelHiddenType::class)
            ->add('code',
                $this->isCurrentRoute('edit') && $roleAdmin ? TextType::class : HiddenType::class,
                [])
            ->add('label')
            ->add('template', BlockTemplateType::class, [
                'label'      => 'Modèle de block partagé',
                'collection' => $site?->getTemplateFilter()
            ]);

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->add('active');
        }

        $formMapper
            ->end()// End form group
            ->end()// End tab général
        ;

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            //region Contenus
            $formMapper->tab('Contenus');
            $formMapper
                ->with('', ['box_class' => 'header_none', 'class' => 'col-xs-12'])
                ->add('contents', AdminCmsBlockCollectionType::class, [
                    'templateFactory' => $this->templateRegistry,
                ])
                ->end();
            $formMapper->end();
            //endregion
        }
    }

    /**
     * @inheritDoc
     */
    public function configureActionButtons(
        array $list,
        string $action,
        ?object $object = null
    ): array {
        $list = [];

        if (in_array($action, ['tree', 'list'], true)
            && $this->hasAccess('create')
            && $this->hasRoute('create')
        ) {
            $list['create'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_create'),
            ];
        }

        if (in_array($action, ['show', 'delete', 'acl', 'history'], true)
            && $this->hasAccess('edit', $object)
            && $this->hasRoute('edit')
        ) {
            $list['edit'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_edit'),
            ];
        }

        if (in_array($action, ['show', 'edit', 'acl'], true)
            && $this->hasAccess('history', $object)
            && $this->hasRoute('history')
        ) {
            $list['history'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_history'),
            ];
        }

        if (in_array($action, ['edit', 'history'], true)
            && $this->isAclEnabled()
            && $this->hasAccess('acl', $object)
            && $this->hasRoute('acl')
        ) {
            $list['acl'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_acl'),
            ];
        }

        if (in_array($action, ['edit', 'history', 'acl'], true)
            && $this->hasAccess('show', $object)
            && count($this->getShow()) > 0
            && $this->hasRoute('show')
        ) {
            $list['show'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_show'),
            ];
        }

        if (in_array($action, ['show', 'edit', 'delete', 'acl', 'batch'], true)
            && $this->hasAccess('list')
            && $this->hasRoute('list')
        ) {
            $list['list'] = [
                'template' => $this->getTemplateRegistry()->getTemplate('button_list'),
            ];
        }

        return $list;
    }
}
