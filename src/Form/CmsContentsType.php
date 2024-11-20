<?php


namespace WebEtDesign\CmsBundle\Form;


use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\Form\EventListener\ResizeFormListener;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CmsContentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new ResizeFormListener(
            $options['type'],
            $options['type_options'],
            $options['modifiable'],
            $options['pre_bind_data_callback']
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['btn_add'] = $options['btn_add'];
        $view->vars['btn_catalogue'] = $options['btn_catalogue'];
        $view->vars['role_admin'] = $options['role_admin'];
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'modifiable'             => false,
            'type'                   => TextType::class,
            'type_options'           => [],
            'pre_bind_data_callback' => null,
            'btn_add'                => 'link_add',
            'btn_catalogue'          => 'SonataFormBundle',
            'role_admin'             => false,
        ]);
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
