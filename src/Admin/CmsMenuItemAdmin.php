<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Form\CmsRouteParamsType;
use WebEtDesign\CmsBundle\Form\MoveForm;
use WebEtDesign\CmsBundle\Form\Type\MenuIconType;

final class CmsMenuItemAdmin extends AbstractAdmin
{
    private ?array $configMenu;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PageFactory $pageFactory,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->configMenu = $this->parameterBag->get('wd_cms.menu');
        parent::__construct();
    }


    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('move', 'move/{itemId}');
        $collection->remove('show');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('linkType')
            ->add('linkValue')
            ->add('isVisible')
            ->add('lvl')
            ->add('lft')
            ->add('rgt')
            ->add('classes')
            ->add('connected')
            ->add('role')
            ->add('params');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('name')
            ->add('linkType')
            ->add('linkValue')
            ->add('isVisible')
            ->add('lvl')
            ->add('lft')
            ->add('rgt')
            ->add('classes')
            ->add('connected')
            ->add('role')
            ->add('params')
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
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig',
            '@WebEtDesignCms/form/cms_route_params.html.twig',
            '@WebEtDesignCms/form/cms_menu_icon_widget.html.twig',
        ]));


        /** @var CmsMenuItem $object */
        $object = $this->getSubject();


        $formMapper
            ->tab('General')
            ->with('', ['box_class' => 'header_none'])
            ->add('name')
            ->add('information', TextType::class, [
                'label'    => 'Informations',
                'required' => false
            ])
            ->add('isVisible', null, ['label' => 'Actif']);

        if ($object->getId() === null) {
            $formMapper->add('linkType', ChoiceType::class, [
                'choices'  => CmsMenuLinkTypeEnum::getChoices(),
                'label'    => 'Type de lien',
                'required' => false,
            ]);

            $formMapper
                ->add('position', MoveForm::class, [
                    'data_class' => null,
                    'entity'     => CmsMenuItem::class,
                    'object'     => $object
                ]);
        }

        $formMapper
            ->end()
            ->end();

        if ($object && $object->getId() != null) {
            $formMapper
                ->tab('Lien')
                ->with('', ['box_class' => 'header_none'])
                ->add('linkType', ChoiceType::class, [
                    'choices'  => CmsMenuLinkTypeEnum::getChoices(),
                    'label'    => 'Type de lien',
                    'required' => false,
                ]);

            switch ($object->getLinkType()) {
                case CmsMenuLinkTypeEnum::CMS_PAGE:
                    $formMapper->add('page', null, [
                        'required'      => false,
                        'label'         => 'Page',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('p')
                                ->orderBy('p.lft', 'ASC');
                        },
                        'choice_label'  => function (CmsPage $page) {
                            return str_repeat('—', $page->getLvl()) . ' ' . $page->getTitle();
                        },
                        'group_by'      => function ($choice, $key, $value) {
                            return $choice->getSite()->__toString();
                        },
                    ]);

                    if ($object->getPage() != null) {
                        /** @var CmsRoute $route */
                        $route = $object->getPage()->getRoute();
                        if ($route && $route->isDynamic()) {
                            $this->getRouteParamsField($formMapper, $object, $route);
                        }
                    }
                    break;
                case CmsMenuLinkTypeEnum::PATH:
                    $formMapper
                        ->add('linkValue', null, [
                            'required' => false,
                            'label'    => 'Chemin',
                        ]);
                    break;
                case CmsMenuLinkTypeEnum::ROUTENAME:
                    $formMapper
                        ->add('linkValue', null, [
                            'required' => false,
                            'label'    => 'Nom technique de la route',
                        ]);
                    break;
                case CmsMenuLinkTypeEnum::URL:
                    $formMapper
                        ->add('linkValue', null, [
                            'required' => false,
                            'label'    => 'Valeur du lien',
                        ]);
                    break;
                case CmsMenuLinkTypeEnum::SERVICE:
                    $choices = [];
                    foreach ($this->configMenu as $code => $configMenu) {
                        $choices[$configMenu['label']] = $code;
                    }
                    $formMapper
                        ->add('linkValue', ChoiceType::class, [
                            'choices'  => $choices,
                            'required' => false,
                            'label'    => 'Service',
                        ]);
                    break;
            }

            $formMapper->add('anchor', TextType::class, [
                'label'    => 'Ancre',
                'required' => false,
            ]);

            $formMapper
                ->end()
                ->end();
            // fin tab lien
            $formMapper
                ->tab('Avancé')
                ->with('', ['box_class' => 'header_none']);

            $formMapper
                ->add('blank', null, ['label' => 'Nouvelle fenetre'])
                ->add('liClass', null, [
                    'label'    => 'item class (li)',
                    'required' => false,
                ])
                ->add('ulClass', null, [
                    'label'    => 'list class (ul)',
                    'required' => false,
                ])
                ->add('linkClass', null, [
                    'label'    => 'link class (a, span)',
                    'required' => false,
                ])
                ->add('iconClass', MenuIconType::class, [
                    'label'    => 'icon class',
                    'required' => false,
                ])
                ->add('connected', ChoiceType::class, [
                    'choices' => [
                        'Tout le temps'                                  => '',
                        "uniquement si l'utilisateur est connecté"       => 'ONLY_LOGIN',
                        "uniquement si l'utilisateur n'est pas connecté" => 'ONLY_LOGOUT'
                    ],
                    'label'   => 'Visible',
                    'help'    => "Permet de dynamiser le menu si l'utilisateur est connecté ou non"
                ])
                ->add('role');

            $formMapper
                ->end()
                ->end();
            // fin tab Avancé

        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('linkType')
            ->add('linkValue')
            ->add('isVisible')
            ->add('lvl')
            ->add('lft')
            ->add('rgt')
            ->add('classes')
            ->add('connected')
            ->add('role')
            ->add('params');
    }

    protected function getRouteParamsField(FormMapper $formMapper, $subject, $route)
    {

        try {
            $config = $this->pageFactory->get($subject->getPage()->getTemplate());
        } catch (Exception $e) {
            $config = null;
        }

        if ($config) {
            $formMapper->add('params', CmsRouteParamsType::class, [
                'config' => $config,
                'route'  => $route,
                'object' => $subject,
                'label'  => 'Parametre de l\'url de la page : ' . $route->getPath() . ', ( ' . $subject->getPath() . ' )'
            ]);
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
