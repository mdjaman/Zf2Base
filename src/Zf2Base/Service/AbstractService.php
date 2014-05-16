<?php

/**
* Class AbstractService
*
* @author Jhon Mike Soares <https://github.com/jhonmike>
* @version 1.0
*
* Dependencia Doctrine (https://github.com/doctrine/DoctrineORMModule.git)
*/

namespace Zf2Base\Service;

use Doctrine\ORM\EntityManager;
use Zend\Stdlib\Hydrator;

abstract class AbstractService
{
    /**
     *
     * @var EntityManager
     */
    protected $em;

    protected $entity;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function persist(array $data, $id = null)
    {
        if ($id) {
            $entity = $this->em->getReference($this->entity, $id);

            $hydrator = new Hydrator\ClassMethods();
            $hydrator->hydrate($data, $entity);
        } else
            $entity = new $this->entity($data);

        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    public function delete($id)
    {
        $entity = $this->em->getReference($this->entity, $id);
        if($entity)
        {
            $this->em->remove($entity);
            $this->em->flush();
            return $id;
        }
    }
}
