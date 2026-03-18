<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
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
    public string $filterActive = '';

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
    public function updatingFilterActive(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $country = Country::findOrFail($id);
        $isActive = !$country->is_active;
        $country->update(['is_active' => $isActive]);
        unset($this->countries);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $isActive ? __('Country activated') : __('Country deactivated'),
            message: $isActive
                ? __(':name has been activated successfully.', ['name' => $country->name])
                : __(':name has been deactivated successfully.', ['name' => $country->name]),
        );
    }

    #[On('country-created')]
    #[On('country-updated')]
    #[On('country-deleted')]
    public function refreshCountries(): void
    {
        unset($this->countries);
    }

    #[Computed]
    public function countries()
    {
        return Country::query()
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('dial_code', 'like', "%{$this->search}%")
                  ->orWhere('currency_code', 'like', "%{$this->search}%")
            )
            ->when($this->filterActive !== '', fn($q) =>
                $q->where('is_active', (bool) $this->filterActive)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Countries') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage countries') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search countries...') }}"
            />
        </div>

        <flux:select wire:model.live="filterActive" placeholder="{{ __('All') }}" class="w-36">
            <flux:select.option value="">{{ __('All') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="0">{{ __('Inactive') }}</flux:select.option>
        </flux:select>

        <flux:spacer />

        <flux:button variant="primary" wire:click="$dispatch('create-country')">
            {{ __('Add Country') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->countries">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                {{ __('Country') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'code'"
                :direction="$sortDirection"
                wire:click="sort('code')"
            >
                {{ __('Code') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'dial_code'"
                :direction="$sortDirection"
                wire:click="sort('dial_code')"
            >
                {{ __('Dial code') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'currency_code'"
                :direction="$sortDirection"
                wire:click="sort('currency_code')"
            >
                {{ __('Currency') }}
            </flux:table.column>

            <flux:table.column>{{ __('Status') }}</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->countries as $country)
                <flux:table.row :key="$country->id">

                    {{-- Flag + Nom --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($country->flag_url)
                                <img
                                    src="{{ $country->flag_url }}"
                                    alt="{{ $country->name }}"
                                    class="h-4 w-6 rounded-sm object-cover shadow-sm"
                                />
                            @else
                                <div class="flex h-4 w-6 items-center justify-center rounded-sm bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="flag" class="size-3 text-zinc-400" />
                                </div>
                            @endif
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $country->name }}
                            </span>
                        </div>
                    </flux:table.cell>

                    {{-- Code ISO --}}
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $country->code }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Dial code --}}
                    <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $country->dial_code ?? '—' }}
                    </flux:table.cell>

                    {{-- Currency --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-1.5">
                            @if ($country->currency_code)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $country->currency_code }}
                                </flux:badge>
                            @endif
                            @if ($country->currency_symbol)
                                <span class="text-sm text-zinc-400">{{ $country->currency_symbol }}</span>
                            @endif
                            @if (!$country->currency_code && !$country->currency_symbol)
                                <span class="text-sm text-zinc-400">—</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    {{-- Status switch --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:field variant="inline">
                                <flux:switch
                                    :checked="$country->is_active"
                                    wire:click="toggleActive({{ $country->id }})"
                                />
                            </flux:field>
                            <flux:badge
                                size="sm"
                                :color="$country->is_active ? 'green' : 'zinc'"
                                inset="top bottom"
                            >
                                {{ $country->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </div>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-country', { id: {{ $country->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="map"
                                    wire:click="$dispatch('view-states', { id: {{ $country->id }} })"
                                >
                                    {{ __('View states') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-country', { id: {{ $country->id }} })"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="globe-alt" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search || $this->filterActive !== '')
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for the applied filters.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Try modifying your search or filters.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No countries found.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Start by adding a country.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::country.create />
    <livewire:pages::country.edit />
    <livewire:pages::country.delete />
</div>
