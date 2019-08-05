<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;
use WebEtDesign\CmsBundle\Entity\CmsContentHasSharedBlock;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use Symfony\Component\Form\CallbackTransformer;
use WebEtDesign\CmsBundle\Services\AbstractCustomContent;

final class CmsContentAdmin extends AbstractAdmin
{
    protected $em;
    protected $customContents;
    protected $media_class;
    protected $container;
    protected $cmsSharedBlockAdmin;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        $contentTypeOption,
        string $media_class,
        Container $container
    ) {
        $this->em             = $em;
        $this->customContents = $contentTypeOption;
        $this->media_class    = $media_class;
        $this->container      = $container;

        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('active')
            ->add('code')
            ->add('label')
            ->add('type');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('active')
            ->add('code')
            ->add('label')
            ->add('type')
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
        $formMapper->getFormBuilder()->setMethod('patch');

        $roleAdmin = $this->canManageContent();
        $admin     = $this;

        $formMapper->add('active', null, [
            'label' => 'Actif',
        ]);
        $formMapper->add(
            'label',
            null,
            [
                'label' => 'Libéllé',
                'attr'  => [
                    'class' => 'admin_restriction'
                ]
            ]
        );

        if ($roleAdmin) {
            $formMapper->add('code');
            $formMapper->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => $this->getContentTypeChoices(),
                ]
            );
        }

        if ($formMapper->getAdmin()->getSubject() && $formMapper->getAdmin()->getSubject()->getId()) {

            switch ($formMapper->getAdmin()->getSubject()->getType()) {
                case CmsContentTypeEnum::TEXT:
                    $formMapper->add('value', TextType::class, ['required' => false]);
                    break;

                case CmsContentTypeEnum::SLIDER:
                    $formMapper->add(
                        'sliders',
                        CollectionType::class,
                        [
                            'label'        => false,
                            'by_reference' => false,
                            //                            'btn_add'      => $roleAdmin ? 'Ajouter' : false,
                            'type_options' => [
                                'delete' => $roleAdmin,
                            ],
                        ],
                        [
                            'inline' => 'table',
                            'edit'   => 'inline'
                        ]
                    );
                    break;

                case CmsContentTypeEnum::IMAGE:
                    $formMapper->add(
                        'media',
                        ModelListType::class,
                        [
                            'class'         => $this->media_class,
                            'required'      => false,
                            'model_manager' => $admin->getModelManager(),
                        ],
                        [
                            "link_parameters" => [
                                'context'  => 'cms_page',
                                'provider' => 'sonata.media.provider.image',
                            ],
                        ]
                    );
                    break;

                case CmsContentTypeEnum::MEDIA:
                    $formMapper->add(
                        'media',
                        ModelListType::class,
                        [
                            'class'         => $this->media_class,
                            'required'      => false,
                            'model_manager' => $admin->getModelManager(),
                        ],
                        [
                            "link_parameters" => [
                                'context' => 'cms_page'
                            ],
                        ]
                    );
                    break;

                case CmsContentTypeEnum::WYSYWYG:
                    $formMapper->add(
                        'value',
                        SimpleFormatterType::class,
                        [
                            'format'           => 'richhtml',
                            'ckeditor_context' => 'cms_page',
                            'required'         => false,
                            'auto_initialize'  => false,
                        ]
                    );
                    break;

                case CmsContentTypeEnum::TEXTAREA:
                    $formMapper->add(
                        'value',
                        TextareaType::class,
                        [
                            'required'        => false,
                            'auto_initialize' => false,
                        ]
                    );
                    break;

                case CmsContentTypeEnum::SHARED_BLOCK:
                    $formMapper->add(
                        'value',
                        EntityType::class,
                        [
                            'class'    => CmsSharedBlock::class,
                            'required' => false,
                        ]
                    );

                    $formMapper->getFormBuilder()->get('value')->addModelTransformer(new CallbackTransformer(
                        function ($value) {
                            return $this->em->getRepository(CmsSharedBlock::class)->find((int)$value);
                        },
                        function ($value) {
                            return $value !== null ? $value->getId() : null;
                        }
                    ));

                    break;
                case CmsContentTypeEnum::SHARED_BLOCK_COLLECTION:
                    $formMapper->add(
                        'sharedBlockList',
                        CollectionType::class,
                        [
                            'by_reference' => false,
                            'required'     => false,
                        ],
                        [
                            'edit'     => 'inline',
                            'inline'   => 'table',
                            'sortable' => 'position',
                        ]
                    );

                    break;

            }

            foreach ($this->customContents as $content => $configuration) {
                if ($formMapper->getAdmin()->getSubject()->getType() === $content) {
                    /** @var AbstractCustomContent $contentService */
                    $contentService = $this->container->get($configuration['service']);
                    $formMapper->add(
                        'value',
                        $contentService->getFormType(),
                        $contentService->getFormOptions()
                    );

                    $formMapper->getFormBuilder()->get('value')->addModelTransformer($contentService->getCallbackTransformer());

                }
            }
        }
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('type')
            ->add('value');
    }

    protected function canManageContent()
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }

    public function prePersist($content)
    {
        $this->preUpdate($content);
    }

    public function preUpdate($content)
    {
        $content->setSliders($content->getSliders());
    }

    protected function getContentTypeChoices()
    {
        $customs = [];
        foreach ($this->customContents as $customContent => $configuration) {
            $customs[$configuration['name']] = $customContent;
        }

        return array_merge(CmsContentTypeEnum::getChoices(), $customs);
    }
}
