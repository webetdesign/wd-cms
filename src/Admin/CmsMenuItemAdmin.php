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
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Form\CmsRouteParamsType;
use WebEtDesign\CmsBundle\Form\MoveForm;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

final class CmsMenuItemAdmin extends AbstractAdmin
{
    /**
     * @var TemplateProvider
     */
    protected $pageProvider;
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    private   $configMenu;

    /**
     * @inheritDoc
     */
    public function __construct(
        $code,
        $class,
        $baseControllerName,
        EntityManagerInterface $em,
        TemplateProvider $pageProvider,
        $configMenu
    ) {
        $this->em           = $em;
        $this->pageProvider = $pageProvider;

        parent::__construct($code, $class, $baseControllerName);
        $this->configMenu = $configMenu;
    }


    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('move', 'move/{itemId}');
        //        $collection->remove('list');
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
        $this->setFormTheme(array_merge($this->getFormTheme(), [
            '@WebEtDesignCms/admin/nestedTreeMoveAction/wd_cms_move_form.html.twig',
            '@WebEtDesignCms/form/cms_route_params.html.twig',
        ]));


        /** @var CmsMenuItem $object */
        $object = $this->getSubject();


        $formMapper
            ->tab('General')
            ->with('', ['box_class' => ''])
            ->add('name')
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
                ->with('', ['box_class' => ''])
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

            $formMapper
                ->end()
                ->end();
            // fin tab lien
            $formMapper
                ->tab('Avancé')
                ->with('', ['box_class' => '']);

            $formMapper
                ->add('blank', null, ['label' => 'Nouvelle fenetre'])
                ->add('classes', null, [
                    'label'    => 'Classes',
                    'required' => false,
                ])
                ->add('connected', ChoiceType::class, [
                    'choices' => [
                        'Tout le temps'                                  => '',
                        "uniquement si l'utilisateur est connecté"       => 'ONLY_LOGIN',
                        "uniquement si l'utilisateur n'est pas connecté" => 'ONLY_LOGOUT'
                    ],
                    'label'   => 'Visible',
                ])
                ->addHelp('connected',
                    "Permet de dynamiser le menu si l'utilisateur est connecté ou non")
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
            $config = $this->pageProvider->getConfigurationFor($subject->getPage()->getTemplate());
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
}
