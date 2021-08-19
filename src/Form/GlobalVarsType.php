<?php

namespace WebEtDesign\CmsBundle\Form;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

/**
 * Class TemplateType
 * @package App\Form
 *
 * Type of template defined in wd_cms.yaml
 */
class GlobalVarsType extends AbstractType
{
    protected $globalVars;
    protected $pageProvider;

    public function __construct(TemplateProvider $pageProvider, Container $container, $globalVarsDefinition)
    {
        $this->pageProvider = $pageProvider;
        $this->globalVars   = $container->get($globalVarsDefinition['global_service']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $page = $options['page'];
        $config = $this->pageProvider->getConfigurationFor($page->getTemplate());
        $objectVars = !empty($config['entityVars']) ? $config['entityVars']::getAvailableVars() : [];
        $d = $this->globalVars->getDelimiters();

        $vars = array_merge($this->globalVars::getAvailableVars(), $objectVars);
        $vars = array_map(function ($value) use ($d){
            return $d['s'].$value.$d['e'];
        }, $vars);

        $view->vars['user_vars'] = $vars;

        parent::buildView($view, $form, $options);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'page' => null,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'cms_global_vars';
    }

    public function getParent()
    {
        return TextType::class;
    }


}
