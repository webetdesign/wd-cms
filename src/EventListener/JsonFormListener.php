<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebEtDesign\CmsBundle\EventListener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Resize a collection form element based on the data sent from the client.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonFormListener implements EventSubscriberInterface
{
    #[ArrayShape([FormEvents::PRE_SET_DATA => "array"])] public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => ['preSetData', 100],
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (null === $data) {
            $data = [];
        }
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $event->setData($data);
    }
}
