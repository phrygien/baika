<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\State;
use App\Models\Country;
use Flux\Flux;

new class extends Component
{
    public ?int $stateId = null;
    public string $country_id = '';
    public string $name = '';
    public string $code = '';
    public string $countrySearch = '';

    #[On('edit-state')]
    public function loadState(int $id): void
    {
        $state = State::findOrFail($id);

        $this->stateId    = $state->id;
        $this->country_id = (string) $state->country_id;
        $this->name       = $state->name;
        $this->code       = $state->code ?? '';

        $this->countrySearch = '';
        $this->resetValidation();
        Flux::modal('edit-state')->show();
    }

    #[Computed]
    public function countries()
    {
        return Country::query()
            ->when($this->countrySearch, fn($q) =>
                $q->where('name', 'like', "%{$this->countrySearch}%")
                  ->orWhere('code', 'like', "%{$this->countrySearch}%")
            )
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'country_id' => 'required|integer|exists:countries,id',
            'name'       => 'required|string|max:255',
            'code'       => 'nullable|string|max:10',
        ]);

        try {
            State::findOrFail($this->stateId)->update([
                'country_id' => $this->country_id,
                'name'       => $this->name,
                'code'       => $this->code ? strtoupper($this->code) : null,
            ]);

            $this->dispatch('state-updated');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('State updated'),
                message: __(':name has been updated successfully.', ['name' => $this->name]),
            );

            Flux::modal('edit-state')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Update failed'),
                message: __('An error occurred while updating the state.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="edit-state" class="w-full max-w-lg">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Edit State') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Update the state or region details.') }}</flux:text>
                </div>

                {{-- Country --}}
                <div class="space-y-2">
                    <flux:label>{{ __('Country') }}</flux:label>
                    <flux:input
                        wire:model.live.debounce.200ms="countrySearch"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search country...') }}"
                        size="sm"
                    />
                    <div class="max-h-40 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @forelse ($this->countries as $country)
                            <label
                                wire:key="country-{{ $country->id }}"
                                class="flex cursor-pointer items-center gap-3 border-b border-zinc-50 px-4 py-2.5 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/30
                                    {{ (int) $country_id === $country->id ? 'bg-blue-50 dark:bg-blue-950/20' : '' }}"
                            >
                                <input
                                    type="radio"
                                    name="country_id"
                                    value="{{ $country->id }}"
                                    x-on:change="$wire.set('country_id', '{{ $country->id }}')"
                                    {{ (int) $country_id === $country->id ? 'checked' : '' }}
                                    class="text-blue-600"
                                />
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    @if ($country->flag_url)
                                        <img
                                            src="{{ $country->flag_url }}"
                                            alt="{{ $country->name }}"
                                            class="h-4 w-6 shrink-0 rounded-sm object-cover shadow-sm"
                                        />
                                    @endif
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $country->name }}
                                    </span>
                                </div>
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $country->code }}
                                </flux:badge>
                            </label>
                        @empty
                            <div class="flex items-center justify-center py-4">
                                <p class="text-sm text-zinc-400">{{ __('No countries found.') }}</p>
                            </div>
                        @endforelse
                    </div>
                    <flux:error name="country_id" />
                </div>

                {{-- Nom + Code --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <flux:input
                            wire:model="name"
                            label="{{ __('State name') }}"
                            placeholder="Île-de-France"
                        />
                    </div>
                    <flux:input
                        wire:model="code"
                        label="{{ __('Code') }}"
                        placeholder="IDF"
                        description="{{ __('Optional') }}"
                        class="uppercase"
                    />
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
