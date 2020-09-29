<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use WebEtDesign\CmsBundle\Entity\CmsContent;
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
use WebEtDesign\CmsBundle\Services\TemplateProvider;

final class CmsContentAdmin extends AbstractAdmin
{
    protected $em;
    protected $customContents;
    protected $media_class;
    protected $container;
    protected $cmsSharedBlockAdmin;
    /**
     * @var TemplateProvider
     */
    private $blockProvider;
    /**
     * @var TemplateProvider
     */
    private $pageProvider;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        EntityManager $em,
        $contentTypeOption,
        string $media_class,
        Container $container,
        TemplateProvider $blockProvider,
        TemplateProvider $pageProvider
    ) {
        $this->em             = $em;
        $this->customContents = $contentTypeOption;
        $this->media_class    = $media_class;
        $this->container      = $container;
        $this->blockProvider  = $blockProvider;
        $this->pageProvider   = $pageProvider;

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
        unset($this->listModes['mosaic']);

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

        /** @var CmsContent $subject */
        $subject = $formMapper->getAdmin()->getSubject();

        $formMapper->add('active', null, [
            'label' => 'Actif',
        ]);
        $formMapper->add(
            'label',
            null,
            [
                'label' => 'Libéllé',
                'attr'  => [
                    'class' => 'admin_restriction',
                ],
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

        $formMapper->add('parent_heritance', null, [
            'label' => 'Contenu hérité',
        ]);
        $this->addHelp($formMapper, $subject, 'parent_heritance');

        if ($subject->getPage()) {
            $configs = $this->pageProvider->getConfigurationFor($subject->getPage()->getTemplate());
        } elseif ($subject->getDeclination() && $subject->getDeclination()->getPage()->getTemplate()) {
            $configs = $this->pageProvider->getConfigurationFor($subject->getDeclination()->getPage()->getTemplate());
        } elseif ($subject->getSharedBlockParent() && $subject->getSharedBlockParent()->getTemplate()) {
            $configs = $this->blockProvider->getConfigurationFor($subject->getSharedBlockParent()->getTemplate());
        }

        if ($subject && $subject->getId()) {
            switch ($subject->getType()) {
                case CmsContentTypeEnum::TEXT:
                    $formMapper->add('value', TextType::class, ['required' => false]);
                    $this->addHelp($formMapper, $subject, 'value');
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
                    $this->addHelp($formMapper, $subject, 'media');
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
                                'context' => 'cms_page',
                            ],
                        ]
                    );
                    $this->addHelp($formMapper, $subject, 'media');
                    break;

                case CmsContentTypeEnum::WYSYWYG:
                    $contents = [];
                    foreach ($configs['contents'] as $content) {
                        $contents[$content['code']] = $content;
                    }
                    $options = $contents[$subject->getCode()]['options'] ?? [];

                    $formMapper->add(
                        'value',
                        SimpleFormatterType::class,
                        [
                            'format'           => 'richhtml',
                            'ckeditor_context' => $options['ckeditor_context'] ?? 'cms_page',
                            'required'         => false,
                            'auto_initialize'  => false,
                        ]
                    );
                    $this->addHelp($formMapper, $subject, 'value');
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
                    $this->addHelp($formMapper, $subject, 'value');
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
                    $this->addHelp($formMapper, $subject, 'value');

                    break;
                case CmsContentTypeEnum::SHARED_BLOCK_COLLECTION:

                    $formMapper->add(
                        'sharedBlockList',
                        \Sonata\CoreBundle\Form\Type\CollectionType::class,
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

                    $formMapper->add('parent_heritance', null, [
                        'label' => 'Contenu hérité',
                    ]);
                    $this->addHelp($formMapper, $subject, 'parent_heritance');
                    break;
                case CmsContentTypeEnum::CHECKBOX:
                    $formMapper->add('value', CheckboxType::class, ['required' => false, 'label' => false]);

                    $formMapper->getFormBuilder()->get('value')->addModelTransformer(new CallbackTransformer(
                        function ($value) {
                            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        },
                        function ($value) {
                            return $value;
                        }
                    ));

                    $this->addHelp($formMapper, $subject, 'value');
                    break;
            }

            foreach ($this->customContents as $content => $configuration) {
                if ($subject->getType() === $content) {
                    /** @var AbstractCustomContent $contentService */
                    $contentService = $this->container->get($configuration['service']);
                    $formMapper->add(
                        'value',
                        $contentService->getFormType(),
                        $contentService->getFormOptions()
                    );

                    if (method_exists($contentService, 'getEventSubscriber')) {
                        $formMapper->getFormBuilder()->get('value')->addEventSubscriber($contentService->getEventSubscriber());
                    }

                    $formMapper->getFormBuilder()->get('value')->addModelTransformer($contentService->getCallbackTransformer());
                }
            }
        }
        //        if ($roleAdmin) {
        //            $formMapper->add('position');
        //        }
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
    }

    protected function getContentTypeChoices()
    {
        $customs = [];
        foreach ($this->customContents as $customContent => $configuration) {
            $customs[$configuration['name']] = $customContent;
        }

        return array_merge(CmsContentTypeEnum::getChoices(), $customs);
    }

    protected function addHelp(FormMapper $formMapper, $subject, $field)
    {
        if ($subject && !empty($subject->getHelp())) {
            $formMapper->addHelp($field, $subject->getHelp());
        }
    }
}
