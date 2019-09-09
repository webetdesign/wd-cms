<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class CmsSiteAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host')
            ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host')
            ->add('hostMultilingual')
            ->add('default')
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('label')
            ->add('locale')
            ->add('host')
            ->add('hostMultilingual')
            ->addHelp('hostMultilingual', "Dans un contexte multilingue, cocher cette case permet de gérer la langue avec l’extension du domaine sans préfixé la route <br>
                        sans prefix : monsite.fr <br> avec prefix : monsite.com/fr")
            ->add('default')
            ->addHelp('default', "Site associé par défaut lorsque l'on crée une page")
            ->add('flagIcon')
            ->addHelp('flagIcon', "<a href='https://www.countryflags.io' target='_blank'>Code du drapeau</a> ex: fr => <img src='https://www.countryflags.io/fr/flat/32.png' alt='fr'>")
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host')
            ;
    }
}
