<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use WebEtDesign\CmsBundle\Entity\CmsPage;
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

        /** @var CmsPage $object */
        $object = $this->getSubject();

        $container = $this->getConfigurationPool()->getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $formMapper
            ->tab('Général')// The tab call is optional
            ->with('', ['box_class' => ''])
            ->add('title', null, ['label' => 'Title'])
            ->add('template', TemplateType::class, ['label' => 'Modèle de page'])
            ->end()// End form group
            ->end()// End tab
        ;


        if ($this->isCurrentRoute('edit') || $this->getRequest()->isXmlHttpRequest()) {
            $formMapper->getFormBuilder()->setMethod('patch');
            $formMapper
                ->tab('Général')// The tab call is optional
                ->with('', ['box_class' => ''])
                ->add('active');

            if ($object->getClassAssociation()) {
                $entities = $em->getRepository($object->getClassAssociation())->{$object->getQueryAssociation()}();
                $choices  = [];
                foreach ($entities as $entity) {
                    $choices[$entity->__toString()] = $entity->getId();
                }
                $formMapper->add('association', ChoiceType::class,
                    [
                        'label'   => 'Association',
                        'choices' => $choices
                    ]
                );
            }


            $formMapper->end()// End form group
            ->end()// End tab
            ->tab('SEO')// The tab call is optional
            ->with('Général')
                ->add('seo_title')
                ->add('seo_description')
                ->add('seo_keywords')
                ->end()
                ->with('Facebook')
                ->add('fb_title')
                ->add('fb_type')
                ->add('fb_url')
                ->add('fb_image')
                ->add('fb_description')
                ->add('fb_site_name')
                ->add('fb_admins')
                ->end()
                ->with('Twitter')
                ->add('twitter_card')
                ->add('twitter_site')
                ->add('twitter_title')
                ->add('twitter_description')
                ->add('twitter_creator')
                ->add('twitter_image')
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
                        'edit'   => 'inline',
                        'inline' => 'table',
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
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
