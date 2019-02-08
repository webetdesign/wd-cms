<?php

namespace WebEtDesign\CmsBundle\Admin;

use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class CmsMenuAdmin extends AbstractAdmin
{
    public function createQuery($context = 'list')
    {
        $proxyQuery = parent::createQuery('list');
        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.root', 'ASC');
        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.lft', 'ASC');

        return $proxyQuery;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('lvl');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('name')
            ->add('lft')
            ->add('lvl')
            ->add('rgt')
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Configuration')
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('linkType', ChoiceType::class, [
                'choices'  => CmsMenuLinkTypeEnum::getChoices(),
                'label'    => 'Type de lien',
                'required' => false,
            ])
            ->addHelp('page', 'help page')
            ->add('page', null, [
                'sonata_help' => 'Si le type de lien utilisé est Page cms',
                'required' => false,
                'label'    => 'Page cms',
            ])
            ->add('linkValue', null, [
                'sonata_help' => 'Valeur pour les autres types de liens',
                'required' => false,
                'label'    => 'Valeur du lien',
            ])
            ->end()
            ->with('Déplacer')
            ->add('moveMode', ChoiceType::class, [
                'choices'  => [
                    'Déplacer comme premier enfant de'        => 'persistAsFirstChildOf',
                    'Déplacer en tant que dernier enfant de'  => 'persistAsLastChildOf',
                    'Déplacer en tant que prochain frère de'  => 'persistAsNextSiblingOf',
                    'Déplacer en tant que frère antérieur de' => 'persistAsPrevSiblingOf',
                ],
                'label'    => false,
                'required' => false,
            ])
            ->add('moveTarget', EntityType::class, [
                'class'         => CmsMenu::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->addOrderBy('m.root', 'asc')
                        ->addOrderBy('m.lft', 'asc');
                },
                'choice_label'  => function ($menu) {
                    return str_repeat('--', $menu->getLvl()) . ' ' . $menu->getName();
                },
                'label'         => false,
                'required'      => false,
            ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('lft')
            ->add('lvl')
            ->add('rgt');
    }
}
