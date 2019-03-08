<?php

namespace WebEtDesign\CmsBundle\Admin;

use App\Application\Sonata\UserBundle\Entity\User;
use WebEtDesign\CmsBundle\Form\TemplateType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;

class CmsPageAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('title');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('title', null, [
                'label' => 'Titre',
            ])
            ->add('route.path', null, [
                'label' => 'Chemin',
            ])
            ->add('active', null, [
                'label' => 'Actif',
            ])
            ->add(
                '_action',
                null,
                [
                    'actions' => [
                        'show'   => [],
                        'edit'   => [],
                        'delete' => [],
                    ],
                ]
            );
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $roleAdmin = $this->canManageContent();

        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => ''])
            ->add('title', null, ['label' => 'title'])
            ->add('template', TemplateType::class, ['label' => 'Modèle de page'])
            ->end()// End form group
            ->end()// End tab
        ;

        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('patch');
            $formMapper
                ->tab('Général')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active')
                ->end()// End form group
                ->end()// End tab
                ->tab('SEO')// The tab call is optional
                ->with(' ')
                ->add('seo_title')
                ->add('seo_description')
                ->add('seo_keywords')
                ->end()
                ->end()
                ->tab('Contenus')
                ->with('', ['box_class' => ''])
                ->add(
                    'contents',
                    CollectionType::class,
                    [
                        'label'        => false,
                        'by_reference' => false,
                        'btn_add'      => $roleAdmin ? 'Ajouter' : false,
                        'type_options' => [
                            'delete' => $roleAdmin,
                        ],
                    ],
                    [
//                        'edit'   => 'inline',
//                        'inline' => 'table',
                    ]
                )
                ->end()
                ->end()
                ->tab('Route')
                ->with('', ['box_class' => ''])
                ->add('route.name', null, ['label' => 'Route name (technique)'])
                ->add('route.path', null, ['label' => 'Chemin', 'attr' => ['class' => 'cms_route_path_input']])
                ->add(
                    'route.methods',
                    ChoiceType::class,
                    [
                        'label'    => 'Méthodes',
                        'choices'  => [
                            Request::METHOD_GET    => Request::METHOD_GET,
                            Request::METHOD_POST   => Request::METHOD_POST,
                            Request::METHOD_DELETE => Request::METHOD_DELETE,
                            Request::METHOD_PUT    => Request::METHOD_PUT,
                            Request::METHOD_PATCH  => Request::METHOD_PATCH,
                        ],
                        'multiple' => true,
                    ]
                )
                ->add('route.controller', null, ['label' => 'Controller (technique)'])
                ->add('route.defaults', HiddenType::class, [
                    'attr' => [
                        'class' => 'cms_route_default_input'
                    ]
                ])
                ->add('route.requirements', HiddenType::class, [
                    'attr' => [
                        'class' => 'cms_route_requirements_input'
                    ]
                ])
                ->end()
                ->end();
        }

    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('title');
    }

    protected function canManageContent()
    {
        /** @var User $user */
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
