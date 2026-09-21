<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class CmsVarsFormSection extends AbstractType
{
    public function __construct(protected TemplateRegistry $templateRegistry) { }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $template = $this->templateRegistry->get($options['template']);
        $exposed = $template->getVarsBag()->getExposed();

        $vars = [];
        foreach ($exposed as $object ) {
            $vars = array_merge($vars, array_keys($object));
        }

        $view->vars['vars_available'] = !empty($vars);
        $view->vars['data'] = $vars;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);

        $resolver->setRequired('template');
    }

    public function getBlockPrefix(): string
    {
        return 'admin_cms_vars_section';
    }


}
