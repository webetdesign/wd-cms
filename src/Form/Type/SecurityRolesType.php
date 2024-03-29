<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Form\Transformer\RestoreRolesTransformer;
use WebEtDesign\CmsBundle\Security\EditableRolesBuilder;

class SecurityRolesType extends AbstractType
{
    /**
     * @var EditableRolesBuilder
     */
    protected EditableRolesBuilder $rolesBuilder;

    public function __construct(EditableRolesBuilder $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
         * The form shows only roles that the current user can edit for the targeted user. Now we still need to persist
         * all other roles. It is not possible to alter those values inside an event listener as the selected
         * key will be validated. So we use a Transformer to alter the value and an listener to catch the original values
         *
         * The transformer will then append non editable roles to the user ...
         */
        $transformer = new RestoreRolesTransformer($this->rolesBuilder);

        // GET METHOD
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($transformer): void {
            $transformer->setOriginalRoles($event->getData());
        });

        // POST METHOD
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($transformer): void {
            $transformer->setOriginalRoles($event->getForm()->getData());
        });

        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'];

        if (isset($attr['class']) && empty($attr['class'])) {
            $attr['class'] = 'sonata-medium';
        }

        $view->vars['choice_translation_domain'] = 'CmsPageSecurity';

        $view->vars['attr'] = $attr;
        $view->vars['read_only_choices'] = $options['read_only_choices'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // make expanded default value
            'expanded' => true,

            'choices' => function (Options $options, $parentChoices) {
                if (!empty($parentChoices)) {
                    return [];
                }
                $roles = $this->rolesBuilder->getRoles($options['choice_translation_domain'], $options['expanded']);

                return array_flip($roles);
            },

            'read_only_choices' => function (Options $options) {
                if (!empty($options['choices'])) {
                    return [];
                }

                return $this->rolesBuilder->getRolesReadOnly($options['choice_translation_domain']);
            },

            'choice_translation_domain' => static function (Options $options, $value) {
                // if choice_translation_domain is true, then it's the same as translation_domain
                if (true === $value) {
                    $value = $options['translation_domain'];
                }
                if (null === $value) {
                    // no translation domain yet, try to ask sonata admin
                    $admin = null;
                    if (isset($options['sonata_admin'])) {
                        $admin = $options['sonata_admin'];
                    }
                    if (null === $admin && isset($options['sonata_field_description'])) {
                        $admin = $options['sonata_field_description']->getAdmin();
                    }
                    if (null !== $admin) {
                        $value = $admin->getTranslationDomain();
                    }
                }

                return $value;
            },

            'data_class' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'sonata_security_roles';
    }
}
