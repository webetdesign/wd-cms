<?php

namespace WebEtDesign\CmsBundle\Admin;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


final class CmsMenuAdmin extends AbstractAdmin
{
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->add('createRootNode', 'initRoot')
            ->add('move', 'move');
    }

    //    public function createQuery($context = 'list')
    //    {
    //        $proxyQuery = parent::createQuery('list');
    //        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.root', 'ASC');
    //        $proxyQuery->addOrderBy($proxyQuery->getRootAlias() . '.lft', 'ASC');
    //
    //        return $proxyQuery;
    //    }

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
        /** @var CmsMenu $subject */
        $subject   = $formMapper->getAdmin()->getSubject();
        $roleAdmin = $this->canManageContent();


        $formMapper
            ->with('Configuration')
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('code', HiddenType::class);
        if ($subject && $subject->getMoveTarget() && $subject->getMoveTarget()->getLvl() == 0) {
            $formMapper->remove('code');
            $formMapper
                ->add('code', null, [
                    'label'    => 'Code',
                    'required' => true,
                ]);
        }
        if ($subject && $subject->getId() != null && $subject->getLvl() > 1) {
            $formMapper
                ->add('linkType', ChoiceType::class, [
                    'choices'  => CmsMenuLinkTypeEnum::getChoices(),
                    'label'    => 'Type de lien',
                    'required' => false,
                ])
                ->addHelp('page', 'help page')
                ->add('page', null, [
                    'sonata_help' => 'Si le type de lien utilisé est Page cms',
                    'required'    => false,
                    'label'       => 'Page cms',
                ])
                ->add('linkValue', null, [
                    'sonata_help' => 'Valeur pour les autres types de liens',
                    'required'    => false,
                    'label'       => 'Valeur du lien',
                ]);
        }

        // end configuration
        $formMapper->end();

        if ($subject && $subject->getId() != null) {

            $formMapper
                ->with('Configuration avancé')
                ->add('classes', null, [
                    'label'    => 'Classes',
                    'required' => false,
                ])
                ->add('connected', ChoiceType::class, [
                    'choices' => [
                        'Tout les temps'             => '',
                        'uniquement si connecté'     => 'ONLY_LOGIN',
                        'uniquement si non connecté' => 'ONLY_LOGOUT'
                    ],
                    'label'   => 'Visible',
                ])
                ->add('role')
                ->end();

        }

        if ($roleAdmin) {
            $formMapper
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

    protected function canManageContent()
    {
        /** @var User $user */
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
