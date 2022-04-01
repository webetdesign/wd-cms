<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use WebEtDesign\CmsBundle\CmsBlock\DynamicBlock;

class CmsDynamicBlockResizeFormListener extends ResizeFormListener
{

    public function __construct(
        private DynamicBlock $cmsblock,
        string $type,
        array $options = [],
        bool $allowAdd = false,
        bool $allowDelete = false,
        $deleteEmpty = false
    ) {
        parent::__construct($type, $options, $allowAdd, $allowDelete, $deleteEmpty);
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT       => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

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

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            $config = $this->cmsblock->getAvailableBlock($value['disc']);
            if(!$config) {
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

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

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
                $config = $this->cmsblock->getAvailableBlock($value['disc']);
                $opts = array_merge($this->options, [
                    'label'        => '#' . $name . ' | ' . $config->getLabel(),
                    'block_config' => $config
                ]);
                $form->add($name, $this->type, array_replace([
                    'property_path' => '[' . $name . ']',
                ], $opts));
            }
        }

        $event->setData($data);
    }

}
