<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Category;

new class extends Component
{
    #[Computed]
    public function categories()
    {
        return Category::query()
            ->roots()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(5)
            ->get();
    }
};
?>

<div class="bg-white">
    <div class="py-16 sm:py-24 xl:mx-auto xl:max-w-7xl xl:px-8">
        <div class="px-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8 xl:px-0">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ __('Shop by Category') }}
            </h2>
            <a href="#" class="hidden text-sm font-semibold text-indigo-600 hover:text-indigo-500 sm:block">
                {{ __('Browse all categories') }} <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="mt-4 flow-root">
            <div class="-my-2">
                <div class="relative box-content h-80 overflow-x-auto py-2 xl:overflow-visible">
                    <div class="absolute flex space-x-8 px-4 sm:px-6 lg:px-8 xl:relative xl:grid xl:grid-cols-5 xl:gap-x-8 xl:space-x-0 xl:px-0">

                        @forelse ($this->categories as $category)

                                href="#"
                                class="relative flex h-80 w-56 flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 xl:w-auto"
                            >
                                <span aria-hidden="true" class="absolute inset-0">
                                    @if ($category->image)
                                        <img
                                            src="{{ asset($category->image) }}"
                                            alt="{{ $category->name }}"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        />
                                    @else
                                        <div class="flex size-full items-center justify-center bg-zinc-200">
                                            <span class="text-6xl">{{ $category->icon ?? '📁' }}</span>
                                        </div>
                                    @endif
                                </span>
                                <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-800 opacity-50"></span>
                                <span class="relative mt-auto text-center text-xl font-bold text-white">
                                    {{ $category->name }}
                                </span>
                            </a>
                        @empty
                            <p class="px-4 text-sm text-gray-500">{{ __('No categories available.') }}</p>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 px-4 sm:hidden">
            <a href="#" class="block text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                {{ __('Browse all categories') }} <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</div>
