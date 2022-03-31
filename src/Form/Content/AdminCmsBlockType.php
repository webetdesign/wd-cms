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
        private EntityManagerInterface $em,
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

            $options = $block->getFormOptions();
            if (isset($options['base_block']) && $options['base_block']) {
                $options['base_block'] = $block;
            }

            $builder->add('value', $block->getFormType(), $options);
            $builder->get('value')
                ->addModelTransformer(
                    $block->getModelTransformer() ?: new CmsBlockTransformer($this->em)
                );

            if ($block->isCompound()) {
                $builder->get('value')->addEventSubscriber(new JsonFormListener());
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['config']) {
            $block               = $this->blockFactory->get($options['config']);
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
