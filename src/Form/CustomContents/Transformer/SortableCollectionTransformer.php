<?php


namespace WebEtDesign\CmsBundle\Form\CustomContents\Transformer;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use WebEtDesign\CmsBundle\Models\CustomContents\SortableEntity;

class SortableCollectionTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $entity;

    public function __construct(EntityManagerInterface $em, $entity)
    {
        $this->em     = $em;
        $this->entity = $entity;
    }

    /**
     *
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function transform($value)
    {
        $rp = $this->em->getRepository($this->entity);

        if ($value !== null && is_string($value)) {
            $value = json_decode($value, true);
        }

        $collection = [];
        if (is_array($value)) {
            foreach ($value as $data) {
                $sortableEntity           = new SortableEntity();
                $sortableEntity->entity   = $rp->find((int)$data['entity']);
                $sortableEntity->position = $data['position'];

                $collection[] = $sortableEntity;
            }
        }

        return $collection;
    }

    /**
     * @param mixed $value The value in the transformed representation
     *
     * @return mixed The value in the original representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function reverseTransform($value)
    {
        $data = [];
        if (is_array($value)) {
            foreach ($value as $sortableEntity) {
                if (method_exists($sortableEntity->entity, 'getId')) {
                    $sortableEntity->entity = $sortableEntity->entity->getId();
                    if ($sortableEntity->entity){
                        $data[] = $sortableEntity;
                    }
                }
            }

            $data = array_values($data);
            usort($data, function (SortableEntity $a, SortableEntity $b) {
                return $a->position < $b->position ? -1 : 1;
            });

        }else{
            $data = $value;
        }

        return json_encode($data, true);
    }
}
