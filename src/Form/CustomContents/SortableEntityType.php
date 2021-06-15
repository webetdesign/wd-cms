<?php

namespace WebEtDesign\CmsBundle\Form\CustomContents;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Models\CustomContents\SortableEntity;
use WebEtDesign\CmsBundle\Services\CmsSonataFormBuilderHelper;

class SortableEntityType extends AbstractType
{
    /**
     * @var CmsSonataFormBuilderHelper
     */
    private CmsSonataFormBuilderHelper $helper;

    /**
     * EquipmentType constructor.
     * @param CmsSonataFormBuilderHelper $helper
     */
    public function __construct(CmsSonataFormBuilderHelper $helper)
    {
        $this->helper = $helper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('position', HiddenType::class);
        if ($options['useModelListType']) {
            $this->helper->buildModelListType($builder, 'entity', CmsContent::class,
                $options['entity_class'], [
                    'label'           => false,
                    'link_parameters' => $options['link_parameters']
                ]);
        } else {
            $builder->add('entity', EntityType::class, [
                'class' => $options['entity_class']
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('entity_class');

        $resolver->setDefaults([
            'data_class'       => SortableEntity::class,
            'useModelListType' => false,
            'link_parameters'  => [],
            'csrf_protection'  => false
        ]);
    }
}
