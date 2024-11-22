<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Siganushka\GenericBundle\Repository\GenericEntityRepository;
use Siganushka\MediaBundle\Entity\Media;

/**
 * @extends GenericEntityRepository<Media>
 */
class MediaRepository extends GenericEntityRepository
{
    public function findOneByHash(string $hash): ?Media
    {
        return $this->findOneBy(['hash' => $hash]);
    }
}
