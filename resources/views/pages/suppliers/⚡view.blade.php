<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Supplier;
use Flux\Flux;

new class extends Component
{
    public ?int $supplierId = null;

    #[On('view-supplier')]
    public function loadSupplier(int $id): void
    {
        $this->supplierId = $id;
        unset($this->supplier);
        Flux::modal('view-supplier')->show();
    }

    #[Computed]
    public function supplier()
    {
        if (!$this->supplierId) return null;

        return Supplier::with([
            'user',
            'country',
            'approvedBy',
            'documents',
            'bankAccounts',
        ])->findOrFail($this->supplierId);
    }

    public function statusColor(string $status): string
    {
        return match($status) {
            'approved'  => 'green',
            'pending'   => 'yellow',
            'rejected'  => 'red',
            'suspended' => 'zinc',
            default     => 'zinc',
        };
    }
};
?>

<div>
    <flux:modal name="view-supplier" class="w-full max-w-3xl">
        <div class="space-y-6">

            @if ($this->supplier)

            {{-- Header --}}
            <div class="flex items-start gap-4">
                <flux:avatar
                    size="lg"
                    src="{{ $this->supplier->logo }}"
                    name="{{ $this->supplier->shop_name }}"
                    class="shrink-0"
                />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $this->supplier->shop_name }}</flux:heading>
                        <flux:badge size="sm" :color="$this->statusColor($this->supplier->status ?? 'pending')" inset="top bottom">
                            {{ ucfirst($this->supplier->status) }}
                        </flux:badge>
                        @if ($this->supplier->is_verified)
                            <flux:badge size="sm" color="blue" inset="top bottom" icon="check-badge">{{ __('Verified') }}</flux:badge>
                        @endif
                        @if ($this->supplier->is_featured)
                            <flux:badge size="sm" color="yellow" inset="top bottom" icon="star">{{ __('Featured') }}</flux:badge>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ $this->supplier->slug }}
                        @if ($this->supplier->website)
                            · <a href="{{ $this->supplier->website }}" target="_blank" class="text-blue-500 hover:underline">
                                {{ $this->supplier->website }}
                            </a>
                        @endif
                    </p>
                </div>
            </div>

                {{-- Stats cards --}}
                <div class="grid grid-cols-4 gap-3">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                            {{ number_format($this->supplier->total_products ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('Products') }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                            {{ number_format($this->supplier->total_sales ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('Sales') }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                            {{ number_format($this->supplier->total_reviews ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('Reviews') }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex items-center justify-center gap-1">
                            <flux:icon name="star" variant="solid" class="size-5 text-yellow-400" />
                            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                                {{ $this->supplier->average_rating ? number_format($this->supplier->average_rating, 1) : '—' }}
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('Avg. rating') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">

                    {{-- Owner --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Owner') }}
                            </p>
                        </div>
                        <div class="p-4">
                            @if ($this->supplier->user)
                                <div class="flex items-center gap-3">
                                    <flux:avatar
                                        size="sm"
                                        src="{{ $this->supplier->user->avatar }}"
                                        name="{{ $this->supplier->user->fullName() }}"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $this->supplier->user->fullName() ?: $this->supplier->user->name }}
                                        </p>
                                        <p class="text-xs text-zinc-400">{{ $this->supplier->user->email }}</p>
                                        @if ($this->supplier->user->phone)
                                            <p class="text-xs text-zinc-400">{{ $this->supplier->user->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-zinc-400">—</p>
                            @endif
                        </div>
                    </div>

                    {{-- Business info --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Business') }}
                            </p>
                        </div>
                        <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Type') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->business_type ? ucfirst($this->supplier->business_type) : '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Registration') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->registration_number ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Tax') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->tax_number ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Commission') }}</span>
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $this->supplier->commission_rate }}%
                                </flux:badge>
                            </div>
                        </div>
                    </div>

                    {{-- Country --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Country') }}
                            </p>
                        </div>
                        <div class="p-4">
                            @if ($this->supplier->country)
                                <div class="flex items-center gap-3">
                                    @if ($this->supplier->country->flag_url)
                                        <img
                                            src="{{ $this->supplier->country->flag_url }}"
                                            alt="{{ $this->supplier->country->name }}"
                                            class="h-5 w-8 rounded-sm object-cover shadow-sm"
                                        />
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $this->supplier->country->name }}
                                        </p>
                                        <p class="text-xs text-zinc-400">
                                            {{ $this->supplier->country->code }}
                                            @if ($this->supplier->country->currency_code)
                                                · {{ $this->supplier->country->currency_code }}
                                                {{ $this->supplier->country->currency_symbol }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-zinc-400">—</p>
                            @endif
                        </div>
                    </div>

                    {{-- Approval info --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Approval') }}
                            </p>
                        </div>
                        <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Approved at') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->approved_at?->format('d M Y') ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Approved by') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->approvedBy?->fullName() ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="text-xs text-zinc-400">{{ __('Member since') }}</span>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->supplier->created_at->format('d M Y') }}
                                </span>
                            </div>
                            @if ($this->supplier->rejection_reason)
                                <div class="px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Rejection reason') }}</span>
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ $this->supplier->rejection_reason }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- Description --}}
                @if ($this->supplier->description)
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Description') }}
                            </p>
                        </div>
                        <div class="p-4">
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                {{ $this->supplier->description }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Documents --}}
                @if ($this->supplier->documents->isNotEmpty())
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                {{ __('Documents') }}
                                <flux:badge size="sm" color="zinc" inset="top bottom" class="ml-1">
                                    {{ $this->supplier->documents->count() }}
                                </flux:badge>
                            </p>
                        </div>
                        <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            @foreach ($this->supplier->documents as $document)
                                <div class="flex items-center gap-3 px-4 py-3">
                                    <flux:icon name="document-text" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="min-w-0 flex-1 truncate text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $document->name ?? $document->type ?? __('Document') }}
                                    </span>
                                    @if ($document->url ?? $document->file_path ?? null)

                                            href="{{ $document->url ?? $document->file_path }}"
                                            target="_blank"
                                            class="text-xs text-blue-500 hover:underline"
                                        >
                                            {{ __('View') }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Actions rapides --}}
                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <p class="mr-auto text-xs font-semibold uppercase tracking-wider text-zinc-400">
                        {{ __('Quick actions') }}
                    </p>
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="pencil-square"
                        wire:click="$dispatch('edit-supplier', { id: {{ $this->supplier->id }} })"
                    >
                        {{ __('Edit') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="check-circle"
                        wire:click="$dispatch('approve-supplier', { id: {{ $this->supplier->id }} })"
                    >
                        {{ __('Approve / Reject') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="star"
                        wire:click="$dispatch('toggle-featured', { id: {{ $this->supplier->id }} })"
                    >
                        {{ $this->supplier->is_featured ? __('Unfeature') : __('Feature') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        class="text-red-400 hover:text-red-500"
                        wire:click="$dispatch('delete-supplier', { id: {{ $this->supplier->id }} })"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>

            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon name="building-storefront" class="mb-3 size-10 text-zinc-300" />
                    <p class="text-sm text-zinc-400">{{ __('Loading supplier...') }}</p>
                </div>
            @endif

        </div>
    </flux:modal>
</div>
