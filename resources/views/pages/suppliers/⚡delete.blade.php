<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Supplier;
use Flux\Flux;

new class extends Component
{
    public ?int $supplierId = null;
    public string $shopName = '';
    public string $ownerName = '';
    public int $totalProducts = 0;
    public int $totalSales = 0;

    #[On('delete-supplier')]
    public function loadSupplier(int $id): void
    {
        $supplier = Supplier::with('user')->findOrFail($id);

        $this->supplierId    = $supplier->id;
        $this->shopName      = $supplier->shop_name;
        $this->ownerName     = $supplier->user?->fullName() ?: ($supplier->user?->name ?? '');
        $this->totalProducts = $supplier->total_products ?? 0;
        $this->totalSales    = $supplier->total_sales ?? 0;

        Flux::modal('delete-supplier')->show();
    }

    public function delete(): void
    {
        try {
            Supplier::findOrFail($this->supplierId)->delete();

            $this->dispatch('supplier-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Supplier deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->shopName]),
            );

            Flux::modal('delete-supplier')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the supplier.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-supplier" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete supplier?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $shopName }}</span>
                        @if ($ownerName)
                            <span class="text-zinc-400"> · {{ $ownerName }}</span>
                        @endif
                        .<br>
                        {{ __('The supplier will be soft deleted and can be restored later.') }}
                    </flux:text>
                </div>

                {{-- Stats warning --}}
                @if ($totalProducts > 0 || $totalSales > 0)
                    <div class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                        <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                        <div class="text-sm text-yellow-700 dark:text-yellow-400">
                            <p class="font-medium">{{ __('This supplier has active data:') }}</p>
                            <ul class="mt-1 space-y-0.5">
                                @if ($totalProducts > 0)
                                    <li>· {{ $totalProducts }} {{ __('products') }}</li>
                                @endif
                                @if ($totalSales > 0)
                                    <li>· {{ $totalSales }} {{ __('sales') }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete supplier') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
