<?php

/**
 * Description of Network
 *
 * @author vdegroote
 */
namespace CanalTP\MttBundle\Services;

use Doctrine\Common\Persistence\ObjectManager;
use CanalTP\MttBundle\Entity\Frequency;

class FrequencyManager
{
    private $om = null;
    
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }
    public function getByBlockId($blockId)
    {
        return $this->om->getRepository('CanalTPMttBundle:Frequency')->findByBlock($blockId);
    }
    
    public function getEntity($blockId)
    {
        // $builder = new FormBuilder($this->repo);
        // $builder->setMethod('POST')
        $frequency= new Frequency();
        $frequency->setBlock($blockId);
        
        return $frequency;
    }
}