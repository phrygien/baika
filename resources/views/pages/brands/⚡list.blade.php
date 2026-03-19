<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Brand;

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

    #[Url(history: true)]
    public string $filterFeatured = '';

    #[Url(history: true)]
    public int $perPage = 15;

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
    public function updatingFilterFeatured(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => !$brand->is_active]);
        unset($this->brands);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $brand->is_active ? __('Brand activated') : __('Brand deactivated'),
            message: $brand->is_active
                ? __(':name has been activated.', ['name' => $brand->name])
                : __(':name has been deactivated.', ['name' => $brand->name]),
        );
    }

    public function toggleFeatured(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['is_featured' => !$brand->is_featured]);
        unset($this->brands);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $brand->is_featured ? __('Brand featured') : __('Brand unfeatured'),
            message: __(':name has been updated.', ['name' => $brand->name]),
        );
    }

    #[On('brand-created')]
    #[On('brand-updated')]
    #[On('brand-deleted')]
    public function refreshBrands(): void
    {
        unset($this->brands);
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->withCount('products')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('slug', 'like', "%{$this->search}%")
            )
            ->when($this->filterActive !== '', fn($q) =>
                $q->where('is_active', (bool) $this->filterActive)
            )
            ->when($this->filterFeatured !== '', fn($q) =>
                $q->where('is_featured', (bool) $this->filterFeatured)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function formatWebsite(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? preg_replace('/^www\./', '', $host) : $url;
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Brands') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage product brands') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search brands...') }}"
            />
        </div>

        <flux:select wire:model.live="filterActive" placeholder="{{ __('All') }}" class="w-36">
            <flux:select.option value="">{{ __('All') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="0">{{ __('Inactive') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filterFeatured" placeholder="{{ __('All') }}" class="w-36">
            <flux:select.option value="">{{ __('All') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Featured') }}</flux:select.option>
            <flux:select.option value="0">{{ __('Not featured') }}</flux:select.option>
        </flux:select>

        <flux:spacer />

        {{-- Per page --}}
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-400">{{ __('Show') }}</span>
            <flux:select wire:model.live="perPage" class="w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="15">15</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
                <flux:select.option value="100">100</flux:select.option>
            </flux:select>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-brand')">
            {{ __('Add Brand') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->brands">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                {{ __('Brand') }}
            </flux:table.column>

            <flux:table.column>{{ __('Products') }}</flux:table.column>

            <flux:table.column>{{ __('Status') }}</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'created_at'"
                :direction="$sortDirection"
                wire:click="sort('created_at')"
            >
                {{ __('Created') }}
            </flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->brands as $brand)
                <flux:table.row :key="$brand->id">

                    {{-- Logo + Nom --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($brand->logo)
                                <img
                                    src="{{ $brand->logo }}"
                                    alt="{{ $brand->name }}"
                                    class="size-9 rounded-lg object-contain p-0.5 ring-1 ring-zinc-200 dark:ring-zinc-700"
                                />
                            @else
                                <div class="flex size-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="building-storefront" class="size-4 text-zinc-400" />
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $brand->name }}
                                </p>
                                <p class="font-mono text-xs text-zinc-400">{{ $brand->slug }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Products --}}
                    <flux:table.cell>
                        @if ($brand->products_count > 0)
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ $brand->products_count }} {{ __('products') }}
                            </flux:badge>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:field variant="inline">
                                <flux:switch
                                    :checked="$brand->is_active"
                                    wire:click="toggleActive({{ $brand->id }})"
                                />
                            </flux:field>
                            @if ($brand->is_featured)
                                <flux:badge size="sm" color="yellow" inset="top bottom" icon="star">
                                    {{ __('Featured') }}
                                </flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    {{-- Created at --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $brand->created_at->format('d M Y') }}
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-brand', { id: {{ $brand->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="star"
                                    wire:click="toggleFeatured({{ $brand->id }})"
                                >
                                    {{ $brand->is_featured ? __('Unfeature') : __('Feature') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-brand', { id: {{ $brand->id }} })"
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
                            <flux:icon name="building-storefront" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search || $this->filterActive !== '' || $this->filterFeatured !== '')
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for the applied filters.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Try modifying your search or filters.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No brands found.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Start by adding a brand.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::brands.create />
    <livewire:pages::brands.edit />
    <livewire:pages::brands.delete />
</div>
