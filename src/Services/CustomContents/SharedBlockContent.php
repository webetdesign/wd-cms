<?php


namespace WebEtDesign\CmsBundle\Services\CustomContents;


use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Form\DataTransformerInterface;
use Twig\Environment;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Form\CustomContents\SharedBlockContentType;
use WebEtDesign\CmsBundle\Form\CustomContents\Transformer\SharedBlockContentTransformer;
use WebEtDesign\CmsBundle\Services\AbstractCustomContent;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class SharedBlockContent extends AbstractCustomContent
{

    const NAME = 'SHARED_BLOCK';

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
        return SharedBlockContentType::class;
    }

    function getFormOptions(): array
    {
        return [];
    }

    function getCallbackTransformer(): DataTransformerInterface
    {
        return new SharedBlockContentTransformer($this->em);
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

        $block = $this->getCallbackTransformer()->transform($content->getValue())['block'] ?? null;

        if ($block) {
            try {
                $template = $this->sharedBlockProvider->getConfigurationFor($block->getTemplate())['template'];

                return $this->twig->render($template, [
                    'block' => $block,
                    'parent' => $parent,
                ]);
            } catch (Exception $e) {
                dump($e);
                return null;
            }
        }

        return null;
    }
}
