<?php

declare(strict_types=1);

namespace Shared\Deletion\Enum;

enum DeletionCascade: string
{
    case NONE = 'none';               // do not delete the child when deleting the parent
    case DELETE_CHILD = 'delete';     // delete the child together with the parent
    case DETACH_RELATIONS = 'detach'; // remove only relations (join table); the child itself is not deleted
}
