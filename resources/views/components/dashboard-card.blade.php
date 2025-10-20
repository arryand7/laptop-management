@props([
    'title' => 'Statistik',
    'value' => '0',
    'icon' => 'chart',
    'accent' => 'blue',
])

@php
    $colors = [
        'blue' => ['bg' => 'bg-blue-500/10', 'text' => 'text-blue-600'],
        'violet' => ['bg' => 'bg-violet-500/10', 'text' => 'text-violet-600'],
        'emerald' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-600'],
        'rose' => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-600'],
        'amber' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-600'],
        'slate' => ['bg' => 'bg-slate-500/10', 'text' => 'text-slate-700'],
    ][$accent] ?? ['bg' => 'bg-slate-500/10', 'text' => 'text-slate-700'];

    $icons = [
        'calendar' => 'M5.25 3a.75.75 0 01.75.75V5h7V3.75a.75.75 0 011.5 0V5h.75A2.75 2.75 0 0118 7.75v8.5A2.75 2.75 0 0115.25 19H4.75A2.75 2.75 0 012 16.25v-8.5A2.75 2.75 0 014.75 5h.75V3.75A.75.75 0 015.25 3zm11.25 6H3.5v7.25c0 .69.56 1.25 1.25 1.25h10.75c.69 0 1.25-.56 1.25-1.25V9z',
        'chart' => 'M4 3.75A.75.75 0 014.75 3h10.5a.75.75 0 01.75.75V18a.75.75 0 01-.75.75H4.75A.75.75 0 014 18V3.75zm4 11a.75.75 0 001.5 0v-6a.75.75 0 10-1.5 0v6zm3 0a.75.75 0 001.5 0v-9a.75.75 0 10-1.5 0v9zm3 0a.75.75 0 001.5 0v-4a.75.75 0 10-1.5 0v4z',
        'spark' => 'M11.25 3a.75.75 0 10-1.5 0v2.25H7.5a.75.75 0 100 1.5h2.25V9a.75.75 0 101.5 0V6.75H13.5a.75.75 0 100-1.5h-2.25V3zM6 11.25a.75.75 0 01.75-.75h2.5a.75.75 0 010 1.5H7.5v2.25a.75.75 0 01-1.5 0v-3zm7.25-.75h2.5a.75.75 0 010 1.5H14.5v2.25a.75.75 0 01-1.5 0v-3a.75.75 0 01.75-.75z',
        'laptop' => 'M4 5.75A1.75 1.75 0 015.75 4h8.5A1.75 1.75 0 0116 5.75v6.5A1.75 1.75 0 0114.25 14h-8.5A1.75 1.75 0 014 12.25v-6.5zM3 15.5h14a.5.5 0 010 1h-1.25A2.75 2.75 0 0113 19H7a2.75 2.75 0 01-2.75-2.5H3a.5.5 0 010-1z',
        'alert' => 'M10.477 3.17a.75.75 0 011.046.276l6.75 11.756A.75.75 0 0117.75 16.5H2.25a.75.75 0 01-.523-1.298l6.75-11.756a.75.75 0 011.046-.276zM11 13.5a1 1 0 10-2 0 1 1 0 002 0zm-.125-6.375a.875.875 0 10-1.75 0v3.5a.875.875 0 101.75 0v-3.5z',
        'shield' => 'M10 2.25a.75.75 0 01.38.102l6.25 3.5a.75.75 0 01.37.648v4.75c0 3.557-2.454 6.873-6.507 7.982a.75.75 0 01-.386 0C6.054 17.123 3.6 13.807 3.6 10.25V6.5a.75.75 0 01.37-.648l6.25-3.5A.75.75 0 0110 2.25z',
    ];

    $iconPath = $icons[$icon] ?? $icons['chart'];
@endphp

<div {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md') }}>
    <div class="flex items-center justify-between">
        <div class="rounded-xl {{ $colors['bg'] }} p-3">
            <svg class="h-6 w-6 {{ $colors['text'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="{{ $iconPath }}" />
            </svg>
        </div>
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ strtoupper($title) }}</span>
    </div>
    <p class="mt-6 text-3xl font-semibold text-slate-900">{{ $value }}</p>
</div>
