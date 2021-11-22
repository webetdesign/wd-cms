<?php


namespace WebEtDesign\CmsBundle\Services\CustomContents;


use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Twig\Environment;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\EventListener\JsonFormListener;
use WebEtDesign\CmsBundle\Form\CustomContents\SharedBlockContentType;
use WebEtDesign\CmsBundle\Form\CustomContents\SortableEntityType;
use WebEtDesign\CmsBundle\Form\CustomContents\Transformer\SharedBlockContentTransformer;
use WebEtDesign\CmsBundle\Form\CustomContents\Transformer\SortableCollectionTransformer;
use WebEtDesign\CmsBundle\Form\SortableCollectionType;
use WebEtDesign\CmsBundle\Services\AbstractCustomContent;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class SharedBlockCollectionContent extends AbstractCustomContent
{

    const NAME = 'SHARED_BLOCK_COLLECTION';

    private EntityManagerInterface $em;
    private TemplateProvider       $sharedBlockProvider;
    private Environment            $twig;

    public function __construct(
        EntityManagerInterface $em,
        TemplateProvider $sharedBlockProvider,
        Environment $twig
    ) {
        $this->em                  = $em;
        $this->sharedBlockProvider = $sharedBlockProvider;
        $this->twig                = $twig;
    }

    function getFormType(): string
    {
        return SortableCollectionType::class;
    }

    function getFormOptions(): array
    {
        return [
            'entry_type'    => SortableEntityType::class,
            'entry_options' => [
                'entity_class' => CmsSharedBlock::class
            ],
            'allow_add'     => true,
            'allow_delete'  => true
        ];
    }

    function getCallbackTransformer(): DataTransformerInterface
    {
        return new SortableCollectionTransformer($this->em, CmsSharedBlock::class);
    }

    public function getEventSubscriber(): EventSubscriberInterface
    {
        return new JsonFormListener();
    }

    function render(CmsContent $content): ?string
    {
        switch (true) {
            case $content->getPage() !== null:
                $parent = $content->getPage();
                break;
            case $content->getSharedBlockParent() !== null:
                $parent = $content->getSharedBlockParent();
                break;
            case $content->getDeclination() !== null:
                $parent = $content->getDeclination();
                break;
            default:
                $parent = null;
                break;
        }

        $blocks = array_map(fn($item) => $item->entity,
            $this->getCallbackTransformer()->transform($content->getValue()));

        try {
            $html = "";
            foreach ($blocks as $block) {
                $template = $this->sharedBlockProvider->getConfigurationFor($block->getTemplate())['template'];
                $html     .= $this->twig->render($template, [
                    'block'  => $block,
                    'parent' => $parent,
                ]);
            }

            return $html;
        } catch (Exception $e) {
            return null;
        }

        return null;
    }
}
