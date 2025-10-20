<?php

use App\Support\Debug\DebugTimeline;

if (!function_exists('debug_event')) {
    function debug_event(string $category, string $message, array $context = []): void
    {
        if (!app()->bound(DebugTimeline::class)) {
            return;
        }

        app(DebugTimeline::class)->add($category, $message, $context);
    }
}
