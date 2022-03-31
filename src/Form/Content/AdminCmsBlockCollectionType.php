<?php

namespace WebEtDesign\CmsBundle\Form\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\EventListener\CmsBlockResizeFormListener;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Factory\TemplateFactoryInterface;

class AdminCmsBlockCollectionType extends AbstractType
{
    public function __construct(private BlockFactory $blockFactory) { }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allow_add'] && $options['prototype']) {
            $prototypeOptions = array_replace([
                'required' => $options['required'],
                'label' => $options['prototype_name'].'label__',
            ], $options['entry_options']);

            if (null !== $options['prototype_data']) {
                $prototypeOptions['data'] = $options['prototype_data'];
            }

            $prototype = $builder->create($options['prototype_name'], $options['entry_type'], $prototypeOptions);
            $builder->setAttribute('prototype', $prototype->getForm());
        }

        $resizeListener = new CmsBlockResizeFormListener(
            $options['templateFactory'],
            $this->blockFactory,
            $options['entry_type'],
            $options['entry_options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );

        $builder->addEventSubscriber($resizeListener);
    }


    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type'    => AdminCmsBlockType::class,
            'entry_options' => [],
            'allow_add'     => false,
            'allow_delete'  => false,
            'by_reference'  => false,
            'block_prefix'  => 'cms_contents_collection'
        ]);

        $resolver->setRequired(['templateFactory']);

        $resolver->setAllowedTypes('templateFactory', TemplateFactoryInterface::class);
    }


}
