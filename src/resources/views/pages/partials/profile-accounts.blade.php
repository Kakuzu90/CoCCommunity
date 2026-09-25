@if($accounts->others)
    <div class="profile-accounts">
        @foreach($accounts->others as $account)
            <x-player.card :account="$account" variant="standard" />
        @endforeach
    </div>
@elseif($accounts->featured)
    <p class="ui-help">{{ $isOwner ? 'Your featured account above is your only attached account.' : 'The featured account above is the only public one.' }}</p>
@else
    <x-ui.empty-state :title="$isOwner ? 'No accounts attached yet' : 'No public accounts'"
        :body="$isOwner ? 'Attach your Clash of Clans account and verify it with an in-game token to earn your verified badge.' : $name.' has not shared any verified Clash of Clans accounts.'" />
@endif
@if($isOwner)
    <p class="profile-accounts__footer"><a class="ui-button" data-variant="secondary" data-size="sm" href="{{ route('accounts.index') }}">{{ $accounts->isEmpty() ? 'Attach an account' : 'Manage accounts' }}</a></p>
@endif
