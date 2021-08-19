<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;


final class CmsSiteAdmin extends AbstractAdmin
{
    protected ?bool $isMultilingual;
    protected ?bool $isMultisite;

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
    }

    /**
     * @inheritDoc
     */
    public function __construct(string $code, string $class, string $baseControllerName, $cmsConfig)
    {
        $this->isMultisite    = $cmsConfig['multisite'];
        $this->isMultilingual = $cmsConfig['multilingual'];

        parent::__construct($code, $class, $baseControllerName);
    }


    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $modes = $this->getListModes();
        unset($modes['mosaic']);
        $this->setListModes($modes);

        $listMapper
            ->add('id')
            ->add('label')
            ->add('host')
            ->add('visible')
            ->add('default');
        if ($this->isMultilingual) {
            $listMapper
                ->add('locale')
                ->add('hostMultilingual');
        }
        $listMapper
            ->add('_action', null, [
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);

    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('label')
            ->add('host')
            ->add('default', null, [
                'help' => "Site associé par défaut lorsque l'on crée une page"
            ]);
        if ($this->isMultisite) {
            $formMapper->add('templateFilter', null, [
                'help' => 'Technique'
            ]);
        }
        if ($this->isMultilingual) {
            $formMapper
                ->add('visible')
                ->add('locale')
                ->add('hostMultilingual', null, [
                    'help' => "Dans un contexte multilingue, cocher cette case permet de gérer la langue avec l’extension du domaine sans préfixé la route <br>
                        sans prefix : monsite.fr <br> avec prefix : monsite.com/fr"
                ])
                ->add('flagIcon', null, [
                    'help' =>  "<a href='https://www.countryflags.io' target='_blank'>Code du drapeau</a> ex: fr => <img src='https://www.countryflags.io/fr/flat/32.png' alt='fr'>"
                ]);
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('label')
            ->add('locale')
            ->add('host');
    }
}
