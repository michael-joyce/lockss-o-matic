<?php

namespace LOCKSSOMatic\CrudBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PluginProperty
 *
 * @ORM\Table(name="plugin_properties", indexes={@ORM\Index(name="IDX_28F93FBCEC46F62F", columns={"plugin_id"}), @ORM\Index(name="IDX_28F93FBC727ACA70", columns={"parent_id"})})
 * @ORM\Entity
 */
class PluginProperty
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="property_key", type="string", length=255, nullable=false)
     */
    private $propertyKey;

    /**
     * @var string
     *
     * @ORM\Column(name="property_value", type="text", nullable=true)
     */
    private $propertyValue;

    /**
     * @var PluginProperty
     *
     * @ORM\ManyToOne(targetEntity="PluginProperty", inversedBy="pluginProperties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="plugin_id", referencedColumnName="id")
     * })
     */
    private $plugin;

    /**
     * @ORM\OneToMany(targetEntity="PluginProperty", mappedBy="parent");
     * @var ArrayCollection
     */
    private $children;

    public function __construct() {
        $this->children = new ArrayCollection();
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
     * Set propertyKey
     *
     * @param string $propertyKey
     * @return PluginProperty
     */
    public function setPropertyKey($propertyKey)
    {
        $this->propertyKey = $propertyKey;

        return $this;
    }

    /**
     * Get propertyKey
     *
     * @return string 
     */
    public function getPropertyKey()
    {
        return $this->propertyKey;
    }

    /**
     * Set propertyValue
     *
     * @param string $propertyValue
     * @return PluginProperty
     */
    public function setPropertyValue($propertyValue)
    {
        $this->propertyValue = $propertyValue;

        return $this;
    }

    /**
     * Get propertyValue
     *
     * @return string 
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }

    /**
     * Set parent
     *
     * @param PluginProperty $parent
     * @return PluginProperty
     */
    public function setParent(PluginProperty $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return PluginProperty
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function hasParent() {
        return $this->parent !== null;
    }

    /**
     * Set plugin
     *
     * @param Plugin $plugin
     * @return PluginProperty
     */
    public function setPlugin(Plugin $plugin = null)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Get plugin
     *
     * @return Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Add children
     *
     * @param PluginProperty $children
     * @return PluginProperty
     */
    public function addChild(PluginProperty $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param PluginProperty $children
     */
    public function removeChild(PluginProperty $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function hasChildren() {
        return count($this->children) > 0;
    }
}