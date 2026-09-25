@php
    $sortLink = function (string $column) use ($filters) {
        $dir = ($filters->sort === $column && $filters->direction === 'asc') ? 'desc' : 'asc';
        return route('admin.users.index', array_merge(request()->query(), ['sort' => $column, 'dir' => $dir]));
    };
    $arrow = fn (string $column) => $filters->sort === $column ? ($filters->direction === 'asc' ? '↑' : '↓') : '';
@endphp

<x-layouts.admin title="Users" heading="Users">
    <section class="adm-panel">
        <form class="adm-filters" method="get" action="{{ route('admin.users.index') }}">
            <div class="adm-filter-select">
                <x-ui.input id="filter-q" name="q" type="search" label="Search" placeholder="username or email" value="{{ $filters->search }}" />
            </div>
            <div class="adm-filter-select">
                <x-ui.select id="filter-role" name="role" label="Role" placeholder="All roles"
                    :value="$filters->role" :searchable="false"
                    :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />
            </div>
            <div class="adm-filter-select">
                <x-ui.select id="filter-status" name="status" label="Status" placeholder="All statuses"
                    :value="$filters->status" :searchable="false"
                    :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            </div>
            <div class="adm-filter-actions">
                <button type="submit" class="adm-btn">Apply</button>
                <a href="{{ route('admin.users.index') }}" class="adm-btn adm-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th><a class="adm-th-sort" href="{{ $sortLink('username') }}">User {{ $arrow('username') }}</a></th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th><a class="adm-th-sort" href="{{ $sortLink('created_at') }}">Joined {{ $arrow('created_at') }}</a></th>
                        <th><a class="adm-th-sort" href="{{ $sortLink('last_login_at') }}">Last login {{ $arrow('last_login_at') }}</a></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $u->username) }}">{{ $u->username }}</a></td>
                            <td class="adm-mono">{{ $u->email }}</td>
                            <td><span class="adm-role">{{ $u->role->label() }}</span></td>
                            <td><span class="adm-status" data-status="{{ $u->status->value }}">{{ $u->status->label() }}</span></td>
                            <td>{{ $u->verifiedAccounts }}</td>
                            <td>{{ $u->createdAt->format('Y-m-d') }}</td>
                            <td>{{ $u->lastLoginAt?->diffForHumans() ?? 'never' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="adm-empty">
                                <h3>No users match</h3>
                                <p>Try a different search or clear the filters.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="adm-pager">
            <span>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }}</span>
            <span class="adm-pager-links">
                @if($users->onFirstPage())
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Prev</span>
                @else
                    <a class="adm-btn" href="{{ $users->previousPageUrl() }}" rel="prev">Prev</a>
                @endif
                @if($users->hasMorePages())
                    <a class="adm-btn" href="{{ $users->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="adm-btn adm-btn-ghost" aria-disabled="true">Next</span>
                @endif
            </span>
        </div>
    </section>
</x-layouts.admin>
