<?php

namespace WebEtDesign\CmsBundle\Admin;

use App\Application\Sonata\MediaBundle\Entity\Media;
use App\Application\Sonata\UserBundle\Entity\User;
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

final class CmsContentAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('type')
            ->add('value');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('type')
            ->add('value')
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

        $formMapper->add(
            'media',
            ModelListType::class,
            [
                'class'         => Media::class,
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

        $formMapper->add('value', TextType::class, ['attr' => ['disabled' => 'disabled'], 'required' => false]);

        $formModifier = function (FormInterface $form, FormMapper $formMapper, $type) use ($admin) {

            if ($form->has('value')) {
                $form->remove('value');
            }
            if ($form->has('media')) {
                $form->remove('media');
            }

            $hiddenMediaType = $formMapper->getFormBuilder()->getFormFactory()
                ->createNamed(
                    'media',
                    HiddenType::class,
                    null,
                    [
                        'required'        => false,
                        'auto_initialize' => false,
                    ]
                );
            $hiddenValueType = $formMapper->getFormBuilder()->getFormFactory()
                ->createNamed(
                    'value',
                    HiddenType::class,
                    null,
                    [
                        'required'        => false,
                        'auto_initialize' => false,
                    ]
                );

            switch ($type) {
                case CmsContentTypeEnum::TEXT:
                    $valueType = $formMapper->getFormBuilder()->getFormFactory()
                        ->createNamed(
                            'value',
                            TextType::class,
                            null,
                            [
                                'required'        => false,
                                'auto_initialize' => false,
                            ]
                        );
                    $form->add($valueType);
                    $form->add($hiddenMediaType);
                    break;
                case CmsContentTypeEnum::TEXTAREA:
                    $valueType = $formMapper->getFormBuilder()->getFormFactory()
                        ->createNamed(
                            'value',
                            TextareaType::class,
                            null,
                            [
                                'required'        => false,
                                'auto_initialize' => false,
                            ]
                        );
                    $form->add($valueType);
                    $form->add($hiddenMediaType);
                    break;
                case CmsContentTypeEnum::WYSYWYG:
                    $valueType = $formMapper->getFormBuilder()->getFormFactory()
                        ->createNamed(
                            'value',
                            SimpleFormatterType::class,
                            null,
                            [
                                'format'           => 'richhtml',
                                'ckeditor_context' => 'cms_page',
                                'required'         => false,
                                'auto_initialize'  => false,
                            ]
                        );
                    $form->add($valueType);
                    $form->add($hiddenMediaType);
                    break;
                case CmsContentTypeEnum::MEDIA:
                    $mediaType = $formMapper->getFormBuilder()->getFormFactory()
                        ->createNamed(
                            'media',
                            ModelListType::class,
                            null,
                            [
                                'class'                    => Media::class,
                                'required'                 => false,
                                'auto_initialize'          => false,
                                'model_manager'            => $admin->getModelManager(),
                                'sonata_field_description' => $admin->getFormFieldDescription(
                                    'media'
                                ),
                                //                            'sonata_admin' => $admin
                            ]
                        );
                    $form->add($mediaType);
                    $form->add($hiddenValueType);
                    break;

            }
        };

        $formMapper->getFormBuilder()->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier, $formMapper, $admin) {
                $form    = $event->getForm();
                $subject = $admin->getSubject($event->getData());

                if ($subject) {
                    $formModifier($form, $formMapper, $subject->getType());
                }

            }
        );

        if ($roleAdmin) {
            $formMapper->get('type')->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($formModifier, $formMapper) {
                    // It's important here to fetch $event->getForm()->getData(), as
                    // $event->getData() will get you the client data (that is, the ID)
                    $type = $event->getData();

                    // since we've added the listener to the child, we'll have to pass on
                    // the parent to the callback functions!
                    $formModifier($event->getForm()->getParent(), $formMapper, $type);
                }
            );
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
        /** @var User $user */
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
