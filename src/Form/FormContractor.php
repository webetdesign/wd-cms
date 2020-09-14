<?php


namespace WebEtDesign\CmsBundle\Form;


use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Form\Type\ModelTypeList;
use Sonata\DoctrineORMAdminBundle\Builder\FormContractor as BaseFormContractor;
use Sonata\Form\Type\CollectionType;

class FormContractor extends BaseFormContractor
{
    public function getDefaultOptions($type, FieldDescriptionInterface $fieldDescription)
    {
        $options = [];
        $options['sonata_field_description'] = $fieldDescription;

        if ($this->isAnyInstanceOf($type, [
            ModelType::class,
            ModelTypeList::class,
            ModelListType::class,
            ModelHiddenType::class,
            ModelAutocompleteType::class,
        ])) {
            if ('list' === $fieldDescription->getOption('edit')) {
                throw new \LogicException(sprintf(
                    'The `%s` type does not accept an `edit` option anymore,'
                    .' please review the UPGRADE-2.1.md file from the SonataAdminBundle',
                    ModelType::class
                ));
            }

            $options['class'] = $fieldDescription->getTargetModel();
            $options['model_manager'] = $fieldDescription->getAdmin()->getModelManager();

            if ($this->isAnyInstanceOf($type, [ModelAutocompleteType::class])) {
                if (!$fieldDescription->getAssociationAdmin()) {
                    throw new \RuntimeException(sprintf(
                        'The current field `%s` is not linked to an admin.'
                        .' Please create one for the target entity: `%s`',
                        $fieldDescription->getName(),
                        $fieldDescription->getTargetModel()
                    ));
                }
            }
        } elseif ($this->isAnyInstanceOf($type, [AdminType::class])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetModel()
                ));
            }

            if (!\in_array($fieldDescription->getMappingType(), [
                ClassMetadata::ONE_TO_ONE,
                ClassMetadata::MANY_TO_ONE,
            ], true)) {
                throw new \RuntimeException(sprintf(
                    'You are trying to add `%s` field `%s` which is not One-To-One or  Many-To-One.'
                    .' Maybe you want `%s` instead?',
                    AdminType::class,
                    $fieldDescription->getName(),
                    CollectionType::class
                ));
            }

            // set sensitive default value to have a component working fine out of the box
            $options['btn_add'] = false;
            $options['delete'] = false;

            $options['data_class'] = $fieldDescription->getAssociationAdmin()->getClass();
            $options['empty_data'] = static function () use ($fieldDescription) {
                return $fieldDescription->getAssociationAdmin()->getNewInstance();
            };
            $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'admin'));
            // NEXT_MAJOR: remove 'Sonata\CoreBundle\Form\Type\CollectionType'
        } elseif ($this->isAnyInstanceOf($type, [CollectionType::class, SonataCollectionType::class, 'Sonata\CoreBundle\Form\Type\CollectionType'])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetModel()
                ));
            }

            $options['type'] = AdminType::class;
            $options['modifiable'] = true;
            $options['type_options'] = [
                'sonata_field_description' => $fieldDescription,
                'data_class' => $fieldDescription->getAssociationAdmin()->getClass(),
                'empty_data' => static function () use ($fieldDescription) {
                    return $fieldDescription->getAssociationAdmin()->getNewInstance();
                },
            ];
        }

        return $options;
    }

    /**
     * @param string|null $type
     * @param string[] $classes
     * @return bool
     */
    private function isAnyInstanceOf(?string $type, array $classes): bool
    {
        if (null === $type) {
            return false;
        }

        foreach ($classes as $class) {
            if (is_a($type, $class, true)) {
                return true;
            }
        }

        return false;
    }

}
