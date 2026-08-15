<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Repository;

use Siganushka\GenericBundle\Repository\GenericEntityRepository;
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
}
