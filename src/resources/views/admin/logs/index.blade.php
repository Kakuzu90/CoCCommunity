<x-layouts.admin title="Audit log" heading="Audit log">
    <section class="adm-panel">
        <form class="adm-filters" method="get" action="{{ route('admin.logs.index') }}">
            <div class="adm-filter-select">
                <x-ui.select id="filter-action" name="action" label="Action" placeholder="All actions"
                    :value="$filters['action'] ?? ''"
                    :options="collect($actionOptions)->mapWithKeys(fn ($o) => [$o['value'] => $o['label']])->all()" />
            </div>
            <div class="adm-filter-select">
                <x-ui.input id="filter-actor" name="actor" label="Actor" placeholder="username" value="{{ $filters['actor'] }}" />
            </div>
            <div class="adm-filter-actions">
                <button type="submit" class="adm-btn">Apply</button>
                <a href="{{ route('admin.logs.index') }}" class="adm-btn adm-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Target</th>
                        <th>Change</th>
                        <th>Request</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td style="white-space:nowrap">{{ $entry->createdAt->format('Y-m-d H:i') }}</td>
                            <td>{{ $entry->actionLabel }}</td>
                            <td>{{ $entry->actorUsername ?? 'system' }}@if($entry->actorRole)<div class="adm-role">{{ $entry->actorRole }}</div>@endif</td>
                            <td>
                                @if($entry->auditableUsername)
                                    <a href="{{ route('admin.users.show', $entry->auditableUsername) }}">{{ $entry->auditableUsername }}</a>
                                @elseif($entry->auditableId)
                                    <span class="adm-mono">{{ $entry->auditableType }}#{{ $entry->auditableId }}</span>
                                @else
                                    –
                                @endif
                            </td>
                            <td>
                                @if($entry->before || $entry->after)
                                    <pre class="adm-diff">{{ collect($entry->before ?? [])->map(fn ($v, $k) => "- $k: $v")->implode("\n") }}
{{ collect($entry->after ?? [])->map(fn ($v, $k) => "+ $k: $v")->implode("\n") }}</pre>
                                @else
                                    <span class="adm-hint">–</span>
                                @endif
                            </td>
                            <td class="adm-mono">{{ $entry->requestId ? \Illuminate\Support\Str::limit($entry->requestId, 8, '') : '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="adm-empty">
                                <h3>No audit entries</h3>
                                <p>Privileged actions are recorded here for two years.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="adm-pager">
            <span>{{ $entries->firstItem() ?? 0 }}–{{ $entries->lastItem() ?? 0 }} of {{ number_format($entries->total()) }}</span>
            <span class="adm-pager-links">
                @if($entries->onFirstPage())
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Prev</span>
                @else
                    <a class="adm-btn" href="{{ $entries->previousPageUrl() }}" rel="prev">Prev</a>
                @endif
                @if($entries->hasMorePages())
                    <a class="adm-btn" href="{{ $entries->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Next</span>
                @endif
            </span>
        </div>
    </section>
</x-layouts.admin>
