# Deletion Service

Service for checking whether entities can be deleted and for listing dependent objects.

## Usage

### 1. Annotating dependencies

On a child entity, declare which entity it depends on:

```php
<?php

use Shared\Deletion\Attribute\RelationTo;
use Shared\Deletion\Enum\RelationType;
use Domain\Order\Common\Entity\OrderEntity;

#[RelationTo(entity: OrderEntity::class, field: 'order', type: RelationType::BLOCKING)]
final class OrderItemEntity
{
    // the order field must reference OrderEntity
    private OrderEntity $order;
    
    // OrderItemEntity cannot be deleted while linked to OrderEntity
    // OrderEntity can be deleted freely
}
```

### 2. Checking whether deletion is allowed

```php
<?php

use Shared\Deletion\DeletionService;

class SomeController
{
    public function __construct(
        private DeletionService $deletionService
    ) {}
    
    public function checkDelete(OrderEntity $order): Response
    {
        $result = $this->deletionService->canDelete($order);
        
        if (!$result->canDelete) {
            // Dependent entities are blocking deletion
            foreach ($result->dependents as $group) {
                echo "Found {$group->count} records of type {$group->childClass}";
                if ($group->hard) {
                    echo " (blocks deletion)";
                }
                echo "\n";
            }
        }
        
        return new JsonResponse([
            'canDelete' => $result->canDelete,
            'dependents' => $result->dependents
        ]);
    }
}
```

### 3. DependsOn attribute parameters

- `parent` - FQCN of the parent entity (required)
- `field` - field name on the child entity that references the parent (required)
- `hard` - if true, blocks parent deletion (default true)
- `joinTable` - intermediate table name for many-to-many relations (optional)
- `joinColumn` - column in the intermediate table that references the parent (optional)
- `inverseJoinColumn` - column in the intermediate table that references the child (optional)

### 4. Relations via intermediate tables (Many-to-Many)

For relations through an intermediate table, provide the extra parameters:

```php
<?php

use Shared\Deletion\Attribute\RelationTo;
use Shared\Deletion\Enum\RelationType;
use Domain\Advert\Common\Entity\AdvertEntity;

#[RelationTo(
    entity: AdvertEntity::class,
    field: 'id', // formally required field
    type: RelationType::BLOCKING,
    joinTable: 'advert_tag_relation', // intermediate table name
    joinColumn: 'advert_id',          // column referencing the parent
    inverseJoinColumn: 'advert_tag_id' // column referencing the current entity
)]
final class AdvertTagEntity
{
    // AdvertTagEntity cannot be deleted while links exist in advert_tag_relation
    // AdvertEntity can be deleted freely
}
```

**How it works:**

- When checking `AdvertTag` — the system checks for rows in `advert_tag_relation`
  where `advert_tag_id` = the tag ID. If any exist, the tag cannot be deleted
- When checking `Advert` — parent deletion is not blocked. Advert can be deleted freely

### 5. Multiple dependencies

An entity can depend on several parents:

```php
<?php

#[RelationTo(entity: OrderEntity::class, field: 'order', type: RelationType::BLOCKING)]
#[RelationTo(entity: ProductEntity::class, field: 'product', type: RelationType::REFERENCE)]
final class OrderItemEntity
{
    private OrderEntity $order;
    private ProductEntity $product;
    
    // ...
}
```

## Console command

To check dependencies from the command line:

```bash
# By short entity name
php bin/console deletion:check OrderEntity 123

# By full FQCN
php bin/console deletion:check "Domain\Order\Common\Entity\OrderEntity" 123
```

The command prints:

- Whether the entity can be deleted
- A list of all dependent objects with counts
- Dependency type (blocking or soft)
- IDs of the first 5 dependent records
