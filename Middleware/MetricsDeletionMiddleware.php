<?php

declare(strict_types=1);

namespace Shared\Deletion\Middleware;

use Shared\Deletion\Middleware\Metrics\MetricsBackendInterface;
use Shared\Deletion\Middleware\Metrics\NullMetricsBackend;

final class MetricsDeletionMiddleware implements DeletionMiddlewareInterface
{
    private readonly MetricsBackendInterface $backend;

    /** @var float|null Nanosecond monotonic time at the start of the deletion operation */
    private ?float $operationStartTime = null;

    /** @var array<string, float> Map of parent-to-child FQCN key to start time in nanoseconds */
    private array $detachStartTimes = [];

    /** @var array<string, float> Map of child FQCN key to start time in nanoseconds */
    private array $deleteChildrenStartTimes = [];

    /** @var float|null Start time of the root deletion in nanoseconds */
    private ?float $deleteRootStartTime = null;

    public function __construct(?MetricsBackendInterface $backend = null)
    {
        $this->backend = $backend ?? new NullMetricsBackend();
    }

    public function supports(string $entityClass): bool
    {
        return true;
    }

    public function beforeDetachRelations(string $parentClass, string $childClass, array $childIds, array $relation, object $root): void
    {
        $this->ensureOperationStarted();

        $key = $this->getRelationKey($parentClass, $childClass);
        $this->detachStartTimes[$key] = hrtime(true);
    }

    public function afterDetachRelations(string $parentClass, string $childClass, array $childIds, array $relation, object $root): void
    {
        $key = $this->getRelationKey($parentClass, $childClass);
        if (isset($this->detachStartTimes[$key])) {
            $durationMs = $this->calculateDurationMs($this->detachStartTimes[$key]);
            unset($this->detachStartTimes[$key]);

            $this->backend->timing('deletion.detach.duration_ms', $durationMs, [
                'root_class' => $root::class,
                'parent_class' => $parentClass,
                'child_class' => $childClass,
            ]);
        }

        $count = count($childIds);
        if ($count > 0) {
            $this->backend->increment('deletion.detached_relations.count', $count, [
                'root_class' => $root::class,
                'parent_class' => $parentClass,
                'child_class' => $childClass,
            ]);
        }
    }

    public function beforeDeleteChildren(string $childClass, array $childIds, object $root): void
    {
        $this->ensureOperationStarted();

        $this->deleteChildrenStartTimes[$childClass] = hrtime(true);
    }

    public function afterDeleteChildren(string $childClass, array $childIds, object $root): void
    {
        if (isset($this->deleteChildrenStartTimes[$childClass])) {
            $durationMs = $this->calculateDurationMs($this->deleteChildrenStartTimes[$childClass]);
            unset($this->deleteChildrenStartTimes[$childClass]);

            $this->backend->timing('deletion.delete_children.duration_ms', $durationMs, [
                'root_class' => $root::class,
                'child_class' => $childClass,
            ]);
        }

        $count = count($childIds);
        if ($count > 0) {
            $this->backend->increment('deletion.deleted_children.count', $count, [
                'root_class' => $root::class,
                'child_class' => $childClass,
            ]);
        }
    }

    public function beforeDeleteRoot(object $root): void
    {
        $this->ensureOperationStarted();

        $this->deleteRootStartTime = hrtime(true);
    }

    public function afterDeleteRoot(object $root): void
    {
        if ($this->deleteRootStartTime !== null) {
            $durationMs = $this->calculateDurationMs($this->deleteRootStartTime);
            $this->deleteRootStartTime = null;

            $this->backend->timing('deletion.delete_root.duration_ms', $durationMs, [
                'root_class' => $root::class,
            ]);
        }

        if ($this->operationStartTime !== null) {
            $totalDurationMs = $this->calculateDurationMs($this->operationStartTime);
            $this->operationStartTime = null;

            $this->backend->timing('deletion.total.duration_ms', $totalDurationMs, [
                'root_class' => $root::class,
            ]);

            $this->backend->increment('deletion.total.count', 1, [
                'root_class' => $root::class,
                'status' => 'success',
            ]);
        }
    }

    private function ensureOperationStarted(): void
    {
        if ($this->operationStartTime === null) {
            $this->operationStartTime = hrtime(true);
        }
    }

    private function getRelationKey(string $parentClass, string $childClass): string
    {
        return sprintf('%s::%s', $parentClass, $childClass);
    }

    private function calculateDurationMs(float $startTimeNs): float
    {
        $endTimeNs = hrtime(true);
        return ($endTimeNs - $startTimeNs) / 1000000.0;
    }
}
