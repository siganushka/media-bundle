<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Siganushka\GenericBundle\Repository\GenericEntityRepository;
use Siganushka\MediaBundle\Dto\MediaQueryDto;
use Siganushka\MediaBundle\Model\MediaInterface;

/**
 * @template T of MediaInterface = MediaInterface
 *
 * @extends GenericEntityRepository<T>
 */
class MediaRepository extends GenericEntityRepository
{
    /**
     * @return T|null
     */
    public function findOneByHash(string $hash): ?MediaInterface
    {
        return $this->findOneBy(compact('hash'));
    }

    public function createQueryBuilderFromDto(string $alias, MediaQueryDto $dto): QueryBuilder
    {
        $criteria = self::createCriteriaFromDto($dto);

        $qb = $this->createQueryBuilderWithOrderBy($alias);
        $qb->addCriteria($criteria);

        return $qb;
    }
}
