<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
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

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move');

        $collection->add('list', 'list/{id}', ['id' => null], ['id' => '\d*']);
    }

    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        $admin   = $this->isChild() ? $this->getParent() : $this;
        $subject = $this->isChild() ? $this->getParent()->getSubject() : $this->getSubject();

        $id = $this->getRequest()->get('id');

        if (!$childAdmin && in_array($action, ['list'])) {
            $sites = $this->em->getRepository(CmsSite::class)->findAll();
            if (sizeof($sites) > 1) {
                foreach ($sites as $site) {
                    $active = $site->getId() == $this->request->attributes->get('id');
                    $menu->addChild(
                        $site->getLabel(),
                        ['uri' => $admin->generateUrl('list', ['id' => $site->getId()]), 'attributes' => ['class' => $active ? 'active' : ""]]
                    );
                }
            }
        }
    }

    //    public function createQuery($context = 'list')
    //    {
    //        $proxyQuery = parent::createQuery('list');
    //        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.root', 'ASC');
    //        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.lft', 'ASC');
    //
    //        return $proxyQuery;
    //    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('lvl')
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
        /** @var CmsMenu $subject */
        $subject   = $formMapper->getAdmin()->getSubject();
        $roleAdmin = $this->canManageContent();

        $formMapper
            ->with('Configuration')
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('code', HiddenType::class);
        if ($subject && $subject->getMoveTarget() && $subject->getMoveTarget()->getLvl() == 0) {
            $formMapper->remove('code');
            $formMapper
                ->add('code', null, [
                    'label'    => 'Code',
                    'required' => true,
                ]);
        }
        if ($subject && $subject->getId() != null && $subject->getLvl() > 1) {
            $formMapper
                ->add('linkType', ChoiceType::class, [
                    'choices'  => CmsMenuLinkTypeEnum::getChoices(),
                    'label'    => 'Type de lien',
                    'required' => false,
                ])
                ->addHelp('page', 'help page')
                ->add('page', null, [
                    'sonata_help' => 'Si le type de lien utilisé est Page cms',
                    'required'    => false,
                    'label'       => 'Page cms',
                ]);

            if ($subject && $subject->getId() != null && $subject->getPage() != null) {
                /** @var CmsRoute $route */
                $route = $subject->getPage()->getRoute();
                if ($route->isDynamic()) {
                    $this->getRouteParamsField($formMapper, $subject, $route);
                }
            }

            $formMapper
                ->add('linkValue', null, [
                    'sonata_help' => 'Valeur pour les autres types de liens',
                    'required'    => false,
                    'label'       => 'Valeur du lien',
                ]);
        }

        // end configuration
        $formMapper->end();

        if ($subject && $subject->getId() != null) {
            $formMapper
                ->with('Configuration avancé')
                ->add('classes', null, [
                    'label'    => 'Classes',
                    'required' => false,
                ])
                ->add('connected', ChoiceType::class, [
                    'choices' => [
                        'Tout les temps'             => '',
                        'uniquement si connecté'     => 'ONLY_LOGIN',
                        'uniquement si non connecté' => 'ONLY_LOGOUT'
                    ],
                    'label'   => 'Visible',
                ])
                ->add('role')
                ->end();
        }

        if ($roleAdmin) {
            $formMapper
                ->with('Déplacer')
                ->add('moveMode', ChoiceType::class, [
                    'choices'  => [
                        'Déplacer comme premier enfant de'        => 'persistAsFirstChildOf',
                        'Déplacer en tant que dernier enfant de'  => 'persistAsLastChildOf',
                        'Déplacer en tant que prochain frère de'  => 'persistAsNextSiblingOf',
                        'Déplacer en tant que frère antérieur de' => 'persistAsPrevSiblingOf',
                    ],
                    'label'    => false,
                    'required' => false,
                ])
                ->add('moveTarget', EntityType::class, [
                    'class'         => CmsMenu::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('m')
                            ->addOrderBy('m.root', 'asc')
                            ->addOrderBy('m.lft', 'asc');
                    },
                    'choice_label'  => function ($menu) {
                        return str_repeat('--', $menu->getLvl()) . ' ' . $menu->getName();
                    },
                    'label'         => false,
                    'required'      => false,
                ])
                ->end();
        } else {
            $formMapper
                ->with('Configuration')
                ->add('moveMode', HiddenType::class)
                ->add('moveTarget', HiddenType::class)
                ->end();

            $formMapper->getFormBuilder()->get('moveTarget')->addModelTransformer(new CallbackTransformer(
                function ($value) {
                    return $value !== null ? $value->getId() : null;
                },
                function ($value) {
                    return $this->em->getRepository(CmsMenu::class)->find((int)$value);
                }
            ));
        }
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('lft')
            ->add('lvl')
            ->add('rgt');
    }

    protected function canManageContent()
    {
        /** @var User $user */
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }

    protected function getRouteParamsField(FormMapper $formMapper, $subject, $route)
    {
        $config = $this->pageProvider->getConfigurationFor($subject->getPage()->getTemplate());
        $keys   = [];
        foreach ($route->getParams() as $name) {
            $param  = $config['params'][$name] ?? null;
            $type   = !empty($param['entity']) ? EntityType::class : TextType::class;
            $opts   = !empty($param['entity']) ? [
                'class'        => $param['entity'],
                'choice_value' => function ($entity = null) use ($param) {
                    $getter = 'get' . ucfirst($param['property']);

                    return $entity ? $entity->$getter() : '';
                },
            ] : [];
            $keys[] = [$name, $type, $opts];
        }
        $formMapper->add('params', ImmutableArrayType::class, [
            'keys'  => $keys,
            'label' => false
        ]);
        $formMapper->getFormBuilder()->get('params')->addModelTransformer(new CallbackTransformer(
            function ($values) use ($config) {
                if ($values != null) {
                    $values = json_decode($values, true);
                    foreach ($values as $name => $value) {
                        $param = $config['params'][$name] ?? null;
                        if ($param) {
                            $object        = $this->em->getRepository($param['entity'])->findOneBy([$param['property'] => $value]);
                            $values[$name] = $object;
                        }
                    }
                }

                return $values;
            },
            function ($values) use ($config) {
                foreach ($values as $name => $value) {
                    $param = $config['params'][$name] ?? null;
                    if ($param) {
                        $getter        = 'get' . ucfirst($param['property']);
                        $values[$name] = $value->$getter();
                    }
                }

                return json_encode($values);
            }
        ));
    }
}
