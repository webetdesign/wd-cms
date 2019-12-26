<?php


namespace WebEtDesign\CmsBundle\EventListener;


use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsContentTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;

class SharedBlockListener
{
    protected $configCustomContent;
    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * SharedBlockListener constructor.
     * @param $configCustomContent
     * @param ContainerInterface $container
     */
    public function __construct(
        $configCustomContent,
        ContainerInterface $container
    ) {
        $this->configCustomContent = $configCustomContent;
        $this->container           = $container;
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        $sharedBlock = $event->getObject();

        if (!$sharedBlock instanceof CmsSharedBlock) {
            return;
        }

        $str = '';

        /** @var CmsContent $content */
        foreach ($sharedBlock->getContents() as $content) {
            if (in_array($content->getType(), [
                CmsContentTypeEnum::TEXT,
                CmsContentTypeEnum::TEXTAREA,
                CmsContentTypeEnum::WYSYWYG,
            ])) {
                $str .= $content->getValue() . ' ';
            } elseif (in_array($content->getType(), array_keys($this->configCustomContent))) {
                $contentService = $this->container->get($this->configCustomContent[$content->getType()]['service']);

                if (method_exists($contentService, 'getIndexableData')) {
                    $str .= $contentService->getIndexableData($content) . " ";
                }
            }
        }


        $sharedBlock->indexedContent = $str;
    }

}
