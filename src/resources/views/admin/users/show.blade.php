@php
    $actor = auth()->user();
    $canAct = $actor->role->level() > $user->role->level();
    $timeBoxed = array_values(array_map(fn ($t) => $t->value, array_filter($sanctionTypes, fn ($t) => $t->isTimeBoxed())));
@endphp

<x-layouts.admin :title="$user->username" :heading="$user->username">
    <a class="adm-back" href="{{ route('admin.users.index') }}">
        <x-ui.icon name="arrow" size="16" class="adm-flip" />Back to users
    </a>

    <div class="adm-detail">
        <div style="display:flex;flex-direction:column;gap:var(--space-5)">
            <section class="adm-panel">
                <div class="adm-panel-head">
                    <h2>Account</h2>
                    <span class="adm-status" data-status="{{ $user->status->value }}" style="margin-left:auto">{{ $user->status->label() }}</span>
                </div>
                <div class="adm-panel-body">
                    <dl class="adm-dl">
                        <dt>Username</dt><dd>{{ $user->username }}</dd>
                        <dt>Email</dt><dd class="adm-mono">{{ $user->email }} @unless($user->emailVerified)<span class="adm-hint">(unverified)</span>@endunless</dd>
                        <dt>Role</dt><dd>{{ $user->role->label() }}</dd>
                        @if($user->statusReason)
                            <dt>Status reason</dt><dd>{{ $user->statusReason }}</dd>
                        @endif
                        @if($user->statusExpiresAt)
                            <dt>Status ends</dt><dd>{{ $user->statusExpiresAt->format('Y-m-d H:i') }} ({{ $user->statusExpiresAt->diffForHumans() }})</dd>
                        @endif
                        <dt>Verified accounts</dt><dd>{{ $user->verifiedAccounts }}</dd>
                        <dt>Bases published</dt><dd>{{ $user->basesPublished }}</dd>
                        <dt>Joined</dt><dd>{{ $user->createdAt->format('Y-m-d') }} ({{ $user->createdAt->diffForHumans() }})</dd>
                        <dt>Last login</dt><dd>{{ $user->lastLoginAt?->format('Y-m-d H:i') ?? 'never' }}</dd>
                        @if($user->deletionRequestedAt)
                            <dt>Deletion requested</dt><dd>{{ $user->deletionRequestedAt->format('Y-m-d') }}</dd>
                        @endif
                        <dt>Active sanctions</dt><dd>{{ $activeSanctions }}</dd>
                    </dl>
                </div>
            </section>

            <section class="adm-panel" aria-label="Sanction history">
                <div class="adm-panel-head"><h2>Sanction history</h2></div>
                @forelse($sanctions as $s)
                    @if($loop->first)<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Type</th><th>Reason</th><th>Issued by</th><th>Period</th><th>State</th></tr></thead><tbody>@endif
                    <tr>
                        <td>{{ $s->type->label() }}</td>
                        <td>{{ $s->reasonCode->label() }}<div class="adm-hint">{{ $s->publicReason }}</div></td>
                        <td>{{ $s->issuedByUsername ?? '–' }}</td>
                        <td>{{ $s->startsAt->format('Y-m-d') }} → {{ $s->expiresAt?->format('Y-m-d') ?? 'permanent' }}</td>
                        <td>
                            @if($s->active)
                                <span class="adm-status" data-status="suspended">Active</span>
                            @elseif($s->liftedAt)
                                <span class="adm-hint">Lifted {{ $s->liftedAt->format('Y-m-d') }} by {{ $s->liftedByUsername ?? '–' }}</span>
                            @else
                                <span class="adm-hint">Expired</span>
                            @endif
                        </td>
                    </tr>
                    @if($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="adm-empty"><h3>Clean record</h3><p>This account has never been sanctioned.</p></div>
                @endforelse
            </section>
        </div>

        <aside style="display:flex;flex-direction:column;gap:var(--space-5)">
            @unless($canAct)
                <section class="adm-panel"><div class="adm-panel-body">
                    <p class="adm-hint">You cannot act on this account. Moderators and admins can only sanction accounts ranked below their own. Escalate to a super admin instead.</p>
                </div></section>
            @else
                <section class="adm-panel" aria-label="Apply a sanction">
                    <div class="adm-panel-head"><h2>Apply a sanction</h2></div>
                    <div class="adm-panel-body">
                        <form class="adm-form" method="post" action="{{ route('admin.users.sanctions.store', $user->username) }}"
                              x-data="{ type: @js(old('type') ?? ''), timeBoxed: @js($timeBoxed) }"
                              @change="if ($event.target.id === 's-type-native') type = $event.target.value"
                              @submit="if (!confirm('Apply this ' + (type || 'sanction') + ' to {{ $user->username }}? They will be told: ' + ($el.public_reason.value || '(no reason given)'))) $event.preventDefault()">
                            @csrf
                            <x-ui.select id="s-type" name="type" label="Sanction" placeholder="Choose a sanction"
                                :searchable="false" :value="old('type')" :error="$errors->first('type')" required
                                :options="collect($sanctionTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />

                            <x-ui.select id="s-reason" name="reason_code" label="Reason code" placeholder="Choose a reason"
                                :value="old('reason_code')" :error="$errors->first('reason_code')" required
                                :options="collect($reasonCodes)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />

                            <div x-show="timeBoxed.includes(type)" x-cloak>
                                <x-ui.input id="s-duration" name="duration_days" type="number" min="1" label="Duration (days)"
                                    hint="Restriction up to {{ $limits['restriction']['max_days'] }} days, suspension up to {{ $limits['suspension']['max_days'] }} days."
                                    value="{{ old('duration_days') }}" :error="$errors->first('duration_days')"
                                    x-bind:required="timeBoxed.includes(type)" />
                            </div>

                            <x-ui.input id="s-public" name="public_reason" label="Public reason (shown to the user)"
                                maxlength="255" value="{{ old('public_reason') }}" :error="$errors->first('public_reason')" required />

                            <x-ui.textarea id="s-note" name="internal_note" label="Internal note (staff only)"
                                :maxlength="2000" :value="old('internal_note')" :error="$errors->first('internal_note')" />

                            <button type="submit" class="adm-btn" data-tone="danger">Apply sanction</button>
                        </form>
                    </div>
                </section>

                @if($activeSanctions > 0 || $user->status->value !== 'active')
                    <section class="adm-panel" aria-label="Lift sanctions">
                        <div class="adm-panel-head"><h2>Lift sanctions</h2></div>
                        <div class="adm-panel-body">
                            <form class="adm-form" method="post" action="{{ route('admin.users.sanctions.destroy', $user->username) }}"
                                  @submit="if (!confirm('Reinstate {{ $user->username }} and lift all active sanctions?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <x-ui.input id="l-reason" name="reason" label="Reason for lifting"
                                    maxlength="255" value="{{ old('reason') }}" :error="$errors->first('reason')" required />
                                <button type="submit" class="adm-btn" data-tone="primary">Lift and reinstate</button>
                            </form>
                        </div>
                    </section>
                @endif
            @endunless
        </aside>
    </div>
</x-layouts.admin>
