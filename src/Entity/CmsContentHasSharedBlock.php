<?php

namespace WebEtDesign\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;


/**
 */
class CmsContentHasSharedBlock
{
    private $content;
    
    private $sharedBlock;
    
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
