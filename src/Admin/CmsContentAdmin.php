<?php

namespace WebEtDesign\CmsBundle\Admin;

use Doctrine\ORM\EntityManager;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentSlider;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use WebEtDesign\CmsBundle\Form\BlockType;
use WebEtDesign\CmsBundle\Form\CmsContentSliderType;
use WebEtDesign\CmsBundle\Form\DataTransformer\CmsContentSliderDataTransformer;
use Symfony\Component\Form\CallbackTransformer;

final class CmsContentAdmin extends AbstractAdmin
{
    protected $em;
    protected $contentTypeOption;
    protected $media_class;

    public function __construct(string $code, string $class, string $baseControllerName, EntityManager $em, $contentTypeOption, string $media_class)
    {
        $this->em = $em;
        $this->contentTypeOption = $contentTypeOption;
        $this->media_class = $media_class;

        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('type');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
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

        $formMapper->add(
            'label',
            null,
            [
                'attr'  => ['disabled' => !$roleAdmin],
                'label' => 'Libéllé',
            ]
        );

        if ($roleAdmin) {
            $formMapper->add('code');
            $formMapper->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => CmsContentTypeEnum::getChoices(),
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

                case CmsContentTypeEnum::PROJECT_COLLECTION:
                    $formMapper->add(
                        'value',
                        EntityType::class,
                        [
                            'class'           => $this->contentTypeOption[CmsContentTypeEnum::PROJECT_COLLECTION]['class'],
                            'required'        => false,
                            'auto_initialize' => false,
                            'multiple'        => true,
                        ]
                    );
                    $formMapper->getFormBuilder()->get('value')
                        ->addModelTransformer(new CallbackTransformer(
                            function ($ids) {
                                $objects = $this->em->getRepository($this->contentTypeOption[CmsContentTypeEnum::PROJECT_COLLECTION]['class'])->findBy(['id' => json_decode($ids)]);
                                return $objects;
                            },
                            function ($objects) {
                                $ids = [];
                                foreach ($objects as $object) {
                                    $ids[] = $object->getId();
                                }
                                // transform the string back to an array
                                return json_encode($ids);
                            }
                        ));
                    break;
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
}
