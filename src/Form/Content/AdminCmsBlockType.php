<?php

namespace WebEtDesign\CmsBundle\Form\Content;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\EventListener\JsonFormListener;
use WebEtDesign\CmsBundle\Factory\BlockFactory;
use WebEtDesign\CmsBundle\Form\Transformer\CmsBlockTransformer;

class AdminCmsBlockType extends AbstractType
{

    public function __construct(
        private BlockFactory $blockFactory
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('active', null, [
            'label' => 'Visible',
        ]);

        if ($options['config']) {
            $block = $this->blockFactory->get($options['config']);

            $opts = $block->getFormOptions();
            if (isset($opts['base_block_config']) && $opts['base_block_config']) {
                $opts['base_block_config'] = $options['config'];
            }

            $builder->add('value', $block->getFormType(), $opts);
            $builder->get('value')
                ->addModelTransformer(
                    $block->getModelTransformer()
                );
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['config']) {
            $block               = $this->blockFactory->get($options['config']);
            $view->vars['block_code'] = $block->getCode();
            $view->vars['block'] = $block;
        }

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CmsContent::class,
            'block'      => null,
            'config'     => null,
        ]);

    }

    public function getBlockPrefix(): string
    {
        return 'admin_cms_block';
    }


}
