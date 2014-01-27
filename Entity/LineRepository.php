<?php

namespace CanalTP\MethBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * LineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LineRepository extends EntityRepository
{
    /*
     * display a form to choose a layout for a given line or save this form and redirects
     */
    public function getTwigPath($line, $layoutConfig)
    {
        return $layoutConfig[$line->getLayout()]['twig'];
    }
}
