<x-layouts.admin title="Dashboard" heading="Dashboard">
    <section class="adm-cards" aria-label="Overview">
        <div class="adm-card">
            <div class="adm-card-label">Total users</div>
            <div class="adm-card-value">{{ number_format($counts['total']) }}</div>
        </div>
        <div class="adm-card">
            <div class="adm-card-label">Staff accounts</div>
            <div class="adm-card-value">{{ number_format($counts['staff']) }}</div>
        </div>
        <div class="adm-card">
            <div class="adm-card-label">Under sanction</div>
            <div class="adm-card-value">{{ number_format($counts['sanctioned']) }}</div>
        </div>
    </section>

    <section class="adm-panel" aria-label="Recent audit activity">
        <div class="adm-panel-head">
            <h2>Recent activity</h2>
            <a class="adm-btn adm-btn-ghost" href="{{ route('admin.logs.index') }}" style="margin-left:auto">View audit log</a>
        </div>
        @forelse($recent as $entry)
            @if($loop->first)<div class="adm-table-wrap"><table class="adm-table"><tbody>@endif
            <tr>
                <td style="width:1%;white-space:nowrap">{{ $entry->createdAt->diffForHumans() }}</td>
                <td>{{ $entry->actionLabel }}</td>
                <td>{{ $entry->actorUsername ?? 'system' }}</td>
                <td>
                    @if($entry->auditableUsername)
                        <a href="{{ route('admin.users.show', $entry->auditableUsername) }}">{{ $entry->auditableUsername }}</a>
                    @else
                        <span class="adm-mono">–</span>
                    @endif
                </td>
            </tr>
            @if($loop->last)</tbody></table></div>@endif
        @empty
            <div class="adm-empty">
                <h3>Nothing logged yet</h3>
                <p>Privileged actions will appear here as staff use the tools.</p>
            </div>
        @endforelse
    </section>
</x-layouts.admin>
