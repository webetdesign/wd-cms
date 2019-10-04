<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 20/02/2019
 * Time: 15:11
 */

namespace WebEtDesign\CmsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;



/**
 * @ORM\Entity(repositoryClass="WebEtDesign\CmsBundle\Repository\CmsContentSliderRepository")
 * @ORM\Table(name="cms__content_slider")
 */
class CmsContentSlider
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    private $url;

    /**
     */
    private $media;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    private $description;

    /**
     */
    private $content;

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param mixed $media
     */
    public function setMedia($media): void
    {
        $this->media = $media;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $contents
     */
    public function setContent($content)
    {
        $this->content = $content;
    }


}
