<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Supplier;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'created_at';

    #[Url(history: true)]
    public string $sortDirection = 'desc';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterVerified = '';

    #[Url(history: true)]
    public string $filterFeatured = '';

    #[Url(history: true)]
    public bool $showTrashed = false;

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
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterVerified(): void { $this->resetPage(); }
    public function updatingFilterFeatured(): void { $this->resetPage(); }

    public function toggleTrashed(): void
    {
        $this->showTrashed   = !$this->showTrashed;
        $this->filterStatus  = '';
        $this->filterVerified = '';
        $this->filterFeatured = '';
        $this->search        = '';
        $this->resetPage();
    }

    public function toggleFeatured(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_featured' => !$supplier->is_featured]);
        unset($this->suppliers);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $supplier->is_featured ? __('Supplier featured') : __('Supplier unfeatured'),
            message: $supplier->is_featured
                ? __(':name is now featured.', ['name' => $supplier->shop_name])
                : __(':name has been unfeatured.', ['name' => $supplier->shop_name]),
        );
    }

    public function restore(int $id): void
    {
        try {
            Supplier::withTrashed()->findOrFail($id)->restore();
            unset($this->suppliers);
            $this->dispatch('notify', variant: 'success', title: __('Supplier restored'), message: __('The supplier has been restored.'));
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'warning', title: __('Restore failed'), message: __('An error occurred.'));
        }
    }

    public function forceDelete(int $id): void
    {
        try {
            Supplier::withTrashed()->findOrFail($id)->forceDelete();
            unset($this->suppliers);
            $this->dispatch('notify', variant: 'success', title: __('Supplier permanently deleted'), message: __('The supplier has been permanently deleted.'));
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'warning', title: __('Delete failed'), message: __('An error occurred.'));
        }
    }

    #[On('supplier-created')]
    #[On('supplier-updated')]
    #[On('supplier-deleted')]
    public function refreshSuppliers(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()
            ->with(['user', 'country'])
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('shop_name', 'like', "%{$this->search}%")
                      ->orWhere('slug', 'like', "%{$this->search}%")
                      ->orWhere('registration_number', 'like', "%{$this->search}%")
                      ->orWhereHas('user', fn($q) =>
                          $q->where('email', 'like', "%{$this->search}%")
                            ->orWhere('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                      )
                )
            )
            ->when(!$this->showTrashed && $this->filterStatus, fn($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when(!$this->showTrashed && $this->filterVerified !== '', fn($q) =>
                $q->where('is_verified', (bool) $this->filterVerified)
            )
            ->when(!$this->showTrashed && $this->filterFeatured !== '', fn($q) =>
                $q->where('is_featured', (bool) $this->filterFeatured)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function statusColor(string $status): string
    {
        return match($status) {
            'approved'  => 'green',
            'pending'   => 'yellow',
            'rejected'  => 'red',
            'suspended' => 'orange',
            default     => 'zinc',
        };
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Suppliers') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your suppliers') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search suppliers...') }}"
            />
        </div>

        @if (!$showTrashed)
            <flux:select wire:model.live="filterStatus" placeholder="{{ __('All statuses') }}" class="w-40">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
                <flux:select.option value="suspended">{{ __('Suspended') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="filterVerified" placeholder="{{ __('All') }}" class="w-36">
                <flux:select.option value="">{{ __('All') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Verified') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Unverified') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="filterFeatured" placeholder="{{ __('All') }}" class="w-36">
                <flux:select.option value="">{{ __('All') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Featured') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Not featured') }}</flux:select.option>
            </flux:select>
        @endif

        <flux:spacer />

        <flux:button
            wire:click="toggleTrashed"
            :variant="$showTrashed ? 'danger' : 'ghost'"
            icon="trash"
            size="sm"
        >
            {{ $showTrashed ? __('View active') : __('View trash') }}
        </flux:button>

        @if (!$showTrashed)
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-supplier')">
                {{ __('Add Supplier') }}
            </flux:button>
        @endif
    </div>

    {{-- Banner trash --}}
    @if ($showTrashed)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <flux:icon name="trash" class="size-4 text-red-500" />
            <p class="text-sm text-red-700 dark:text-red-400">
                {{ __('Showing deleted suppliers. You can restore or permanently delete them.') }}
            </p>
        </div>
    @endif

    <flux:table :paginate="$this->suppliers">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'shop_name'"
                :direction="$sortDirection"
                wire:click="sort('shop_name')"
            >
                {{ __('Supplier') }}
            </flux:table.column>

            <flux:table.column>{{ __('Owner') }}</flux:table.column>

            <flux:table.column>{{ __('Country') }}</flux:table.column>

            @if (!$showTrashed)
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'status'"
                    :direction="$sortDirection"
                    wire:click="sort('status')"
                >
                    {{ __('Status') }}
                </flux:table.column>

                <flux:table.column>{{ __('Stats') }}</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'commission_rate'"
                    :direction="$sortDirection"
                    wire:click="sort('commission_rate')"
                >
                    {{ __('Commission') }}
                </flux:table.column>

                <flux:table.column>{{ __('Badges') }}</flux:table.column>
            @else
                <flux:table.column>{{ __('Deleted') }}</flux:table.column>
            @endif

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
            @forelse ($this->suppliers as $supplier)
                <flux:table.row :key="$supplier->id" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                    {{-- Shop --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                size="sm"
                                src="{{ $supplier->logo }}"
                                name="{{ $supplier->shop_name }}"
                                class="{{ $showTrashed ? 'grayscale' : '' }}"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $supplier->shop_name }}
                                </p>
                                <p class="text-xs text-zinc-400">{{ $supplier->slug }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Owner --}}
                    <flux:table.cell>
                        @if ($supplier->user)
                            <div class="flex items-center gap-2">
                                <flux:avatar
                                    size="xs"
                                    src="{{ $supplier->user->avatar }}"
                                    name="{{ $supplier->user->fullName() }}"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $supplier->user->fullName() ?: $supplier->user->name }}
                                    </p>
                                    <p class="text-xs text-zinc-400">{{ $supplier->user->email }}</p>
                                </div>
                            </div>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Country --}}
                    <flux:table.cell>
                        @if ($supplier->country)
                            <div class="flex items-center gap-2">
                                @if ($supplier->country->flag_url)
                                    <img
                                        src="{{ $supplier->country->flag_url }}"
                                        alt="{{ $supplier->country->name }}"
                                        class="h-4 w-6 rounded-sm object-cover shadow-sm"
                                    />
                                @endif
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $supplier->country->name }}
                                </span>
                            </div>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    @if (!$showTrashed)
                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="$this->statusColor($supplier->status ?? 'pending')"
                                inset="top bottom"
                            >
                                {{ ucfirst($supplier->status ?? 'pending') }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Stats --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span title="{{ __('Products') }}">
                                    <flux:icon name="cube" class="mr-0.5 inline size-3" />
                                    {{ $supplier->total_products ?? 0 }}
                                </span>
                                <span title="{{ __('Sales') }}">
                                    <flux:icon name="shopping-bag" class="mr-0.5 inline size-3" />
                                    {{ $supplier->total_sales ?? 0 }}
                                </span>
                                @if ($supplier->average_rating)
                                    <span title="{{ __('Rating') }}">
                                        <flux:icon name="star" class="mr-0.5 inline size-3 text-yellow-400" variant="solid" />
                                        {{ number_format($supplier->average_rating, 1) }}
                                    </span>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Commission --}}
                        <flux:table.cell>
                            @if ($supplier->commission_rate !== null)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $supplier->commission_rate }}%
                                </flux:badge>
                            @else
                                <span class="text-sm text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Badges --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                @if ($supplier->is_verified)
                                    <flux:badge size="sm" color="blue" inset="top bottom" icon="check-badge">
                                        {{ __('Verified') }}
                                    </flux:badge>
                                @endif
                                @if ($supplier->is_featured)
                                    <flux:badge size="sm" color="yellow" inset="top bottom" icon="star">
                                        {{ __('Featured') }}
                                    </flux:badge>
                                @endif
                                @if (!$supplier->is_verified && !$supplier->is_featured)
                                    <span class="text-sm text-zinc-400">—</span>
                                @endif
                            </div>
                        </flux:table.cell>
                    @else
                        {{-- Deleted at --}}
                        <flux:table.cell class="whitespace-nowrap text-sm text-red-500">
                            {{ $supplier->deleted_at?->diffForHumans() ?? '—' }}
                        </flux:table.cell>
                    @endif

                    {{-- Created at --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $supplier->created_at->format('d M Y') }}
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        @if ($showTrashed)
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="arrow-path" wire:click="restore({{ $supplier->id }})">
                                        {{ __('Restore') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="forceDelete({{ $supplier->id }})"
                                        wire:confirm="{{ __('Permanently delete this supplier? This cannot be undone.') }}"
                                    >
                                        {{ __('Delete permanently') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @else
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="eye"
                                        wire:click="$dispatch('view-supplier', { id: {{ $supplier->id }} })"
                                    >
                                        {{ __('View') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        icon="pencil-square"
                                        wire:click="$dispatch('edit-supplier', { id: {{ $supplier->id }} })"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        icon="check-circle"
                                        wire:click="$dispatch('approve-supplier', { id: {{ $supplier->id }} })"
                                    >
                                        {{ __('Approve / Reject') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        :icon="$supplier->is_featured ? 'star' : 'star'"
                                        wire:click="toggleFeatured({{ $supplier->id }})"
                                    >
                                        {{ $supplier->is_featured ? __('Unfeature') : __('Feature') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="$dispatch('delete-supplier', { id: {{ $supplier->id }} })"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon
                                name="{{ $showTrashed ? 'trash' : 'building-storefront' }}"
                                class="mb-3 size-10 text-zinc-300 dark:text-zinc-600"
                            />
                            @if ($showTrashed)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No deleted suppliers found.') }}
                                </p>
                            @elseif ($this->search || $this->filterStatus || $this->filterVerified !== '' || $this->filterFeatured !== '')
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for the applied filters.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Try modifying your search or filters.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No suppliers found.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Start by adding a supplier.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::suppliers.create />
    <livewire:pages::suppliers.edit />
</div>
