<?php

namespace Marek\ArticlesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Article
 */
class Article
{
    /**
     * @var integer
     */
    private $id;

    /**
     *
     * @Assert\NotBlank(
     *      message = "To pole jest wymagane"
     * )
     * @Assert\Length(
     *      min = "2",
     *      max = "50",
     *      minMessage = "Tytuł musi mieć przynajmniej {{ limit }} znaki",
     *      maxMessage = "Tytuł musi mieć najwyżej {{ limit }} znaków"
     * )
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @var boolean
     */
    private $active;

    /**
     * @var integer
     */
    private $position;

    /*
    * @Assert\Valid()
    */
    private $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Article
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Article
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Article
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Article
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return Article
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function setCreatedOnValue()
    {
        $this->createdOn = new \DateTime;
    }
    /**
     * Get createdOn
     *
     * @return \DateTime 
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    public function getImages()
    {
        return $this->images;
    }

    /**
     * Add images
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $images
     * @return Article
     */
    //public function addImage(\Marek\ArticlesBundle\Entity\Image $images)
    public function addImage(UploadedFile $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $images
     */
    //public function removeImage(\Marek\ArticlesBundle\Entity\Image $images)
    public function removeImage(UploadedFile $images)
    {
        $this->images->removeElement($images);
    }
}
