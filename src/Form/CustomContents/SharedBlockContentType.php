<?php


namespace WebEtDesign\CmsBundle\Form\CustomContents;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;

class SharedBlockContentType extends AbstractType
{

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('block', EntityType::class, [
            'class'    => CmsSharedBlock::class,
            'required' => false,
            'group_by' => function(CmsSharedBlock $block) {
                return $block->getSite()->__toString();
            },
        ]);
    }

}
