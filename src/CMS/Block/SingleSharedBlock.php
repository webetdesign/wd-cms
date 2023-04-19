<?php

namespace WebEtDesign\CmsBundle\CMS\Block;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use WebEtDesign\CmsBundle\Attribute\AsCmsBlock;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;
use WebEtDesign\CmsBundle\Repository\CmsSharedBlockRepository;

#[AsCmsBlock(name: self::code)]
class SingleSharedBlock extends AbstractBlock
{

    public const code = 'SINGLE_SHARED_BLOCK';

    protected string $formType = EntityType::class;

    protected ?string $template = '@WebEtDesignCms/block/single_shared_block.html.twig';

    public function __construct(protected CmsSharedBlockRepository $sharedBlockRepository) { }

    public function getFormOptions(): array
    {
        return array_replace([
            'label'         => 'Bloc',
            'class'         => CmsSharedBlock::class,
            'query_builder' => $this->sharedBlockRepository->getBuilderByCollections($this->getSettings()['collections'] ?? null),
            'group_by'      => function ($choice, $key, $value) {
                return $choice->getSite()->__toString();
            },
        ], $this->formOptions);
    }
}
