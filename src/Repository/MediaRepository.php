<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Siganushka\MediaBundle\Entity\Media;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @psalm-method list<Media>    findAll()
 * @psalm-method list<Media>    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function createQueryBuilderWithSorted(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->addOrderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
        ;
    }

    public function createNew(): Media
    {
        $ref = new \ReflectionClass($this->_entityName);

        return $ref->newInstance();
    }
}
