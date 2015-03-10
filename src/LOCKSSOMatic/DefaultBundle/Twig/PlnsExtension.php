<?php

namespace LOCKSSOMatic\DefaultBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class PlnsExtension extends \Twig_Extension {
    
    /**
     * @var Doctrine
     */
    private $doctrine;

    public function __construct(Doctrine $doctrine = null)
    {
        $this->doctrine = $doctrine;
    }

    public function getFunctions()
    {
        return array(
            'plnsList' => new \Twig_SimpleFunction('plnsList', array($this, 'plnsList')),
        );
    }
    
    public function plnsList() {
        $em = $this->doctrine->getManager();
        return $em->getRepository('LOCKSSOMaticCRUDBundle:Plns')->findAll();
    }
    
     public function getName()
    {
        return 'lom_plnsextension';
    }

}