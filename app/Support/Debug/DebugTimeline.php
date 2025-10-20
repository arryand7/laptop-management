<?php

namespace App\Support\Debug;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class DebugTimeline
{
    protected bool $enabled;

    protected array $events = [];

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function add(string $category, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->events[] = [
            'time' => Carbon::now(),
            'category' => $category,
            'message' => $message,
            'context' => Arr::map($context, fn ($value) => is_scalar($value) ? $value : json_encode($value)),
        ];
    }

    public function all(): array
    {
        return $this->events;
    }
}
