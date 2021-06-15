<?php

namespace WebEtDesign\CmsBundle\Services\CustomContents;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Twig\Environment;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\EventListener\JsonFormListener;
use WebEtDesign\CmsBundle\Form\CustomContents\SortableEntityType;
use WebEtDesign\CmsBundle\Form\CustomContents\Transformer\SortableCollectionTransformer;
use WebEtDesign\CmsBundle\Form\SortableCollectionType;
use WebEtDesign\CmsBundle\Services\AbstractCustomContent;

class SortableCollection extends AbstractCustomContent
{

    const CMS_SORTABLE_COLLECTION = 'CMS_SORTABLE_COLLECTION';

    protected ?string $entity;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;

    /**
     * @var AdminInterface
     */
    protected $admin;
    /**
     * @var array
     */
    private array $link_parameters;
    /**
     * @var string|null
     */
    private ?string $template;
    /**
     * @var Environment
     */
    private Environment $twig;

    /**
     * @param EntityManagerInterface $em
     * @param Environment $twig
     * @param string|null $entity
     * @param bool $useModelListeType
     * @param array $link_parameters
     * @param string|null $template
     */
    public function __construct(
        EntityManagerInterface $em,
        Environment $twig,
        string $entity = null,
        bool $useModelListeType = false,
        array $link_parameters = [],
        string $template = null
    ) {
        $this->em              = $em;
        $this->entity          = $entity;
        $this->admin           = $useModelListeType;
        $this->link_parameters = $link_parameters;
        $this->template        = $template;
        $this->twig            = $twig;
    }

    function getFormOptions(): array
    {
        return [
            'entry_type'    => SortableEntityType::class,
            'entry_options' => [
                'entity_class'     => $this->entity,
                'useModelListType' => $this->admin,
                'link_parameters'  => $this->link_parameters,
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
        $values = $this->getCallbackTransformer()
            ->transform(json_decode($content->getValue(), true));


        if ($this->template) {
            return $this->twig->render($this->template,
                ['entities' => array_column($values, 'entity')]);
        }

        return array_column($values, 'entity');
    }

}
