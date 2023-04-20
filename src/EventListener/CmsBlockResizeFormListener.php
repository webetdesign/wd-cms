<?php

namespace WebEtDesign\CmsBundle\EventListener;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class CmsBlockResizeFormListener extends ResizeFormListener
{

    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly BlockRegistry $blockRegistry,
        string $type,
        array $options = [],
        bool $allowAdd = false,
        bool $allowDelete = false,
        $deleteEmpty = false
    ) {
        parent::__construct($type, $options, $allowAdd, $allowDelete, $deleteEmpty);
    }


    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var CmsContent $data */
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

            if ($value instanceof CmsContent) {
                if ($value->getPage()) {
                    $template = $value->getPage()->getTemplate();
                } elseif ($value->getDeclination()) {
                    $template = $value->getDeclination()->getPage()->getTemplate();
                } elseif ($value->getSharedBlockParent()) {
                    $template = $value->getSharedBlockParent()->getTemplate();
                }

                if (isset($template)) {
                    $tpl     = $this->templateRegistry->get($template);
                    $config  = $tpl->getBlock($value->getCode());
                    $block   = $config ? $this->blockRegistry->get($config) : null;
                    $options = array_merge($this->options, ['block' => $block, 'config' => $config]);

                    $form->add($name, $this->type, array_replace([
                        'property_path' => '[' . $name . ']',
                    ], $options ?? $this->options));
                }
            }
        }
    }
}
