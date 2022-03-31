<?php

namespace WebEtDesign\CmsBundle\Form\Content\Dynamic;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

class DynamicBlockType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $em,
        private BlockFactory $blockFactory
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['block_config']) {

            $block = $this->blockFactory->get($options['block_config']);

            $opts = $block->getFormOptions();

            $builder->add($block->getCode(), $block->getFormType(), $opts);

            $builder->get($block->getCode())->addModelTransformer(new CmsBlockTransformer($this->em));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'block_config' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'admin_cms_dynamic_block';
    }


}
