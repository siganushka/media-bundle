<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Siganushka\GenericBundle\Repository\GenericEntityRepository;
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
}
