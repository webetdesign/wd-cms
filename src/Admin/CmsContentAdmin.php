<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Exception;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use WebEtDesign\CmsBundle\Security\Voter\ManageContentVoter;

final class CmsContentAdmin extends AbstractAdmin
{
    protected ?array $customContents;

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('active')
            ->add('code')
            ->add('label')
            ->add('type');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);


        $listMapper
            ->add('id')
            ->add('active')
            ->add('code')
            ->add('label')
            ->add('type')
            ->add(
                ListMapper::NAME_ACTIONS,
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

    /**
     * @param FormMapper $formMapper
     * @throws Exception
     * @author Benjamin Robert
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper->getFormBuilder()->setMethod('patch');

        /** @var CmsContent $subject */
        $subject = $this->getSubject();

        $formMapper->add('active', null, [
            'label' => 'Visible',
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

        if ($this->isGranted(ManageContentVoter::CAN_MANAGE_CONTENT)) {
            $formMapper->add('code');
            $formMapper->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => $this->getContentTypeChoices(),
                ]
            );
        }


//        if ($subject->getPage()) {
//            $configs = $this->pageProvider->getConfigurationFor($subject->getPage()->getTemplate());
//        } elseif ($subject->getDeclination() && $subject->getDeclination()->getPage()->getTemplate()) {
//            $configs = $this->pageProvider->getConfigurationFor($subject->getDeclination()->getPage()->getTemplate());
//        } elseif ($subject->getSharedBlockParent() && $subject->getSharedBlockParent()->getTemplate()) {
//            $configs = $this->blockProvider->getConfigurationFor($subject->getSharedBlockParent()->getTemplate());
//        }else{
            $configs = [
                'contents' => []
            ];
//        }

        if ($this->canInheritFromParent($subject)) {
            $formMapper->add('parent_heritance', null, [
                'label' => 'Hériter le contenu de la page parent',
                'attr'  => [
                    'class' => 'checkbox-right'
                ]
            ]);
        }

        $contents = [];
        foreach ($configs['contents'] as $content) {
            $contents[$content['code']] = $content;
        }
        $contentParams = $contents[$subject->getCode()] ?? [];

        if ($subject && $subject->getId()) {

            $subject->collapseOpen = $contentParams['open'] ?? false;
            $options = $contentParams['options'] ?? [];
            switch ($subject->getType()) {
                case CmsContentTypeEnum::TEXT:
                    $formMapper->add('value', TextType::class, ['required' => false, 'help' => $subject->getHelp()]);
                    break;

                case CmsContentTypeEnum::WYSIWYG:
                    $formMapper->add(
                        'value',
                        CKEditorType::class,
                        [
                            'format'           => 'richhtml',
                            'ckeditor_context' => $options['ckeditor_context'] ?? 'cms_page',
                            'required'         => false,
                            'auto_initialize'  => false,
                            'help' => $subject->getHelp()
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
                            'help' => $subject->getHelp()
                        ]
                    );
                    break;

                case CmsContentTypeEnum::CHECKBOX:
                    $formMapper->add('value', CheckboxType::class,
                        [
                            'required' => false,
                            'label' => false,
                            'help' => $subject->getHelp()
                        ]);

                    $formMapper->getFormBuilder()->get('value')->addModelTransformer(new CallbackTransformer(
                        function ($value) {
                            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        },
                        function ($value) {
                            return $value;
                        }
                    ));

                    break;
            }
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('code')
            ->add('label')
            ->add('type')
            ->add('value');
    }

    public function prePersist($content): void
    {
        $this->preUpdate($content);
    }

    public function preUpdate($content): void
    {
    }

    protected function getContentTypeChoices(): array
    {
        $customs = [];
        foreach ($this->customContents as $customContent => $configuration) {
            $customs[$configuration['name']] = $customContent;
        }

        return array_merge(CmsContentTypeEnum::getChoices(), $customs);
    }

    private function canInheritFromParent(CmsContent $content): bool
    {
        if ($content->getPage() && $content->getPage()->getParent() && $content->getPage()->getParent()->getContent($content->getCode())) {
            return true;
        }

        return false;
    }
}
