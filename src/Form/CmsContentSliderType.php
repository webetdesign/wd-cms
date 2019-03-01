<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 28/02/2019
 * Time: 17:28
 */

namespace WebEtDesign\CmsBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsContentSlider;

class CmsContentSliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('url')
            ->add('file')
            ->add('description')
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CmsContentSlider::class,
        ]);
    }
}