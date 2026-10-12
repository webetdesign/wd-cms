<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Registry\BlockRegistry;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class CmsBlockResizeFormListener implements EventSubscriberInterface
{
    /**
     * $type and $options are private in ResizeFormListener since Symfony 7,
     * so this listener keeps its own copy.
     */
    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly BlockRegistry $blockRegistry,
        private readonly string $type,
        private readonly array $options = [],
        bool $allowAdd = false,
        bool $allowDelete = false,
        $deleteEmpty = false,
    ) {
        $this->entryType = $type;
        $this->entryOptions = $options;
        $this->resizeFormListener = new ResizeFormListener($type, $options, $allowAdd, $allowDelete, $deleteEmpty);
    }

    /**
     * The rows are built on PRE_SET_DATA by preSetData() below: the parent
     * POST_SET_DATA listener would only report that override as deprecated.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT       => ['onSubmit', 50],
        ];
    }


    public function preSubmit(FormEvent $event): void
    {
        $this->resizeFormListener->preSubmit($event);
    }

    public function onSubmit(FormEvent $event): void
    {
        $this->resizeFormListener->onSubmit($event);
    }

    public function postSetData(FormEvent|PostSetDataEvent $event): void
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
            $name = (string) $name;
            if ($value instanceof CmsContent) {
                if ($value->getPage()) {
                    $template = $value->getPage()->getTemplate();
                } elseif ($value->getDeclination()) {
                    $template = $value->getDeclination()->getPage()->getTemplate();
                } elseif ($value->getSharedBlockParent()) {
                    $template = $value->getSharedBlockParent()->getTemplate();
                } elseif (isset($this->options['template_code'])) {
                    $template = $this->options['template_code'];
                }

                if (isset($template)) {
                    $tpl     = $this->templateRegistry->get($template);
                    $config  = $tpl->getBlock($value->getCode());
                    $block   = $config ? $this->blockRegistry->get($config) : null;
                    $options = array_merge($this->entryOptions, ['block' => $block, 'config' => $config]);

                    $form->add($name, $this->entryType, array_replace([
                        'property_path' => '[' . $name . ']',
                    ], $options ?? $this->entryOptions));
                }
            }
        }
    }
}
