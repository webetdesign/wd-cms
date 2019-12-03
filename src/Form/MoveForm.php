<?php


namespace WebEtDesign\CmsBundle\Form;


use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsPage;

class MoveForm extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $object = $options['object'];

        $builder
            ->add('moveMode', ChoiceType::class, [
                'choices'  => [
                    'Déplacer en premier, dessous :' => 'persistAsFirstChildOf',
                    'Déplacer en dernier, dessous :' => 'persistAsLastChildOf',
                    'Déplacer après :'               => 'persistAsNextSiblingOf',
                    'Déplacer avant :'               => 'persistAsPrevSiblingOf',
                ],
                'expanded' => true,
                'label'    => false,
                'required' => true,
                'data'     => 'persistAsFirstChildOf'
            ])
            ->add('moveTarget', EntityType::class, [
                'class'         => $options['data_class'] !== null ? $options['data_class'] : $options['entity'],
                'query_builder' => function (EntityRepository $er) use ($object) {
                    if ($object instanceof CmsMenuItem) {
                        return $er->createQueryBuilder('m')
                            ->andWhere('m.menu = :menu')
                            ->setParameter('menu', $object->getMenu())
                            ->addOrderBy('m.root', 'asc')
                            ->addOrderBy('m.lft', 'asc');
                    } elseif ($object instanceof CmsPage) {
                        return $er->createQueryBuilder('p')
                            ->andWhere('p.site = :site')
                            ->setParameter('site', $object->getSite())
                            ->addOrderBy('p.root', 'asc')
                            ->addOrderBy('p.lft', 'asc');
                    } else {
                        return $er->createQueryBuilder('o')
                            ->addOrderBy('o.root', 'asc')
                            ->addOrderBy('o.lft', 'asc');
                    }
                },
                'choice_label'  => function ($object) {
                    if ($object instanceof CmsMenuItem) {
                        $lvl = $object->getLvl();
                        if ($lvl == 0) {
                            return $object->getMenu()->getLabel();
                        }
                    } else {
                        $lvl = $object->getLvl();
                    }
                    return str_repeat('—', $lvl) . ' ' . $object->__toString();
                },
                'choice_attr' => function($choice, $key, $value) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['data-root' => $choice->isRoot()];
                },
                'label'         => false,
                'required'      => false,
            ]);

    }

    public function getBlockPrefix()
    {
        return 'wd_cms_move_form';
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('entity', null);

        $resolver->remove('data_class');
        $resolver->setRequired('data_class');
        $resolver->setRequired('object');
    }


}