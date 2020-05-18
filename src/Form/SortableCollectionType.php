<?php


namespace WebEtDesign\CmsBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class SortableCollectionType extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sortable_collection';
    }
}
