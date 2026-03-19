<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Category;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'sort_order';

    #[Url(history: true)]
    public string $sortDirection = 'asc';

    #[Url(history: true)]
    public string $filterActive = '';

    #[Url(history: true)]
    public string $filterFeatured = '';

    #[Url(history: true)]
    public string $filterParent = '';

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
    public function updatingFilterParent(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        unset($this->categories);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $category->is_active ? __('Category activated') : __('Category deactivated'),
            message: $category->is_active
                ? __(':name has been activated.', ['name' => $category->name])
                : __(':name has been deactivated.', ['name' => $category->name]),
        );
    }

    public function toggleFeatured(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_featured' => !$category->is_featured]);
        unset($this->categories);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $category->is_featured ? __('Category featured') : __('Category unfeatured'),
            message: __(':name has been updated.', ['name' => $category->name]),
        );
    }

    #[On('category-created')]
    #[On('category-updated')]
    #[On('category-deleted')]
    public function refreshCategories(): void
    {
        unset($this->categories);
        unset($this->rootCategories);
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->with('parent')
            ->withCount('children', 'products')
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
            ->when($this->filterParent === 'root', fn($q) => $q->roots())
            ->when($this->filterParent && $this->filterParent !== 'root', fn($q) =>
                $q->where('parent_id', $this->filterParent)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function rootCategories()
    {
        return Category::roots()->orderBy('name')->get(['id', 'name']);
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage product categories') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search categories...') }}"
            />
        </div>

        <flux:select wire:model.live="filterParent" placeholder="{{ __('All levels') }}" class="w-44">
            <flux:select.option value="">{{ __('All levels') }}</flux:select.option>
            <flux:select.option value="root">{{ __('Root only') }}</flux:select.option>
            @foreach ($this->rootCategories as $root)
                <flux:select.option value="{{ $root->id }}">{{ $root->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterActive" placeholder="{{ __('All statuses') }}" class="w-36">
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

        <flux:button variant="primary" wire:click="$dispatch('create-category')">
            {{ __('Add Category') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                {{ __('Category') }}
            </flux:table.column>

            <flux:table.column>{{ __('Parent') }}</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'depth'"
                :direction="$sortDirection"
                wire:click="sort('depth')"
            >
                {{ __('Depth') }}
            </flux:table.column>

            <flux:table.column>{{ __('Children') }}</flux:table.column>

            <flux:table.column>{{ __('Products') }}</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'commission_rate'"
                :direction="$sortDirection"
                wire:click="sort('commission_rate')"
            >
                {{ __('Commission') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'sort_order'"
                :direction="$sortDirection"
                wire:click="sort('sort_order')"
            >
                {{ __('Order') }}
            </flux:table.column>

            <flux:table.column>{{ __('Status') }}</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->id">

                    {{-- Nom + image --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($category->image)
                                <img
                                    src="{{ $category->image }}"
                                    alt="{{ $category->name }}"
                                    class="size-8 rounded-lg object-cover"
                                />
                            @elseif ($category->icon)
                                <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-lg dark:bg-zinc-800">
                                    {{ $category->icon }}
                                </div>
                            @else
                                <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="tag" class="size-4 text-zinc-400" />
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $category->name }}
                                </p>
                                <p class="text-xs text-zinc-400">{{ $category->slug }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Parent --}}
                    <flux:table.cell>
                        @if ($category->parent)
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $category->parent->name }}
                            </flux:badge>
                        @else
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ __('Root') }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Depth --}}
                    <flux:table.cell>
                        <span class="text-sm text-zinc-500">
                            {{ $category->depth ?? 0 }}
                        </span>
                    </flux:table.cell>

                    {{-- Children --}}
                    <flux:table.cell>
                        @if ($category->children_count > 0)
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $category->children_count }}
                            </flux:badge>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Products --}}
                    <flux:table.cell>
                        @if ($category->products_count > 0)
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ $category->products_count }}
                            </flux:badge>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Commission --}}
                    <flux:table.cell>
                        @if ($category->commission_rate !== null)
                            <flux:badge size="sm" color="green" inset="top bottom">
                                {{ $category->commission_rate }}%
                            </flux:badge>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Sort order --}}
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $category->sort_order ?? 0 }}
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:field variant="inline">
                                <flux:switch
                                    :checked="$category->is_active"
                                    wire:click="toggleActive({{ $category->id }})"
                                />
                            </flux:field>
                            @if ($category->is_featured)
                                <flux:badge size="sm" color="yellow" inset="top bottom" icon="star">
                                    {{ __('Featured') }}
                                </flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-category', { id: {{ $category->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="squares-plus"
                                    wire:click="$dispatch('create-category', { parentId: {{ $category->id }} })"
                                >
                                    {{ __('Add subcategory') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    :icon="$category->is_featured ? 'star' : 'star'"
                                    wire:click="toggleFeatured({{ $category->id }})"
                                >
                                    {{ $category->is_featured ? __('Unfeature') : __('Feature') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-category', { id: {{ $category->id }} })"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="tag" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search || $this->filterActive !== '' || $this->filterFeatured !== '' || $this->filterParent)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for the applied filters.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Try modifying your search or filters.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No categories found.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Start by adding a category.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::categories.create />
    <livewire:pages::categories.edit />
    <livewire:pages::categories.delete />
</div>
