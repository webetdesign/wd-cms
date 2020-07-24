<?php

namespace WebEtDesign\CmsBundle\Form\CustomContents;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Admin\CmsContentAdmin;
use WebEtDesign\CmsBundle\Models\CustomContents\SortableEntity;
use WebEtDesign\CmsBundle\Utils\CmsCustomContentFormHelper;

class SortableEntityType extends AbstractType
{
    use CmsCustomContentFormHelper;

    /**
     * @var CmsContentAdmin
     */
    private $cmsContentAdmin;


    /**
     * EquipmentType constructor.
     * @param CmsContentAdmin $cmsContentAdmin
     */
    public function __construct(CmsContentAdmin $cmsContentAdmin)
    {
        $this->cmsContentAdmin = $cmsContentAdmin;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('position', HiddenType::class);
        if($options['admin']) {
            $this->buildModelListType($builder, $this->cmsContentAdmin, $options['admin'], 'entity', $options['entity_class'], [
                'label'           => false,
                'link_parameters' => $options['link_parameters']
            ]);
        } else {
            $builder->add('entity', EntityType::class, [
                'class' => $options['entity_type']
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('entity_class');

        $resolver->setDefaults([
            'data_class'      => SortableEntity::class,
            'admin'           => null,
            'link_parameters' => [],
            'csrf_protection' => false
        ]);
    }
}
