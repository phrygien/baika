<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Brand;
use Flux\Flux;

new class extends Component
{
    public ?int $brandId = null;
    public string $brandName = '';
    public string $brandLogo = '';
    public int $productsCount = 0;

    #[On('delete-brand')]
    public function loadBrand(int $id): void
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        $this->brandId      = $brand->id;
        $this->brandName    = $brand->name;
        $this->brandLogo    = $brand->logo ?? '';
        $this->productsCount = $brand->products_count;

        Flux::modal('delete-brand')->show();
    }

    public function delete(): void
    {
        try {
            Brand::findOrFail($this->brandId)->delete();

            $this->dispatch('brand-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Brand deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->brandName]),
            );

            Flux::modal('delete-brand')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the brand.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-brand" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete brand?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $brandName }}</span>.
                        <br>
                        {{ __('This action cannot be reversed.') }}
                    </flux:text>
                </div>

                {{-- Warning produits --}}
                @if ($productsCount > 0)
                    <div class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                        <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                        <p class="text-sm text-yellow-700 dark:text-yellow-400">
                            {{ __('This brand has') }}
                            <span class="font-semibold">{{ $productsCount }}</span>
                            {{ __('products linked to it.') }}
                        </p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete brand') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
