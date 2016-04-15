<?php

namespace LOCKSSOMatic\CrudBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * LOCKSS archival unit.
 *
 * @ORM\Table(name="aus")
 * @ORM\Entity(repositoryClass="AuRepository")
 */
class Au implements GetPlnInterface
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
     * True if this AU is managed by LOCKSSOMatic. Defaults to false.
     *
     * @var boolean
     * @ORM\Column(name="managed", type="boolean", nullable=false)
     */
    private $managed;

    /**
     * The AU ID, as constructed by LOCKSS strange rules.
     * @var string
     *
     * @ORM\Column(name="auid", type="string", length=512, nullable=true)
     */
    private $auid;

    /**
     * LOCKSSOMatic comment for this au. Its specific to LOCKSSOMatic.
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=512, nullable=true)
     */
    private $comment;

    /**
     * The PLN for this AU.
     *
     * @var Pln
     *
     * @ORM\ManyToOne(targetEntity="Pln", inversedBy="aus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pln_id", referencedColumnName="id")
     * })
     */
    private $pln;

    /**
     * @var ContentProvider
     *
     * @ORM\ManyToOne(targetEntity="ContentProvider", inversedBy="aus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contentprovider_id", referencedColumnName="id")
     * })
     */
    private $contentProvider;

    /**
     * LOCKSS AUs are generated by LOCKSS plugins. This is the plugin that
     * generated this AU.
     *
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="aus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="plugin_id", referencedColumnName="id")
     * })
     */
    private $plugin;

    /**
     * Hierarchial collection of properties for the AU.
     *
     * @ORM\OneToMany(targetEntity="AuProperty", mappedBy="au")
     * @var AuProperties[]
     */
    private $auProperties;

    /**
     * Timestamped list of AU status records.
     *
     * @ORM\OneToMany(targetEntity="AuStatus", mappedBy="au")
     * @var AuStatus[]
     */
    private $auStatus;

    /**
     * List of all content deposited to the AU. This is a LOCKSSOMatic-specific
     * field.
     *
     * @ORM\OneToMany(targetEntity="Content", mappedBy="au")
     * @var Content[]
     */
    private $content;


    public function __construct()
    {
        $this->managed = false;
        $this->auProperties = new ArrayCollection();
        $this->auStatus = new ArrayCollection();
        $this->content = new ArrayCollection();
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
     * Set managed
     *
     * @param boolean $managed
     * @return Au
     */
    public function setManaged($managed)
    {
        $this->managed = $managed;

        return $this;
    }

    /**
     * Get managed
     *
     * @return boolean
     */
    public function getManaged()
    {
        return $this->managed;
    }

    /**
     * Set auid
     *
     * @param string $auid
     * @return Au
     */
    public function setAuid($auid)
    {
        $this->auid = $auid;

        return $this;
    }

    /**
     * Get auid. Will attempt to generate the auid if necessary.
     *
     * @return string
     */
    public function getAuid()
    {
        return $this->auid;
    }

    /**
     * Get a list of the top-level plugin properties.
     *
     * @return PluginProperty[]
     */
    public function getRootPluginProperties()
    {
        $properties = array();
        foreach ($this->auProperties as $p) {
            if ($p->hasParent()) {
                continue;
            }
            $properties[] = $p;
        }
        return $properties;
    }

    /**
     * Get the named AU property value, optionally %-encoded.
     *
     * @param string $name
     * @param bool $encoded
     * @return string
     */
    public function getAuPropertyValue($name, $encoded = false)
    {
        $property = $this->getAuProperty($name);
        $value = '';
        foreach($property->getChildren() as $prop) {
            if($prop->getPropertyKey() === 'value') {
                $value = $prop->getPropertyValue();
            }
        }
        if ($encoded === false) {
            return $value;
        }
        $callback = function ($matches) {
            $char = ord($matches[0]);
            return '%' . strtoupper(sprintf("%02x", $char));
        };
        return preg_replace_callback('/[^-_*a-zA-Z0-9]/', $callback, $value);
    }

    /**
     * Get the named AU property.
     *
     * @param string $name
     * @param bool $encoded
     * @return AuProperty
     */
    public function getAuProperty($name)
    {
        foreach ($this->getAuProperties() as $prop) {
            if ($prop->getPropertyKey() === 'key' && $prop->getPropertyValue() === $name) {
                return $prop->getParent();
            }
        }
        return null;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Au
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set pln
     *
     * @param Pln $pln
     * @return Au
     */
    public function setPln(Pln $pln = null)
    {
        $this->pln = $pln;
        $pln->addAus($this);

        return $this;
    }

    /**
     * Get pln
     *
     * @return Pln
     */
    public function getPln()
    {
        return $this->pln;
    }

    /**
     * Set contentprovider
     *
     * @param ContentProvider $contentprovider
     * @return Au
     */
    public function setContentprovider(ContentProvider $contentprovider = null)
    {
        $this->contentProvider = $contentprovider;
        $contentprovider->addAus($this);

        return $this;
    }

    /**
     * Get contentprovider
     *
     * @return ContentProvider
     */
    public function getContentprovider()
    {
        return $this->contentProvider;
    }

    /**
     * Set plugin
     *
     * @param Plugin $plugin
     * @return Au
     */
    public function setPlugin(Plugin $plugin = null)
    {
        $this->plugin = $plugin;
        $plugin->addAus($this);

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
     * Add auProperties
     *
     * @param AuProperty $auProperties
     * @return Au
     */
    public function addAuProperty(AuProperty $auProperties)
    {
        $this->auProperties[] = $auProperties;

        return $this;
    }

    /**
     * Remove auProperties
     *
     * @param AuProperty $auProperties
     */
    public function removeAuProperty(AuProperty $auProperties)
    {
        $this->auProperties->removeElement($auProperties);
    }

    /**
     * Get auProperties
     *
     * @return AuProperty[]
     */
    public function getAuProperties()
    {
        return $this->auProperties;
    }

    /**
     * Add auStatus
     *
     * @param AuStatus $auStatus
     * @return Au
     */
    public function addAuStatus(AuStatus $auStatus)
    {
        $this->auStatus[] = $auStatus;

        return $this;
    }

    /**
     * Remove auStatus
     *
     * @param AuStatus $auStatus
     */
    public function removeAuStatus(AuStatus $auStatus)
    {
        $this->auStatus->removeElement($auStatus);
    }

    /**
     * Get auStatus
     *
     * @return Collection
     */
    public function getAuStatus()
    {
        return $this->auStatus;
    }

    /**
     * Add content
     *
     * @param Content $content
     * @return Au
     */
    public function addContent(Content $content)
    {
        $this->content[] = $content;

        return $this;
    }

    /**
     * Remove content
     *
     * @param Content $content
     */
    public function removeContent(Content $content)
    {
        $this->content->removeElement($content);
    }

    /**
     * Get content
     *
     * @return Collection|Content[]
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the total size of the AU by adding the size of the
     * content items. Returns size in kB (1,000 bytes).
     *
     * @return int
     */
    public function getContentSize()
    {
        $size = 0;
        foreach ($this->getContent() as $content) {
            $size += $content->getSize();
        }
        return $size;
    }
	
	public function status() {
		return $this->auStatus->last()->getAuStatus();
	}

    public function __toString()
    {
        return "AU #" . $this->id;
    }
}
