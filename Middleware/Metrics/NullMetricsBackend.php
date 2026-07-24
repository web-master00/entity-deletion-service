<?php

declare(strict_types=1);

namespace Shared\Deletion\Middleware\Metrics;

final class NullMetricsBackend implements MetricsBackendInterface
{
    public function increment(string $name, int $value = 1, array $tags = []): void
    {
        // No-op
    }

    public function timing(string $name, float $durationMs, array $tags = []): void
    {
        // No-op
    }

    public function gauge(string $name, float $value, array $tags = []): void
    {
        // No-op
    }
}
