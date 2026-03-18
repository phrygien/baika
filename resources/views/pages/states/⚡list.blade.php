<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\State;
use App\Models\Country;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'name';

    #[Url(history: true)]
    public string $sortDirection = 'asc';

    #[Url(history: true)]
    public string $filterCountry = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterCountry(): void { $this->resetPage(); }

    #[On('state-created')]
    #[On('state-updated')]
    #[On('state-deleted')]
    public function refreshStates(): void
    {
        unset($this->states);
    }

    #[Computed]
    public function states()
    {
        return State::query()
            ->with('country')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
            )
            ->when($this->filterCountry, fn($q) =>
                $q->where('country_id', $this->filterCountry)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function countries()
    {
        return Country::orderBy('name')->get();
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('States') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage states and regions') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search states...') }}"
            />
        </div>

        <flux:select wire:model.live="filterCountry" placeholder="{{ __('All countries') }}" class="w-48">
            <flux:select.option value="">{{ __('All countries') }}</flux:select.option>
            @foreach ($this->countries as $country)
                <flux:select.option value="{{ $country->id }}">
                    {{ $country->name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:spacer />

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-state')">
            {{ __('Add State') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->states">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                {{ __('State') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'code'"
                :direction="$sortDirection"
                wire:click="sort('code')"
            >
                {{ __('Code') }}
            </flux:table.column>

            <flux:table.column>{{ __('Country') }}</flux:table.column>

            <flux:table.column>{{ __('Cities') }}</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->states as $state)
                <flux:table.row :key="$state->id">

                    {{-- Nom --}}
                    <flux:table.cell variant="strong">
                        {{ $state->name }}
                    </flux:table.cell>

                    {{-- Code --}}
                    <flux:table.cell>
                        @if ($state->code)
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $state->code }}
                            </flux:badge>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Country --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            @if ($state->country?->flag_url)
                                <img
                                    src="{{ $state->country->flag_url }}"
                                    alt="{{ $state->country->name }}"
                                    class="h-4 w-6 rounded-sm object-cover shadow-sm"
                                />
                            @endif
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $state->country?->name ?? '—' }}
                            </span>
                            @if ($state->country?->code)
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $state->country->code }}
                                </flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    {{-- Cities count --}}
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $state->cities_count ?? $state->cities()->count() }} {{ __('cities') }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-state', { id: {{ $state->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="building-office"
                                    wire:click="$dispatch('view-cities', { id: {{ $state->id }} })"
                                >
                                    {{ __('View cities') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-state', { id: {{ $state->id }} })"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="map" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search || $this->filterCountry)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for the applied filters.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Try modifying your search or filters.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No states found.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Start by adding a state.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::states.create />
    <livewire:pages::states.edit />
    <livewire:pages::states.delete />
    <livewire:pages::states.view />
</div>
