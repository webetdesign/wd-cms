<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\EventListener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use WebEtDesign\CmsBundle\CMS\Configuration\BlockDefinition;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;

class CmsDynamicBlockResizeFormListener extends ResizeFormListener
{

    public function __construct(
        private readonly BlockRegistry $blockRegistry,
        private readonly BlockDefinition $blockDefinition,
        string $type,
        array $options = [],
        bool $allowAdd = false,
        bool $allowDelete = false,
        $deleteEmpty = false
    ) {
        parent::__construct($type, $options, $allowAdd, $allowDelete, $deleteEmpty);
    }

    #[ArrayShape([
        FormEvents::PRE_SET_DATA => "string",
        FormEvents::PRE_SUBMIT   => "string",
        FormEvents::SUBMIT       => "array"
    ])] public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT       => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $block = $this->blockRegistry->get($this->blockDefinition);
        $form  = $event->getForm();
        $data  = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        foreach ($data as $name => $value) {
            $config = $block->getAvailableBlock($value['disc']);
            if ($config === null) {
                continue;
            }
            $opts = array_merge($this->options, [
                'label'        => '#' . $name . ' | ' . $config->getLabel(),
                'block_config' => $config
            ]);
            $form->add($name, $this->type, array_replace([
                'property_path' => '[' . $name . ']',
            ], $opts));
        }

    }

    public function preSubmit(FormEvent $event): void
    {
        $block = $this->blockRegistry->get($this->blockDefinition);
        $form  = $event->getForm();
        $data  = $event->getData();

        if (!\is_array($data)) {
            $data = [];
        }

        if ($form->has('block_selector')) {
            $form->remove('block_selector');
        }

        // Remove all empty rows
        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                if (!isset($data[$name])) {
                    $form->remove($name);
                }
            }
        }

        // Add all additional rows
        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                if ($name === 'block_selector') {
                    unset($data[$name]);
                    continue;
                }
                $form->remove($name);
                $config = $block->getAvailableBlock($value['disc']);
                $opts   = array_merge($this->options, [
                    'label'        => '#' . $name . ' | ' . $config->getLabel(),
                    'block_config' => $config
                ]);
                $name = (string) $name;
                $form->add($name, $this->type, array_replace([
                    'property_path' => '[' . $name . ']',
                ], $opts));
            }
        }

        $event->setData($data);
    }

}
