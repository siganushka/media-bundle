<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Dto;

use Doctrine\Common\Collections\Expr\Comparison;
use Siganushka\GenericBundle\Attribute\QueryFilter;

class MediaQueryDto
{
    public function __construct(
        #[QueryFilter(expr: Comparison::CONTAINS)]
        public readonly ?string $name = null,
        #[QueryFilter]
        public readonly ?string $extension = null,
        #[QueryFilter]
        public readonly ?string $mime = null,
    ) {
    }
}
