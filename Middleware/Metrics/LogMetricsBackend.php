<?php

declare(strict_types=1);

namespace Shared\Deletion\Middleware\Metrics;

use Psr\Log\LoggerInterface;

final class LogMetricsBackend implements MetricsBackendInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function increment(string $name, int $value = 1, array $tags = []): void
    {
        $this->logger->info(sprintf('Metric Count [%s]: %d', $name, $value), ['tags' => $tags]);
    }

    public function timing(string $name, float $durationMs, array $tags = []): void
    {
        $this->logger->info(sprintf('Metric Timing [%s]: %.2fms', $name, $durationMs), ['tags' => $tags]);
    }

    public function gauge(string $name, float $value, array $tags = []): void
    {
        $this->logger->info(sprintf('Metric Gauge [%s]: %.2f', $name, $value), ['tags' => $tags]);
    }
}
