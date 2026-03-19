<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Category;
use Flux\Flux;

new class extends Component
{
    public ?int $categoryId = null;
    public string $categoryName = '';
    public string $categoryIcon = '';
    public int $childrenCount = 0;
    public int $productsCount = 0;

    #[On('delete-category')]
    public function loadCategory(int $id): void
    {
        $category = Category::withCount(['children', 'products'])->findOrFail($id);

        $this->categoryId    = $category->id;
        $this->categoryName  = $category->name;
        $this->categoryIcon  = $category->icon ?? '';
        $this->childrenCount = $category->children_count;
        $this->productsCount = $category->products_count;

        Flux::modal('delete-category')->show();
    }

    public function delete(): void
    {
        try {
            Category::findOrFail($this->categoryId)->delete();

            $this->dispatch('category-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Category deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->categoryName]),
            );

            Flux::modal('delete-category')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the category.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-category" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete category?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        @if ($categoryIcon)
                            <span class="mx-1">{{ $categoryIcon }}</span>
                        @endif
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $categoryName }}</span>.
                        <br>
                        {{ __('This action cannot be reversed.') }}
                    </flux:text>
                </div>

                {{-- Warnings --}}
                @if ($childrenCount > 0 || $productsCount > 0)
                    <div class="space-y-2">
                        @if ($childrenCount > 0)
                            <div class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                                <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                    {{ __('This category has') }}
                                    <span class="font-semibold">{{ $childrenCount }}</span>
                                    {{ __('subcategories that will also be deleted.') }}
                                </p>
                            </div>
                        @endif

                        @if ($productsCount > 0)
                            <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-red-600 dark:text-red-400" />
                                <p class="text-sm text-red-700 dark:text-red-400">
                                    {{ __('This category has') }}
                                    <span class="font-semibold">{{ $productsCount }}</span>
                                    {{ __('products linked to it.') }}
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
                        {{ __('Delete category') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
