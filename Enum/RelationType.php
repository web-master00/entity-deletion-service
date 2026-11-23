<?php

declare(strict_types=1);

namespace Shared\Deletion\Enum;

enum RelationType: string
{
    case BLOCKING = 'blocking';     // presence of the relation blocks deletion of the current entity
    case REFERENCE = 'reference';   // plain relation (for cascade parent deletion); does not block the current entity
}
