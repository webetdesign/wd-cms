<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms__content_has_shared_block")
 */
class CmsContentHasSharedBlock
{
    /**
     * @Gedmo\SortableGroup
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsContent", inversedBy="sharedBlockList")
     * @ORM\JoinColumn(name="content_id", referencedColumnName="id")
     */
    private $content;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="WebEtDesign\CmsBundle\Entity\CmsSharedBlock", inversedBy="contentList"))
     * @ORM\JoinColumn(name="shared_block_id", referencedColumnName="id")
     */
    private $sharedBlock;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @Gedmo\SortablePosition
     *
     */
    private $position;

    public function __toString()
    {
        return (string) $this->content . ' ' . $this->sharedBlock;
    }


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
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param mixed $sharedBlock
     */
    public function setSharedBlock($sharedBlock)
    {
        $this->sharedBlock = $sharedBlock;
        return $this;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

}
