<x-layouts.app title="Publish a base" description="Share a Clash of Clans base layout.">
    @php($oldScalar = fn (string $key, string $fallback = ''): string => is_scalar(old($key)) ? (string) old($key) : $fallback)
    <div class="base-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Bases</p>
            <h1>Publish a base</h1>
            <p class="ui-help">Use a base link from the game. Your draft stays in this browser while you work.</p>
        </header>

        <form class="base-composer" method="POST" action="{{ route('bases.store') }}" x-data="baseComposer"
            data-old-screenshots="{{ implode(',', array_filter((array) old('screenshots', []), 'is_string')) }}"
            data-has-errors="{{ $errors->any() ? '1' : '0' }}"
            x-on:input.debounce.300ms="saveDraft()" x-on:change="saveDraft()"
            x-on:submit="if (pendingCount > 0 || failedCount > 0) $event.preventDefault()">
            @csrf

            @if($errors->any())
                <div id="composer-errors" class="base-errors" role="alert" tabindex="-1"
                    x-init="$nextTick(() => $el.focus())">
                    <h2>Check these fields</h2>
                    <ul>
                        @foreach($errors->keys() as $key)
                            @php($target = match (strtok($key, '.')) { 'baseLink' => 'base_link', 'cocAccountId' => 'coc_account_id', 'thLevel' => 'th_level', 'tags' => 'tags_text', 'media' => 'screenshots', default => strtok($key, '.') })
                            <li><a href="#{{ $target }}">{{ $errors->first($key) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="settings-card base-composer__section" aria-labelledby="base-media-heading">
                <div>
                    <p class="ui-eyebrow">01 / Artwork</p>
                    <h2 id="base-media-heading" class="settings-card-title">Screenshots</h2>
                    <p class="ui-help">Add up to {{ config('bases.screenshots_max') }} screenshots. JPG, PNG or WebP, up to 5 MB each.</p>
                </div>
                <label class="base-dropzone" x-on:dragover.prevent x-on:drop.prevent="addFiles($event.dataTransfer.files)">
                    <span>Choose screenshots or drop them here</span>
                    <input id="screenshots" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" multiple
                        x-on:change="addFiles($event.target.files); $event.target.value = ''">
                </label>
                <ul class="base-uploads" aria-live="polite">
                    <template x-for="item in items" :key="item.key">
                        <li>
                            <div class="base-upload-line">
                                <span x-text="item.name"></span>
                                <span x-text="item.status === 'uploading' ? item.progress + '%' : item.status"></span>
                            </div>
                            <progress x-show="item.status === 'uploading'" :value="item.progress" :aria-label="`Upload ${item.name}`" max="100"></progress>
                            <p class="text-danger" x-show="item.error" x-text="item.error" role="alert"></p>
                            <div class="base-upload-actions">
                                <button type="button" class="ui-button" data-variant="secondary" data-size="sm"
                                    x-show="item.status === 'failed' && item.file" x-on:click="retry(item)">Retry</button>
                                <button type="button" class="ui-button" data-variant="ghost" data-size="sm"
                                    x-on:click="remove(item)">Remove</button>
                            </div>
                            <input x-show="false" type="hidden" name="screenshots[]" :value="item.ulid" :disabled="item.status !== 'ready'">
                        </li>
                    </template>
                </ul>
                @error('screenshots')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                @error('media')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            </section>

            <section class="settings-card base-composer__section" aria-labelledby="base-details-heading">
                <div>
                    <p class="ui-eyebrow">02 / Details</p>
                    <h2 id="base-details-heading" class="settings-card-title">Describe the layout</h2>
                </div>
                <x-ui.input id="title" name="title" label="Title" :value="$oldScalar('title')" maxlength="80"
                    :error="$errors->first('title')" required hint="Up to 80 characters." />
                <x-ui.textarea id="description" name="description" label="Description" rows="4" maxlength="2000"
                    :error="$errors->first('description')" hint="Optional. Explain how you use this base.">{{ $oldScalar('description') }}</x-ui.textarea>

                <fieldset class="base-choice-group" id="th_level">
                    <legend>Town Hall level</legend>
                    <div class="base-choice-grid base-choice-grid--th">
                        @for($level = (int) config('bases.th_min'); $level <= (int) config('bases.th_max'); $level++)
                            <label class="base-choice">
                                <input type="radio" name="th_level" value="{{ $level }}" @checked($oldScalar('th_level') === (string) $level) required>
                                <span>TH {{ $level }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('th_level')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <fieldset class="base-choice-group" id="category">
                    <legend>Category</legend>
                    <div class="base-choice-grid">
                        @foreach(config('bases.categories') as $value => $label)
                            <label class="base-choice">
                                <input type="radio" name="category" value="{{ $value }}" @checked($oldScalar('category') === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('category')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <x-ui.input id="base_link" name="base_link" label="Base link" :value="$oldScalar('base_link')" type="url"
                    inputmode="url" autocomplete="off" required :error="$errors->first('base_link') ?: $errors->first('baseLink')"
                    hint="Paste the OpenLayout link copied from Clash of Clans." x-on:input="checkLink($event.target.value)" />
                <p class="ui-help" x-text="linkFeedback" aria-live="polite"></p>

                <x-ui.input id="tags_text" name="tags_text" label="Tags" :value="$oldScalar('tags_text')"
                    :error="$errors->first('tags_text') ?: $errors->first('tags')" hint="Optional. Separate up to 10 tags with commas." />
                <div class="base-suggestions" aria-label="Suggested tags">
                    @foreach(config('bases.suggested_tags') as $tag)
                        <button type="button" class="base-suggestion" x-on:click="addTag('{{ $tag }}')">{{ $tag }}</button>
                    @endforeach
                </div>
            </section>

            <section class="settings-card base-composer__section" aria-labelledby="base-publish-heading">
                <div>
                    <p class="ui-eyebrow">03 / Share</p>
                    <h2 id="base-publish-heading" class="settings-card-title">Publish settings</h2>
                </div>
                <label class="base-select-label" for="coc_account_id">Credit an account (optional)</label>
                <select id="coc_account_id" name="coc_account_id" class="ui-control">
                    <option value="">No account credit</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account['id'] }}" @selected($oldScalar('coc_account_id') === (string) $account['id'])>{{ $account['name'] }} · TH {{ $account['th_level'] }}</option>
                    @endforeach
                </select>
                @error('coc_account_id')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                @error('cocAccountId')<p class="text-danger" role="alert">{{ $message }}</p>@enderror

                <fieldset class="base-choice-group" id="visibility">
                    <legend>Who can see it?</legend>
                    <div class="base-choice-grid base-choice-grid--visibility">
                        @foreach(config('bases.visibilities') as $value => $label)
                            <label class="base-choice">
                                <input type="radio" name="visibility" value="{{ $value }}" @checked($oldScalar('visibility', 'public') === $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('visibility')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <p class="ui-help" x-show="pendingCount > 0" x-text="`${pendingCount} file${pendingCount === 1 ? '' : 's'} still uploading or processing`" aria-live="polite"></p>
                <p class="text-danger" x-show="failedCount > 0" x-text="`${failedCount} upload${failedCount === 1 ? '' : 's'} need a retry or removal`" role="alert"></p>
                <div class="base-composer__actions">
                    <x-ui.button type="submit" x-bind:disabled="pendingCount > 0 || failedCount > 0">Publish base</x-ui.button>
                    <a href="{{ route('bases.index') }}">Cancel</a>
                </div>
            </section>
        </form>
    </div>
</x-layouts.app>
