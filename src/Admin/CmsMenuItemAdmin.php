<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\Form\Type\ImmutableArrayType;
use stdClass;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
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

    /**
     * @inheritDoc
     */
    public function __construct($code, $class, $baseControllerName, EntityManagerInterface $em, TemplateProvider $pageProvider)
    {
        $this->em           = $em;
        $this->pageProvider = $pageProvider;

        parent::__construct($code, $class, $baseControllerName);
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
                        'required' => false,
                        'label'    => 'Page',
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
            }

            $formMapper
                ->end()
                ->end();
            // fin tab lien
            $formMapper
                ->tab('Avancé')
                ->with('', ['box_class' => '']);

            $formMapper
                ->add('classes', null, [
                    'label'    => 'Classes',
                    'required' => false,
                ])
                ->add('connected', ChoiceType::class, [
                    'choices' => [
                        'Tout le temps'                                 => '',
                        "uniquement si l'utilisateur est connecté"       => 'ONLY_LOGIN',
                        "uniquement si l'utilisateur n'est pas connecté" => 'ONLY_LOGOUT'
                    ],
                    'label'   => 'Visible',
                ])
                ->addHelp('connected', "Permet de dynamiser le menu si l'utilisateur est connecté ou non")
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
        $config = $this->pageProvider->getConfigurationFor($subject->getPage()->getTemplate());
        $formMapper->add('params', CmsRouteParamsType::class, [
            'config' => $config,
            'route'  => $route,
            'object' => $subject,
            'label'  => 'Paramtre de l\'url de la page : ' . $route->getPath() . ' '
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
                        $getter = 'get' . ucfirst($param['property']);
                        if (method_exists($value, $getter)) {
                            $values[$name] = $value->$getter();
                        }
                    }
                }

                return json_encode($values);
            }
        ));
    }
}
