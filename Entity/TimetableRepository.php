<?php

namespace CanalTP\MttBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * TimetableRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TimetableRepository extends EntityRepository
{
    /*
     * init timetable object, if not found in db create an entity
     */
    public function getTimetableByRouteExternalId($externalRouteId)
    {
        $timetable = $this->findOneByExternalRouteId($externalRouteId);
        // not found then insert it
        if (empty($timetable)) {
            $timetable = new Timetable();
            $timetable->setExternalRouteId($externalRouteId);

            $this->getEntityManager()->persist($timetable);
            $this->getEntityManager()->flush();
        }

        return $timetable;
    }
    
    /*
     * Return blocks defined for this timetable on route level
     */
    public function getBlocks($timetable)
    {
        $result = $this->getEntityManager()->getRepository('CanalTPMttBundle:Block')->findBy(
            array(
                'stopPoint' => null,
                'timetable' => $timetable->getId()
            )
        );
        return $result;
    }
}