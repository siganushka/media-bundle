<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Siganushka\GenericBundle\Repository\GenericEntityRepository;
use Siganushka\MediaBundle\Dto\MediaFilterDto;
use Siganushka\MediaBundle\Entity\Media;

/**
 * @template T of Media = Media
 *
 * @extends GenericEntityRepository<T>
 */
class MediaRepository extends GenericEntityRepository
{
    /**
     * @return T|null
     */
    public function findOneByHash(string $hash): ?Media
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function createQueryBuilderByFilter(string $alias, MediaFilterDto $dto): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilderWithOrderBy($alias);

        if ($dto->startAt) {
            $queryBuilder->andWhere(\sprintf('%s.createdAt >= :startAt', $alias))->setParameter('startAt', $dto->startAt);
        }

        if ($dto->endAt) {
            $queryBuilder->andWhere(\sprintf('%s.createdAt <= :endAt', $alias))->setParameter('endAt', $dto->endAt);
        }

        return $queryBuilder;
    }
}
