<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\State;
use App\Models\City;
use Flux\Flux;

new class extends Component
{
    // ── View state ─────────────────────────────────────────────────────────
    public ?int $stateId = null;
    public string $stateName = '';
    public string $countryName = '';
    public ?int $countryId = null;
    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    // ── Create city ────────────────────────────────────────────────────────
    public string $newName = '';
    public string $newPostalCode = '';
    public string $newLatitude = '';
    public string $newLongitude = '';
    public bool $newIsActive = true;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        unset($this->cities);
    }

    #[On('view-cities')]
    public function loadState(int $id): void
    {
        $state = State::with('country')->findOrFail($id);

        $this->stateId       = $state->id;
        $this->countryId     = $state->country_id;
        $this->stateName     = $state->name;
        $this->countryName   = $state->country?->name ?? '';
        $this->search        = '';
        $this->sortBy        = 'name';
        $this->sortDirection = 'asc';

        $this->resetCreateForm();
        unset($this->cities);

        Flux::modal('view-cities')->show();
    }

    #[On('city-created')]
    #[On('city-updated')]
    #[On('city-deleted')]
    public function refreshCities(): void
    {
        unset($this->cities);
    }

    #[Computed]
    public function cities()
    {
        return City::query()
            ->where('state_id', $this->stateId)
            ->when($this->search, fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('name', 'like', "%{$this->search}%")
                          ->orWhere('postal_code', 'like', "%{$this->search}%")
                )
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    public function openCreateCity(): void
    {
        $this->resetCreateForm();
        $this->resetValidation();
        Flux::modal('create-city')->show();
    }

    protected function resetCreateForm(): void
    {
        $this->newName        = '';
        $this->newPostalCode  = '';
        $this->newLatitude    = '';
        $this->newLongitude   = '';
        $this->newIsActive    = true;
    }

    public function saveCity(): void
    {
        $this->validate([
            'newName'       => 'required|string|max:255',
            'newPostalCode' => 'nullable|string|max:20',
            'newLatitude'   => 'nullable|numeric|between:-90,90',
            'newLongitude'  => 'nullable|numeric|between:-180,180',
            'newIsActive'   => 'boolean',
        ]);

        try {
            City::create([
                'state_id'    => $this->stateId,
                'country_id'  => $this->countryId,
                'name'        => $this->newName,
                'postal_code' => $this->newPostalCode ?: null,
                'latitude'    => $this->newLatitude !== '' ? $this->newLatitude : null,
                'longitude'   => $this->newLongitude !== '' ? $this->newLongitude : null,
                'is_active'   => $this->newIsActive,
            ]);

            unset($this->cities);
            $this->resetCreateForm();

            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('City created'),
                message: __(':name has been created successfully.', ['name' => $this->newName]),
            );

            Flux::modal('create-city')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Creation failed'),
                message: __('An error occurred while creating the city.'),
            );
        }
    }
};
?>

<div>
    {{-- Modal principal : liste des cities --}}
    <flux:modal name="view-cities" class="w-full max-w-2xl">
        <div class="space-y-6">

            {{-- Header --}}
            <div>
                <flux:heading size="lg">{{ $stateName }}</flux:heading>
                <flux:text class="mt-1">
                    @if ($countryName)
                        <span class="text-zinc-400">{{ $countryName }} · </span>
                    @endif
                    <span class="font-medium text-blue-600 dark:text-blue-400">
                        {{ $this->cities->count() }} {{ __('cities') }}
                    </span>
                </flux:text>
            </div>

            {{-- Search + Add --}}
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <flux:input
                        wire:model.live.debounce.200ms="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search cities...') }}"
                        size="sm"
                    />
                </div>
                <flux:button
                    type="button"
                    variant="primary"
                    size="sm"
                    x-on:click="$wire.openCreateCity()"
                >
                    {{ __('Add City') }}
                </flux:button>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                @if ($this->cities->isNotEmpty())
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-100 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/50">
                                <th class="px-4 py-3 text-left">
                                    <button
                                        type="button"
                                        wire:click="sort('name')"
                                        class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:text-zinc-400"
                                    >
                                        {{ __('City') }}
                                        @if ($sortBy === 'name')
                                            <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                        @endif
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                        {{ __('Postal code') }}
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                        {{ __('Status') }}
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                            @foreach ($this->cities as $city)
                                <tr
                                    wire:key="city-{{ $city->id }}"
                                    class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
                                >
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $city->name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $city->postal_code ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge
                                            size="sm"
                                            :color="$city->is_active ? 'green' : 'zinc'"
                                            inset="top bottom"
                                        >
                                            {{ $city->is_active ? __('Active') : __('Inactive') }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                            <flux:menu>
                                                <flux:menu.item
                                                    icon="pencil-square"
                                                    wire:click="$dispatch('edit-city', { id: {{ $city->id }} })"
                                                >
                                                    {{ __('Edit') }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item
                                                    icon="trash"
                                                    variant="danger"
                                                    wire:click="$dispatch('delete-city', { id: {{ $city->id }} })"
                                                >
                                                    {{ __('Delete') }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <flux:icon name="building-office" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                        @if ($search)
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                {{ __('No results for') }}
                                <span class="text-zinc-700 dark:text-zinc-200">"{{ $search }}"</span>
                            </p>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('Try a different search term.') }}</p>
                        @else
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                {{ __('No cities found for this state.') }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('Start by adding a city.') }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>

        </div>
    </flux:modal>

    {{-- Sous-modal : créer une city --}}
    <flux:modal name="create-city" class="w-full max-w-lg">
        <form wire:submit="saveCity">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Add City') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('Adding to') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $stateName }}</span>
                        @if ($countryName)
                            <span class="text-zinc-400"> · {{ $countryName }}</span>
                        @endif
                    </flux:text>
                </div>

                {{-- Nom --}}
                <flux:input
                    wire:model="newName"
                    label="{{ __('City name') }}"
                    placeholder="Paris"
                />

                {{-- Postal code --}}
                <flux:input
                    wire:model="newPostalCode"
                    label="{{ __('Postal code') }}"
                    placeholder="75000"
                />

                {{-- Coordonnées --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="newLatitude"
                        label="{{ __('Latitude') }}"
                        placeholder="48.8566"
                        type="number"
                        step="any"
                    />
                    <flux:input
                        wire:model="newLongitude"
                        label="{{ __('Longitude') }}"
                        placeholder="2.3522"
                        type="number"
                        step="any"
                    />
                </div>

                {{-- Status --}}
                <flux:field variant="inline">
                    <flux:label>{{ __('Active') }}</flux:label>
                    <flux:switch wire:model="newIsActive" />
                </flux:field>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="plus">
                        {{ __('Add City') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>

    <livewire:pages::cities.edit />
    <livewire:pages::cities.delete />
</div>
