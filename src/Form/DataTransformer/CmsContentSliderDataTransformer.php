<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 27/02/2019
 * Time: 17:18
 */
namespace WebEtDesign\CmsBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use WebEtDesign\CmsBundle\Entity\CmsContentSlider;

class CmsContentSliderDataTransformer implements DataTransformerInterface
{

    private $entityManager;

    public function __construct(ModelManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  CmsContentSlider|null $cmsContentSlider
     * @return string
     */
    public function transform($cmsContentSlider)
    {
        if (null === $cmsContentSlider) {
            return '';
        }

        return $cmsContentSlider->getId();
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $cmsContentSliderNumber
     * @return CmsContentSlider|null
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($cmsContentSliderNumber)
    {
        // no issue number? It's optional, so that's ok
        if (!$cmsContentSliderNumber) {
            return;
        }

        $cmsContentSlider = $this->entityManager
            ->getRepository(CmsContentSlider::class)
            // query for the issue with this id
            ->find($cmsContentSliderNumber)
        ;

        if (null === $cmsContentSlider) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $cmsContentSliderNumber
            ));
        }

        return $cmsContentSlider;
    }
}