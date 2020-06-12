<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Knp\Menu\ItemInterface as MenuItemInterface;


final class CmsSiteAdmin extends AbstractAdmin
{
    protected $isMultilingual;
    protected $isMultisite;

    /**
     * @inheritDoc
     */
    public function __construct(string $code, string $class, string $baseControllerName, $cmsConfig)
    {
        $this->isMultisite = $cmsConfig['multisite'];
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
        unset($this->listModes['mosaic']);

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
            ->add('default')
            ->add('visible')
            ->addHelp('default', "Site associé par défaut lorsque l'on crée une page");
        if ($this->isMultisite) {
            $formMapper->add('templateFilter')
                ->addHelp('templateFilter', "Technique");
        }
        if ($this->isMultilingual) {
            $formMapper
                ->add('locale')
                ->add('hostMultilingual')
                ->addHelp('hostMultilingual', "Dans un contexte multilingue, cocher cette case permet de gérer la langue avec l’extension du domaine sans préfixé la route <br>
                        sans prefix : monsite.fr <br> avec prefix : monsite.com/fr")
                ->add('flagIcon')
                ->addHelp('flagIcon',
                    "<a href='https://www.countryflags.io' target='_blank'>Code du drapeau</a> ex: fr => <img src='https://www.countryflags.io/fr/flat/32.png' alt='fr'>");
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
