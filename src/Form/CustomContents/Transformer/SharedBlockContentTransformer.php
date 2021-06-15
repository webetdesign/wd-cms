<?php


namespace WebEtDesign\CmsBundle\Form\CustomContents\Transformer;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Entity\CmsSharedBlock;

class SharedBlockContentTransformer implements DataTransformerInterface
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function transform($value)
    {
        if ($value) {
            return [
                'block' => $this->em->find(CmsSharedBlock::class, $value),
            ];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        if (is_array($value) && isset($value['block']) && $value['block'] !== null) {
            return $value['block']->getId();
        }

        return null;
    }
}
