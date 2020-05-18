<?php

namespace WebEtDesign\CmsBundle\Services\CustomContents;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\EventListener\JsonFormListener;
use WebEtDesign\CmsBundle\Form\CustomContents\SortableEntityType;
use WebEtDesign\CmsBundle\Form\CustomContents\Transformer\SortableCollectionTransformer;
use WebEtDesign\CmsBundle\Form\SortableCollectionType;
use WebEtDesign\CmsBundle\Services\AbstractCustomContent;

class SortableCollection extends AbstractCustomContent
{

    const CMS_SORTABLE_COLLECTION = 'CMS_SORTABLE_COLLECTION';

    /**
     * @var mixed
     */
    protected $entity;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var AdminInterface
     */
    protected $admin;
    /**
     * @var array
     */
    private $link_parameters;

    /**
     * @param EntityManagerInterface $em
     * @param $entity
     * @param AdminInterface $admin
     * @param array $link_prameters
     */
    public function __construct(
        EntityManagerInterface $em,
        $entity = null,
        AdminInterface $admin = null,
        $link_prameters = []
    ) {
        $this->em             = $em;
        $this->entity         = $entity;
        $this->admin          = $admin;
        $this->link_parameters = $link_prameters;
    }

    function getFormOptions(): array
    {
        return [
            'entry_type'    => SortableEntityType::class,
            'entry_options' => [
                'entity_class'    => $this->entity,
                'admin'           => $this->admin,
                'link_parameters' => $this->link_parameters,
            ],
            'allow_add'     => true,
            'allow_delete'  => true,
            'required'      => false,
        ];
    }

    function getFormType(): string
    {
        return SortableCollectionType::class;
    }

    public function getCallbackTransformer(): DataTransformerInterface
    {
        return new SortableCollectionTransformer($this->em, $this->entity);
    }

    public function getEventSubscriber(): EventSubscriberInterface
    {
        return new JsonFormListener();
    }

    function render(CmsContent $content)
    {
        $rp     = $this->em->getRepository($this->entity);
        $values = json_decode($content->getValue(), true);

        $content = [];

        if (!empty($values)) {
            foreach ($values as $item) {
                $entity    = $rp->find($item['entity']);
                $content[] = $entity;
            }
        }

        return $content;
    }

}
