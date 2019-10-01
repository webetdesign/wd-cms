<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;



/**
 * @ORM\Table(name="cms__content_has_shared_block")
 */
class CmsContentHasSharedBlock
{
    /**
     * @ORM\Id
     */
    private $content;

    /**
     * @ORM\Id
     */
    private $sharedBlock;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, length=255)
     * @Gedmo\Sortable(groups={"content"})
     *
     */
    private $position;

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getSharedBlock()
    {
        return $this->sharedBlock;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $content
     * @return CmsContentHasSharedBlock
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param mixed $sharedBlock
     * @return CmsContentHasSharedBlock
     */
    public function setSharedBlock($sharedBlock)
    {
        $this->sharedBlock = $sharedBlock;
        return $this;
    }

    /**
     * @param mixed $position
     * @return CmsContentHasSharedBlock
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

}
