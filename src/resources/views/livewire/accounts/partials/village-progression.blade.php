@if($groups)
    <div class="account-detail__groups">
        @foreach($groups as $group => $units)
            @php($groupId = 'progression-'.$village.'-'.\Illuminate\Support\Str::slug($group))
            <section aria-labelledby="{{ $groupId }}">
                <h3 id="{{ $groupId }}">{{ $group }}</h3>
                <ul class="account-detail__units">
                    @foreach($units as $unit)
                        <li><x-player.unit :unit="$unit" :opens="$unit['equipment'] ? 'equipment-'.$unit['slug'] : null" /></li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@else
    <p class="ui-help">No {{ \App\Domain\PlayerAccounts\Services\AccountProgressionView::VILLAGES[$village] }} progression from the game yet.</p>
@endif
