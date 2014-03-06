<?php

namespace LOCKSSOMatic\CRUDBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PluginProperties
 *
 * @ORM\Table(name="plugin_properties", @ORM\Index(name="plugins_id_idx", columns={"plugins_id"})})
 * @ORM\Entity
 */
class PluginProperties
{
    /**
    * Property required for many-to-one relationship with Plugins.
    * 
    * @ManyToOne(targetEntity="Plugins", mappedBy="pluginProperties")
    * @JoinColumn(name="plugins_id", referencedColumnName="id")
    */
    protected $plugin;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="plugins_id", type="integer", nullable=true)
     */
    private $pluginsId;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId;

    /**
     * @var string
     *
     * @ORM\Column(name="property_key", type="text", nullable=true)
     */
    private $propertyKey;

    /**
     * @var string
     *
     * @ORM\Column(name="property_value", type="text", nullable=true)
     */
    private $propertyValue;

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
     * Set pluginsId
     *
     * @param integer $pluginsId
     * @return PluginProperties
     */
    public function setPluginsId($pluginsId)
    {
        $this->pluginsId = $pluginsId;

        return $this;
    }

    /**
     * Get pluginsId
     *
     * @return integer 
     */
    public function getPluginsId()
    {
        return $this->pluginsId;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return PluginProperties
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set propertyKey
     *
     * @param string $propertyKey
     * @return PluginProperties
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
     * @return PluginProperties
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
     * Set plugin
     *
     * @param \LOCKSSOMatic\CRUDBundle\Entity\Plugins $plugin
     * @return PluginProperties
     */
    public function setPlugin(\LOCKSSOMatic\CRUDBundle\Entity\Plugins $plugin = null)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Get plugin
     *
     * @return \LOCKSSOMatic\CRUDBundle\Entity\Plugins 
     */
    public function getPlugin()
    {
        return $this->plugin;
    }
}
