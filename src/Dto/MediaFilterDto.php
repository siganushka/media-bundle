<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Dto;

use Siganushka\GenericBundle\Dto\DateRangeDtoTrait;
use Siganushka\GenericBundle\Dto\PageQueryDtoTrait;

class MediaFilterDto
{
    use DateRangeDtoTrait;
    use PageQueryDtoTrait;
}
