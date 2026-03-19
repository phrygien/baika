<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Product;
use Flux\Flux;

new class extends Component
{
    public ?int $productId    = null;
    public string $productName = '';
    public string $productSku  = '';
    public int $imagesCount    = 0;
    public int $variantsCount  = 0;
    public int $totalSold      = 0;

    #[On('delete-product')]
    public function loadProduct(int $id): void
    {
        $product = Product::withCount(['images', 'variants'])
            ->findOrFail($id);

        $this->productId    = $product->id;
        $this->productName  = $product->name;
        $this->productSku   = $product->sku ?? '';
        $this->imagesCount  = $product->images_count;
        $this->variantsCount = $product->variants_count;
        $this->totalSold    = $product->total_sold ?? 0;

        Flux::modal('delete-product')->show();
    }

    public function delete(): void
    {
        try {
            Product::findOrFail($this->productId)->delete();

            $this->dispatch('product-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Product deleted'),
                message: __(':name has been moved to trash.', ['name' => $this->productName]),
            );

            Flux::modal('delete-product')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the product.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-product" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete product?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __("You're about to delete") }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $productName }}</span>
                        @if ($productSku)
                            <span class="font-mono text-xs text-zinc-400"> · {{ $productSku }}</span>
                        @endif
                        .<br>
                        {{ __('The product will be soft deleted and can be restored from trash.') }}
                    </flux:text>
                </div>

                {{-- Warnings --}}
                @if ($totalSold > 0 || $variantsCount > 0 || $imagesCount > 0)
                    <div class="space-y-2">
                        @if ($totalSold > 0)
                            <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-red-600 dark:text-red-400" />
                                <p class="text-sm text-red-700 dark:text-red-400">
                                    {{ __('This product has') }}
                                    <span class="font-semibold">{{ number_format($totalSold) }}</span>
                                    {{ __('units sold. Past orders will not be affected.') }}
                                </p>
                            </div>
                        @endif

                        @if ($variantsCount > 0)
                            <div class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                                <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                    {{ __('This product has') }}
                                    <span class="font-semibold">{{ $variantsCount }}</span>
                                    {{ __('variants that will also be deleted.') }}
                                </p>
                            </div>
                        @endif

                        @if ($imagesCount > 0)
                            <div class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <flux:icon name="photo" class="mt-0.5 size-4 shrink-0 text-zinc-500" />
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ __('This product has') }}
                                    <span class="font-semibold">{{ $imagesCount }}</span>
                                    {{ __('images linked to it.') }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete product') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
