<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Siganushka\GenericBundle\Repository\GenericEntityRepository;
use Siganushka\MediaBundle\Entity\Media;

/**
 * @extends GenericEntityRepository<Media>
 *
 * @method Media      createNew(...$args)
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends GenericEntityRepository
{
    public function findOneByHash(string $hash): ?Media
    {
        return $this->findOneBy(['hash' => $hash]);
    }
}
