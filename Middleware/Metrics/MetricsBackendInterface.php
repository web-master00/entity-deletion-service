<?php

declare(strict_types=1);

namespace Shared\Deletion\Middleware\Metrics;

interface MetricsBackendInterface
{
    /**
     * Increment a counter metric.
     *
     * @param array<string, string> $tags
     */
    public function increment(string $name, int $value = 1, array $tags = []): void;

    /**
     * Record a duration/timing metric in milliseconds.
     *
     * @param array<string, string> $tags
     */
    public function timing(string $name, float $durationMs, array $tags = []): void;

    /**
     * Record a gauge metric.
     *
     * @param array<string, string> $tags
     */
    public function gauge(string $name, float $value, array $tags = []): void;
}
