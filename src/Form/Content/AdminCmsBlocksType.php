<?php

namespace WebEtDesign\CmsBundle\Form\Content;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

class AdminCmsBlocksType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $em,
        private BlockFactory $blockFactory
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['base_block']) {
            foreach ($options['base_block']->getBlocks() as $config) {
                $block = $this->blockFactory->get($config);

                $options = $block->getFormOptions();
                if (isset($options['base_block']) && $options['base_block']) {
                    $options['base_block'] = $block;
                }

                $builder->add($block->getCode(), $block->getFormType(), $options);

                $builder->get($block->getCode())->addModelTransformer(new CmsBlockTransformer($this->em));
            }
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'base_block'  => null,
            'blocks' => null,
        ]);
    }

}
