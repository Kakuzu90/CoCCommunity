<x-layouts.admin title="Disputes" heading="Ownership disputes">
    <section class="adm-panel">
        <form class="adm-filters" method="get" action="{{ route('admin.disputes.index') }}">
            <div class="adm-filter-select">
                <x-ui.select id="filter-status" name="status" label="Status" placeholder="Open queue"
                    :value="$status?->value ?? ''"
                    :options="collect(\App\Domain\PlayerAccounts\Enums\DisputeStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            </div>
            <div class="adm-filter-actions">
                <button type="submit" class="adm-btn">Apply</button>
                <a href="{{ route('admin.disputes.index') }}" class="adm-btn adm-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Filed</th>
                        <th>Tag</th>
                        <th>Claimant</th>
                        <th>Holder</th>
                        <th>Status</th>
                        <th>Respond by</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td style="white-space:nowrap">{{ $dispute->createdAt?->format('Y-m-d') }}</td>
                            <td class="adm-mono">#{{ $dispute->tagNormalized }}</td>
                            <td>{{ $dispute->claimantName ?? '–' }}</td>
                            <td>{{ $dispute->holderName ?? '–' }}</td>
                            <td>{{ $dispute->status->label() }}</td>
                            <td style="white-space:nowrap">{{ $dispute->holderRespondsBy?->format('Y-m-d') ?? '–' }}</td>
                            <td><a class="adm-btn" href="{{ route('admin.disputes.show', $dispute->ulid) }}">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="adm-empty">
                                <h3>Queue is clear</h3>
                                <p>No disputes are waiting for a decision.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="adm-pager">
            <span>{{ $disputes->firstItem() ?? 0 }}–{{ $disputes->lastItem() ?? 0 }} of {{ number_format($disputes->total()) }}</span>
            <span class="adm-pager-links">
                @if($disputes->onFirstPage())
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Prev</span>
                @else
                    <a class="adm-btn" href="{{ $disputes->previousPageUrl() }}" rel="prev">Prev</a>
                @endif
                @if($disputes->hasMorePages())
                    <a class="adm-btn" href="{{ $disputes->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Next</span>
                @endif
            </span>
        </div>
    </section>
</x-layouts.admin>
