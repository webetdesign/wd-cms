<?php

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\EventListener\CmsDynamicBlockResizeFormListener;
use WebEtDesign\CmsBundle\EventListener\JsonFormListener;

class DynamicBlockCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allow_add'] && $options['prototype']) {
            $prototypeOptions = array_replace([
                'required' => $options['required'],
                'label'    => $options['prototype_name'] . 'label__',
            ], $options['entry_options']);

            if (null !== $options['prototype_data']) {
                $prototypeOptions['data'] = $options['prototype_data'];
            }

            $prototypes = [];

            $availableBlocks = [];
            foreach ($options['base_block']->getAvailableBlocks() as $config) {
                $availableBlocks[$config->getLabel()] = $config->getCode();

                $prototypeOptions = array_merge($prototypeOptions, ['block_config' => $config]);
                $prototype        = $builder->create(
                    $options['prototype_name'],
                    $options['entry_type'],
                    $prototypeOptions);

                $prototypes[$config->getCode()] = $prototype->getForm();
            }

            $blockSelector = $builder->create('block_selector', ChoiceType::class, [
                'choices' => $availableBlocks,
                'attr'    => [
                    'mapped'               => false,
                    'data-cms-adbc-target' => 'blockSelector'
                ]
            ]);
            $builder->setAttribute('block_selector', $blockSelector->getForm());
            $builder->setAttribute('prototypes', $prototypes);
        }

        $resizeListener = new CmsDynamicBlockResizeFormListener(
            $options['base_block'],
            $options['entry_type'],
            $options['entry_options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );

        $builder->addEventSubscriber(new JsonFormListener());
        $builder->addEventSubscriber($resizeListener);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return $value;
            },
            function ($value) {
                if (is_array($value)) {
                    usort($value, fn($a, $b) => $a['position'] > $b['position']);
                    $value = array_map(function ($item) {
                        unset($item['position']);
                        return $item;
                    }, $value);
                }
                return $value;
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'allow_add'    => $options['allow_add'],
            'allow_delete' => $options['allow_delete'],
        ]);

        if ($form->getConfig()->hasAttribute('prototypes')) {
            $prototypes = $form->getConfig()->getAttribute('prototypes');

            $view->vars['prototypes'] = array_map(
                fn($prototype) => $prototype->setParent($form)->createView($view),
                $prototypes
            );
        }
        if ($form->getConfig()->hasAttribute('block_selector')) {
            $prototype                    = $form->getConfig()->getAttribute('block_selector');
            $view->vars['block_selector'] = $prototype->setParent($form)->createView($view);
        }
    }


    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type'    => DynamicBlockLoaderType::class,
            'entry_options' => [
                'required' => false,
                //                'label' => false,
            ],
            'allow_add'     => true,
            'allow_delete'  => true,
        ]);

        $resolver->setRequired(['base_block']);
    }

    public function getBlockPrefix(): string
    {
        return 'admin_cms_dynamic_block_collection';
    }

}
