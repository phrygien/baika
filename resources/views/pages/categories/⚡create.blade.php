<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Str;
use Flux\Flux;

new class extends Component
{
    use WithFileUploads;

    public ?int $preselectedParentId = null;

    public string $name        = '';
    public string $slug        = '';
    public string $description = '';
    public string $icon        = '';
    public $image              = null;   // fichier uploadé (TemporaryUploadedFile)
    public ?string $existingImage = null; // chemin déjà stocké
    public ?int  $parent_id    = null;
    public string $sort_order  = '0';
    public string $commission_rate = '';
    public bool  $is_active    = true;
    public bool  $is_featured  = false;

    // SEO
    public string $meta_title       = '';
    public string $meta_description = '';
    public string $meta_keywords    = '';

    // UI
    public string $parentSearch = '';
    public bool   $showSeo      = false;

    #[On('create-category')]
    public function open(?int $parentId = null): void
    {
        $this->reset();
        $this->is_active      = true;
        $this->sort_order     = '0';
        $this->image          = null;
        $this->existingImage  = null;

        if ($parentId) {
            $this->parent_id           = $parentId;
            $this->preselectedParentId = $parentId;
        }

        $this->resetValidation();
        Flux::modal('create-category')->show();
    }

    public function updatedName(string $value): void
    {
        $this->slug       = Str::slug($value);
        $this->meta_title = $value;
    }

    public function updatedImage(): void
    {
        $this->validate([
            'image' => 'image|max:2048|mimes:jpeg,png,webp',
        ]);
    }

    public function removeImage(): void
    {
        $this->image         = null;
        $this->existingImage = null;
    }

    #[Computed]
    public function parentResults()
    {
        return Category::query()
            ->when($this->parentSearch, fn($q) =>
                $q->where('name', 'like', "%{$this->parentSearch}%")
            )
            ->orderBy('depth')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function selectedParent()
    {
        if (!$this->parent_id) return null;
        return Category::find($this->parent_id);
    }

    public function clearParent(): void
    {
        $this->parent_id           = null;
        $this->preselectedParentId = null;
        $this->parentSearch        = '';
        unset($this->parentResults);
    }

    public function selectParent(int $id): void
    {
        $this->parent_id    = $id;
        $this->parentSearch = '';
        unset($this->parentResults);
        unset($this->selectedParent);
    }

    public function save(): void
    {
        $this->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:categories,slug',
            'description'      => 'nullable|string|max:5000',
            'icon'             => 'nullable|string|max:10',
            'image'            => 'nullable|image|max:2048|mimes:jpeg,png,webp',
            'parent_id'        => 'nullable|integer|exists:categories,id',
            'sort_order'       => 'required|integer|min:0',
            'commission_rate'  => 'nullable|numeric|min:0|max:100',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:500',
        ]);

        try {
            $depth = 0;
            $path  = '';

            if ($this->parent_id) {
                $parent = Category::findOrFail($this->parent_id);
                $depth  = ($parent->depth ?? 0) + 1;
                $path   = $parent->path;
            }

            // Stocker l'image si uploadée
            $imagePath = null;
            if ($this->image) {
                $imagePath = '/storage/' . $this->image->store('categories', 'public');
            }

            $category = Category::create([
                'parent_id'        => $this->parent_id,
                'name'             => $this->name,
                'slug'             => $this->slug,
                'description'      => $this->description ?: null,
                'icon'             => $this->icon ?: null,
                'image'            => $imagePath,
                'sort_order'       => (int) $this->sort_order,
                'commission_rate'  => $this->commission_rate !== '' ? $this->commission_rate : null,
                'is_active'        => $this->is_active,
                'is_featured'      => $this->is_featured,
                'depth'            => $depth,
                'path'             => $path,
                'meta_title'       => $this->meta_title ?: null,
                'meta_description' => $this->meta_description ?: null,
                'meta_keywords'    => $this->meta_keywords ?: null,
            ]);

            $category->update([
                'path' => $path ? $path . '/' . $category->id : (string) $category->id,
            ]);

            $this->dispatch('category-created');
            $this->dispatch('notify', variant: 'success',
                title: __('Category created'),
                message: __(':name has been created successfully.', ['name' => $this->name]),
            );

            Flux::modal('create-category')->close();

        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'warning',
                title: __('Creation failed'),
                message: __('An error occurred while creating the category.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="create-category" class="w-full max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Add Category') }}</flux:heading>
                    <flux:text class="mt-2">
                        @if ($this->selectedParent)
                            {{ __('Creating subcategory under') }}
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $this->selectedParent->name }}</span>
                        @else
                            {{ __('Create a new root category or subcategory.') }}
                        @endif
                    </flux:text>
                </div>

                {{-- Informations --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Information') }}</p>
                    </div>
                    <div class="space-y-4 p-4">

                        {{-- Preview + Name --}}
                        <div class="flex items-start gap-4">

                            {{-- Preview carré --}}
                            <div class="relative shrink-0">
                                <div class="flex size-20 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                    @if ($image)
                                        <img src="{{ $image->temporaryUrl() }}" class="size-full object-cover" alt="" />
                                    @elseif ($icon)
                                        <span class="text-3xl">{{ $icon }}</span>
                                    @else
                                        <flux:icon name="tag" class="size-7 text-zinc-300" />
                                    @endif
                                </div>

                                {{-- Bouton supprimer image --}}
                                @if ($image)
                                    <button
                                        type="button"
                                        wire:click="removeImage"
                                        class="absolute -right-2 -top-2 flex size-5 items-center justify-center rounded-full bg-red-500 text-white shadow-sm hover:bg-red-600"
                                    >
                                        <flux:icon name="x-mark" class="size-3" />
                                    </button>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <flux:input wire:model.live="name" label="{{ __('Name') }}" placeholder="Electronics" />
                                <flux:input wire:model="slug" label="{{ __('Slug') }}" placeholder="electronics" description="{{ __('Auto-generated from name') }}" />
                            </div>
                        </div>

                        {{-- Upload image + icon --}}
                        <div class="grid grid-cols-2 gap-4">

                            {{-- Upload image --}}
                            <div>
                                <flux:label>{{ __('Image') }}</flux:label>
                                <div class="mt-1.5">
                                    @if ($image)
                                        {{-- Image chargée : aperçu + bouton changer --}}
                                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                                            <img src="{{ $image->temporaryUrl() }}" class="size-10 rounded-lg object-cover" alt="" />
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ $image->getClientOriginalName() }}
                                                </p>
                                                <p class="text-xs text-zinc-400">
                                                    {{ number_format($image->getSize() / 1024, 1) }} KB
                                                </p>
                                            </div>
                                            <button type="button" wire:click="removeImage" class="shrink-0 text-zinc-400 hover:text-red-500">
                                                <flux:icon name="trash" class="size-4" />
                                            </button>
                                        </div>
                                    @else
                                        {{-- Zone drop / browse --}}
                                        <label
                                            x-data="{ dragging: false }"
                                            x-on:dragover.prevent="dragging = true"
                                            x-on:dragleave.prevent="dragging = false"
                                            x-on:drop.prevent="dragging = false; $wire.upload('image', $event.dataTransfer.files[0])"
                                            :class="dragging ? 'border-blue-400 bg-blue-50 dark:bg-blue-950/10' : 'border-zinc-200 dark:border-zinc-700'"
                                            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-4 transition hover:border-zinc-300 dark:hover:border-zinc-600"
                                        >
                                            <input
                                                type="file"
                                                wire:model="image"
                                                accept="image/jpeg,image/png,image/webp"
                                                class="hidden"
                                            />
                                            <flux:icon name="cloud-arrow-up" class="mb-1 size-6 text-zinc-300" />
                                            <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('Drop or click to browse') }}
                                            </p>
                                            <p class="mt-0.5 text-center text-xs text-zinc-400">
                                                JPG, PNG, WEBP · max 2MB
                                            </p>
                                        </label>
                                    @endif

                                    {{-- Barre de progression upload --}}
                                    <div wire:loading wire:target="image" class="mt-2 flex items-center gap-2 text-xs text-zinc-500">
                                        <flux:icon name="arrow-path" class="size-3 animate-spin text-blue-500" />
                                        {{ __('Uploading...') }}
                                    </div>

                                    <flux:error name="image" />
                                </div>
                            </div>

                            {{-- Icon emoji --}}
                            <div>
                                <flux:input
                                    wire:model="icon"
                                    label="{{ __('Icon (emoji)') }}"
                                    placeholder="🔌"
                                    description="{{ __('Single emoji character') }}"
                                />
                                <p class="mt-1.5 text-xs text-zinc-400">{{ __('Icon is shown when no image is set.') }}</p>
                            </div>
                        </div>

                        <flux:textarea
                            wire:model="description"
                            label="{{ __('Description') }}"
                            placeholder="{{ __('Describe this category...') }}"
                            rows="2"
                        />

                    </div>
                </div>

                {{-- Parent --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Parent category') }}</p>
                        @if ($this->selectedParent)
                            <flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearParent">{{ __('Remove parent') }}</flux:button>
                        @endif
                    </div>
                    <div class="space-y-2 p-4">
                        @if ($this->selectedParent)
                            <div class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950/20">
                                <div class="flex size-8 items-center justify-center rounded-lg bg-white text-lg shadow-sm dark:bg-zinc-800">{{ $this->selectedParent->icon ?? '📁' }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->selectedParent->name }}</p>
                                    <p class="text-xs text-zinc-400">{{ __('Depth') }} {{ $this->selectedParent->depth ?? 0 }} · {{ $this->selectedParent->slug }}</p>
                                </div>
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ __('Level') }} {{ ($this->selectedParent->depth ?? 0) + 1 }}</flux:badge>
                            </div>
                        @else
                            <flux:input wire:model.live.debounce.200ms="parentSearch" icon="magnifying-glass" placeholder="{{ __('Search parent category...') }}" size="sm" />
                            @if (strlen($parentSearch) >= 1)
                                <div class="max-h-40 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    @forelse ($this->parentResults as $cat)
                                        <button type="button" wire:key="parent-{{ $cat->id }}" wire:click="selectParent({{ $cat->id }})"
                                            class="flex w-full items-center gap-3 border-b border-zinc-50 px-4 py-2.5 text-left last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/30">
                                            <div class="flex size-7 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-base dark:bg-zinc-800">{{ $cat->icon ?? '📁' }}</div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $cat->name }}</p>
                                                <p class="text-xs text-zinc-400">{{ $cat->slug }}</p>
                                            </div>
                                            <flux:badge size="sm" color="zinc" inset="top bottom">L{{ $cat->depth ?? 0 }}</flux:badge>
                                        </button>
                                    @empty
                                        <div class="flex items-center justify-center py-4">
                                            <p class="text-sm text-zinc-400">{{ __('No categories found.') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <p class="text-xs text-zinc-400">{{ __('Leave empty to create a root category, or search to select a parent.') }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Settings --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Settings') }}</p>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="commission_rate" label="{{ __('Commission rate') }}" placeholder="10" type="number" min="0" max="100" step="0.5" description="{{ __('Optional, overrides parent (%)') }}" />
                            <flux:input wire:model="sort_order" label="{{ __('Sort order') }}" placeholder="0" type="number" min="0" />
                        </div>
                        <div class="flex items-center gap-6">
                            <flux:field variant="inline">
                                <flux:label>{{ __('Active') }}</flux:label>
                                <flux:switch wire:model="is_active" />
                            </flux:field>
                            <flux:field variant="inline">
                                <flux:label>{{ __('Featured') }}</flux:label>
                                <flux:switch wire:model="is_featured" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- SEO (collapsible) --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <button type="button" wire:click="$toggle('showSeo')" class="flex w-full items-center justify-between px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('SEO') }}</p>
                        <flux:icon name="{{ $showSeo ? 'chevron-up' : 'chevron-down' }}" class="size-4 text-zinc-400" />
                    </button>
                    @if ($showSeo)
                        <div class="space-y-4 border-t border-zinc-100 p-4 dark:border-zinc-800">
                            <flux:input wire:model="meta_title" label="{{ __('Meta title') }}" placeholder="{{ $name ?: __('Category name') }}" />
                            <flux:textarea wire:model="meta_description" label="{{ __('Meta description') }}" placeholder="{{ __('Brief description for search engines...') }}" rows="2" />
                            <flux:input wire:model="meta_keywords" label="{{ __('Meta keywords') }}" placeholder="{{ __('keyword1, keyword2, keyword3') }}" description="{{ __('Comma separated') }}" />
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="plus">
                        {{ __('Create Category') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
