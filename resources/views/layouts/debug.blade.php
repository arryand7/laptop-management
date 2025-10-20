@if(isset($debugTimeline) && $debugTimeline->enabled() && count($debugTimeline->all()))
    <div class="fixed bottom-4 right-4 z-40 w-96 text-sm">
        <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-lg">
            <button type="button" data-debug-toggle class="flex w-full items-center justify-between bg-slate-900 px-4 py-2 text-left font-semibold text-slate-100">
                <span>Debug Timeline ({{ count($debugTimeline->all()) }})</span>
                <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
            </button>
            <div data-debug-body class="hidden max-h-80 overflow-y-auto divide-y divide-slate-200">
                @foreach($debugTimeline->all() as $event)
                    <div class="px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ $event['time']->format('H:i:s') }} · {{ $event['category'] }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $event['message'] }}</p>
                        @if(!empty($event['context']))
                            <dl class="mt-2 space-y-1">
                                @foreach($event['context'] as $key => $value)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-xs text-slate-500">{{ $key }}</dt>
                                        <dd class="text-xs font-mono text-slate-600">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-debug-toggle]');
            const body = document.querySelector('[data-debug-body]');
            if (!toggle || !body) return;
            toggle.addEventListener('click', () => {
                body.classList.toggle('hidden');
            });
        });
    </script>
@endif
