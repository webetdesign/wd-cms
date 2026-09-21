<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class MenuIconType extends AbstractType
{
    private array $iconSet;

    public function __construct($iconSet) {
        $this->iconSet = $iconSet;
    }

    /**
     * @inheritDoc
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['iconSet'] = $this->iconSet;
    }


    /**
     * @inheritDoc
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix(): string
    {
        return 'cms_menu_icon';
    }


}
