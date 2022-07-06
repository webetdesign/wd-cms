<?php


namespace WebEtDesign\CmsBundle\Form;


use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CmsContentsType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['role_admin'] = $options['role_admin'];
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('role_admin', false);
    }

    public function getParent()
    {
        return CollectionType::class;
    }


    public function getBlockPrefix()
    {
        return 'cms_contents';
    }

}
