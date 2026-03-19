<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Transition;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Flux\Flux;

new class extends Component
{
    use WithFileUploads;

    // ── Stepper ────────────────────────────────────────────────────────────
    public int $step       = 1;
    public int $totalSteps = 5;

    // ── Product ID ─────────────────────────────────────────────────────────
    public ?int $productId = null;

    // ── Step 1 : Informations ──────────────────────────────────────────────
    public string $name              = '';
    public string $slug              = '';
    public string $sku               = '';
    public string $short_description = '';
    public string $description       = '';
    public ?int   $category_id       = null;
    public ?int   $brand_id          = null;
    public ?int   $supplier_id       = null;
    public string $status            = '';
    public bool   $is_featured       = false;
    public bool   $is_active         = true;
    public string $categorySearch    = '';
    public string $brandSearch       = '';
    public string $supplierSearch    = '';

    // ── Step 2 : Pricing ───────────────────────────────────────────────────
    public string $base_price          = '';
    public string $compare_at_price    = '';
    public string $cost_price          = '';
    public string $currency            = 'USD';
    public bool   $track_inventory     = true;
    public string $low_stock_threshold = '5';
    public bool   $requires_shipping   = true;
    public bool   $is_digital          = false;
    public string $weight_kg           = '';
    public string $origin_country      = '';
    public string $barcode             = '';
    public string $hs_code             = '';

    // ── Step 3 : Images ────────────────────────────────────────────────────
    public array $existingImages    = []; // Images déjà en DB
    public array $uploadedImages    = []; // Nouvelles images uploadées
    public $newImages               = [];
    public int   $primaryImageIndex = 0;
    public array $deletedImageIds   = [];

    // ── Step 4 : Variants ──────────────────────────────────────────────────
    public bool  $hasVariants       = false;
    public array $variants          = [];
    public array $deletedVariantIds = [];

    // ── Step 5 : SEO ───────────────────────────────────────────────────────
    public string $meta_title       = '';
    public string $meta_description = '';
    public string $meta_keywords    = '';

    protected array $statusEnumValues = [];

    protected function getStatusEnumValues(): array
    {
        if (!empty($this->statusEnumValues)) return $this->statusEnumValues;

        try {
            $result = \DB::select("SHOW COLUMNS FROM products LIKE 'status'");
            if (!empty($result)) {
                $type = $result[0]->Type ?? '';
                preg_match("/^enum\((.+)\)$/", $type, $matches);
                if (!empty($matches[1])) {
                    $this->statusEnumValues = array_map(
                        fn($v) => trim($v, "'"),
                        explode(',', $matches[1])
                    );
                    return $this->statusEnumValues;
                }
            }
        } catch (\Exception $e) {}

        return ['approved', 'rejected'];
    }

    #[On('edit-product')]
    public function loadProduct(int $id): void
    {
        $product = Product::with(['images', 'variants'])->findOrFail($id);

        $this->productId = $product->id;
        $this->step      = 1;

        // Step 1
        $this->name              = $product->name;
        $this->slug              = $product->slug;
        $this->sku               = $product->sku ?? '';
        $this->short_description = $product->short_description ?? '';
        $this->description       = $product->description ?? '';
        $this->category_id       = $product->category_id;
        $this->brand_id          = $product->brand_id;
        $this->supplier_id       = $product->supplier_id;
        $this->status            = $product->status;
        $this->is_featured       = $product->is_featured;
        $this->is_active         = $product->is_active;

        // Step 2
        $this->base_price          = (string) $product->base_price;
        $this->compare_at_price    = $product->compare_at_price ? (string) $product->compare_at_price : '';
        $this->cost_price          = $product->cost_price ? (string) $product->cost_price : '';
        $this->currency            = $product->currency ?? 'USD';
        $this->track_inventory     = $product->track_inventory;
        $this->low_stock_threshold = (string) ($product->low_stock_threshold ?? 5);
        $this->requires_shipping   = $product->requires_shipping;
        $this->is_digital          = $product->is_digital;
        $this->weight_kg           = $product->weight_kg ? (string) $product->weight_kg : '';
        $this->origin_country      = $product->origin_country ?? '';
        $this->barcode             = $product->barcode ?? '';
        $this->hs_code             = $product->hs_code ?? '';

        // Step 3 — images existantes
        $this->existingImages  = $product->images->map(fn($img) => [
            'id'         => $img->id,
            'image_path' => $img->image_path,
            'alt_text'   => $img->alt_text ?? '',
            'sort_order' => $img->sort_order,
            'is_primary' => $img->is_primary,
        ])->toArray();
        $this->uploadedImages  = [];
        $this->newImages       = [];
        $this->deletedImageIds = [];
        $primaryIdx = collect($this->existingImages)->search(fn($img) => $img['is_primary']);
        $this->primaryImageIndex = $primaryIdx !== false ? $primaryIdx : 0;

        // Step 4 — variants existantes
        $this->variants = $product->variants->map(fn($v) => [
            'id'               => $v->id,
            'name'             => $v->name,
            'sku'              => $v->sku ?? '',
            'price'            => (string) $v->price,
            'compare_at_price' => $v->compare_at_price ? (string) $v->compare_at_price : '',
            'cost_price'       => $v->cost_price ? (string) $v->cost_price : '',
            'weight_kg'        => $v->weight_kg ? (string) $v->weight_kg : '',
            'barcode'          => $v->barcode ?? '',
            'is_active'        => $v->is_active,
            'is_new'           => false,
        ])->toArray();
        $this->hasVariants       = !empty($this->variants);
        $this->deletedVariantIds = [];

        // Step 5
        $this->meta_title       = $product->meta_title ?? '';
        $this->meta_description = $product->meta_description ?? '';
        $this->meta_keywords    = $product->meta_keywords ?? '';

        $this->categorySearch = '';
        $this->brandSearch    = '';
        $this->supplierSearch = '';

        $this->resetValidation();
        Flux::modal('edit-product')->show();
    }

    // ── Navigation ─────────────────────────────────────────────────────────

    #[Transition(type: 'forward')]
    public function next(): void
    {
        $this->validateStep($this->step);
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    #[Transition(type: 'backward')]
    public function previous(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function goToStep(int $target): void
    {
        if ($target < $this->step) {
            $this->transition(type: 'backward');
            $this->step = $target;
        } elseif ($target > $this->step) {
            try {
                $this->validateStep($this->step);
                $this->transition(type: 'forward');
                $this->step = $target;
            } catch (\Illuminate\Validation\ValidationException $e) {}
        }
    }

    protected function validateStep(int $step): void
    {
        $validStatuses = implode(',', $this->getStatusEnumValues());

        match($step) {
            1 => $this->validate([
                'name'        => "required|string|max:255",
                'slug'        => "required|string|max:255|unique:products,slug,{$this->productId}",
                'supplier_id' => 'required|integer|exists:suppliers,id',
                'category_id' => 'nullable|integer|exists:categories,id',
                'brand_id'    => 'nullable|integer|exists:brands,id',
                'status'      => "required|in:{$validStatuses}",
            ]),
            2 => $this->validate([
                'base_price'          => 'required|numeric|min:0',
                'compare_at_price'    => 'nullable|numeric|min:0',
                'cost_price'          => 'nullable|numeric|min:0',
                'currency'            => 'required|string|size:3',
                'low_stock_threshold' => 'required|integer|min:0',
            ]),
            3 => $this->validate([
                'uploadedImages.*' => 'nullable|image|max:5120',
            ]),
            4 => $this->validateVariants(),
            default => null,
        };
    }

    protected function validateVariants(): void
    {
        if (!$this->hasVariants || empty($this->variants)) return;

        foreach ($this->variants as $i => $variant) {
            $this->validate([
                "variants.{$i}.name"  => 'required|string|max:255',
                "variants.{$i}.price" => 'required|numeric|min:0',
            ]);
        }
    }

    // ── Step 1 helpers ─────────────────────────────────────────────────────

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function generateSku(): void
    {
        $this->sku = strtoupper(Str::random(3) . '-' . rand(1000, 9999));
    }

    #[Computed]
    public function statusOptions(): array
    {
        $labels = [
            'draft'     => __('Draft'),
            'pending'   => __('Pending'),
            'approved'  => __('Approved'),
            'rejected'  => __('Rejected'),
            'suspended' => __('Suspended'),
        ];

        return collect($this->getStatusEnumValues())
            ->mapWithKeys(fn($v) => [$v => $labels[$v] ?? ucfirst($v)])
            ->toArray();
    }

    #[Computed]
    public function categoryResults()
    {
        if (strlen($this->categorySearch) < 1) return collect();
        return Category::where('name', 'like', "%{$this->categorySearch}%")
            ->orderBy('depth')->orderBy('name')->limit(15)->get(['id', 'name', 'depth', 'icon']);
    }

    #[Computed]
    public function brandResults()
    {
        return Brand::when($this->brandSearch, fn($q) =>
            $q->where('name', 'like', "%{$this->brandSearch}%")
        )->orderBy('name')->limit(15)->get(['id', 'name', 'logo']);
    }

    #[Computed]
    public function supplierResults()
    {
        return Supplier::approved()
            ->when($this->supplierSearch, fn($q) =>
                $q->where('shop_name', 'like', "%{$this->supplierSearch}%")
            )->orderBy('shop_name')->limit(15)->get(['id', 'shop_name', 'logo']);
    }

    #[Computed]
    public function selectedCategory()
    {
        return $this->category_id ? Category::find($this->category_id) : null;
    }

    #[Computed]
    public function selectedBrand()
    {
        return $this->brand_id ? Brand::find($this->brand_id) : null;
    }

    #[Computed]
    public function selectedSupplier()
    {
        return $this->supplier_id ? Supplier::find($this->supplier_id) : null;
    }

    // ── Step 3 helpers ─────────────────────────────────────────────────────

    public function updatedNewImages(): void
    {
        $this->validate(['newImages.*' => 'image|max:5120']);

        foreach ((array) $this->newImages as $image) {
            $totalImages = count($this->existingImages) + count($this->uploadedImages);
            if ($totalImages >= 10) break;
            $this->uploadedImages[] = $image;
        }

        $this->newImages = [];
    }

    public function removeExistingImage(int $index): void
    {
        $image = $this->existingImages[$index] ?? null;
        if ($image) {
            $this->deletedImageIds[] = $image['id'];
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
        }
    }

    public function removeNewImage(int $index): void
    {
        unset($this->uploadedImages[$index]);
        $this->uploadedImages = array_values($this->uploadedImages);
    }

    public function setPrimaryExisting(int $index): void
    {
        // Marque l'image existante comme primary
        foreach ($this->existingImages as $i => $img) {
            $this->existingImages[$i]['is_primary'] = ($i === $index);
        }
        foreach ($this->uploadedImages as $i => $img) {
            $this->primaryImageIndex = -1;
        }
    }

    public function setPrimaryNew(int $index): void
    {
        foreach ($this->existingImages as $i => $img) {
            $this->existingImages[$i]['is_primary'] = false;
        }
        $this->primaryImageIndex = $index;
    }

    // ── Step 4 helpers ─────────────────────────────────────────────────────

    public function addVariant(): void
    {
        $this->variants[] = [
            'id'               => null,
            'name'             => '',
            'sku'              => '',
            'price'            => $this->base_price,
            'compare_at_price' => '',
            'cost_price'       => '',
            'weight_kg'        => '',
            'barcode'          => '',
            'is_active'        => true,
            'is_new'           => true,
        ];
    }

    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index] ?? null;
        if ($variant && !empty($variant['id'])) {
            $this->deletedVariantIds[] = $variant['id'];
        }
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function duplicateVariant(int $index): void
    {
        $variant         = $this->variants[$index];
        $variant['id']   = null;
        $variant['sku']  = '';
        $variant['is_new'] = true;
        $this->variants[] = $variant;
    }

    // ── Save ───────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:500',
        ]);

        try {
            $product = Product::findOrFail($this->productId);

            $product->update([
                'supplier_id'         => $this->supplier_id,
                'category_id'         => $this->category_id,
                'brand_id'            => $this->brand_id,
                'name'                => $this->name,
                'slug'                => $this->slug,
                'sku'                 => $this->sku ?: null,
                'short_description'   => $this->short_description ?: null,
                'description'         => $this->description ?: null,
                'base_price'          => $this->base_price,
                'compare_at_price'    => $this->compare_at_price ?: null,
                'cost_price'          => $this->cost_price ?: null,
                'currency'            => $this->currency,
                'weight_kg'           => $this->weight_kg ?: null,
                'requires_shipping'   => $this->requires_shipping,
                'is_digital'          => $this->is_digital,
                'status'              => $this->status,
                'is_featured'         => $this->is_featured,
                'is_active'           => $this->is_active,
                'track_inventory'     => $this->track_inventory,
                'low_stock_threshold' => (int) $this->low_stock_threshold,
                'origin_country'      => $this->origin_country ?: null,
                'barcode'             => $this->barcode ?: null,
                'hs_code'             => $this->hs_code ?: null,
                'meta_title'          => $this->meta_title ?: $this->name,
                'meta_description'    => $this->meta_description ?: null,
                'meta_keywords'       => $this->meta_keywords ?: null,
            ]);

            // ── Images : supprimer les marquées ────────────────────────────
            if (!empty($this->deletedImageIds)) {
                ProductImage::whereIn('id', $this->deletedImageIds)
                    ->where('product_id', $product->id)
                    ->delete();
            }

            // ── Images : mettre à jour is_primary des existantes ───────────
            foreach ($this->existingImages as $img) {
                ProductImage::where('id', $img['id'])->update([
                    'is_primary' => $img['is_primary'],
                    'alt_text'   => $img['alt_text'] ?: $product->name,
                ]);
            }

            // ── Images : ajouter les nouvelles ─────────────────────────────
            $currentCount = count($this->existingImages);
            foreach ($this->uploadedImages as $i => $image) {
                $path = $image->store('products', 'public');
                $isPrimary = (empty($this->existingImages))
                    && ($i === $this->primaryImageIndex);

                ProductImage::create([
                    'product_id'  => $product->id,
                    'image_path'  => '/storage/' . $path,
                    'alt_text'    => $product->name,
                    'sort_order'  => $currentCount + $i,
                    'is_primary'  => $isPrimary,
                ]);
            }

            // ── Variants : supprimer les marquées ─────────────────────────
            if (!empty($this->deletedVariantIds)) {
                ProductVariant::whereIn('id', $this->deletedVariantIds)
                    ->where('product_id', $product->id)
                    ->delete();
            }

            // ── Variants : mettre à jour / créer ──────────────────────────
            foreach ($this->variants as $i => $variant) {
                if (!empty($variant['id'])) {
                    // Mettre à jour existante
                    ProductVariant::where('id', $variant['id'])->update([
                        'name'             => $variant['name'],
                        'sku'              => $variant['sku'] ?: null,
                        'price'            => $variant['price'],
                        'compare_at_price' => $variant['compare_at_price'] ?: null,
                        'cost_price'       => $variant['cost_price'] ?: null,
                        'weight_kg'        => $variant['weight_kg'] ?: null,
                        'barcode'          => $variant['barcode'] ?: null,
                        'sort_order'       => $i,
                        'is_active'        => $variant['is_active'] ?? true,
                    ]);
                } else {
                    // Créer nouvelle
                    ProductVariant::create([
                        'product_id'       => $product->id,
                        'name'             => $variant['name'],
                        'sku'              => $variant['sku'] ?: null,
                        'price'            => $variant['price'],
                        'compare_at_price' => $variant['compare_at_price'] ?: null,
                        'cost_price'       => $variant['cost_price'] ?: null,
                        'weight_kg'        => $variant['weight_kg'] ?: null,
                        'barcode'          => $variant['barcode'] ?: null,
                        'sort_order'       => $i,
                        'is_active'        => $variant['is_active'] ?? true,
                    ]);
                }
            }

            $this->dispatch('product-updated');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Product updated'),
                message: __(':name has been updated successfully.', ['name' => $this->name]),
            );

            Flux::modal('edit-product')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Update failed'),
                message: $e->getMessage(),
            );
        }
    }
};
?>

<div>
    <style>
        html:active-view-transition-type(forward) {
            &::view-transition-old(stepper-content-edit) {
                animation: 280ms cubic-bezier(.4,0,.2,1) both edit-slide-out-left;
            }
            &::view-transition-new(stepper-content-edit) {
                animation: 280ms cubic-bezier(.4,0,.2,1) both edit-slide-in-right;
            }
        }
        html:active-view-transition-type(backward) {
            &::view-transition-old(stepper-content-edit) {
                animation: 280ms cubic-bezier(.4,0,.2,1) both edit-slide-out-right;
            }
            &::view-transition-new(stepper-content-edit) {
                animation: 280ms cubic-bezier(.4,0,.2,1) both edit-slide-in-left;
            }
        }
        @keyframes edit-slide-out-left  { from { transform: translateX(0);    opacity: 1; } to { transform: translateX(-40px); opacity: 0; } }
        @keyframes edit-slide-in-right  { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0);    opacity: 1; } }
        @keyframes edit-slide-out-right { from { transform: translateX(0);    opacity: 1; } to { transform: translateX(40px); opacity: 0; } }
        @keyframes edit-slide-in-left   { from { transform: translateX(-40px);opacity: 0; } to { transform: translateX(0);    opacity: 1; } }
    </style>

    <flux:modal name="edit-product" class="w-full max-w-3xl">
        <div class="flex flex-col gap-0">

            {{-- ── Stepper header ── --}}
            <div class="mb-6">
                <div class="mb-4 flex items-center justify-between pr-8">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Edit Product') }}</flux:heading>
                        @if ($name)
                            <p class="mt-0.5 truncate text-sm text-zinc-400">{{ $name }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-zinc-400">{{ __('Step') }} {{ $step }}/{{ $totalSteps }}</span>
                </div>

                <div class="flex items-center">
                    @php
                        $steps = [
                            1 => ['label' => __('Info'),     'icon' => 'information-circle'],
                            2 => ['label' => __('Pricing'),  'icon' => 'currency-dollar'],
                            3 => ['label' => __('Images'),   'icon' => 'photo'],
                            4 => ['label' => __('Variants'), 'icon' => 'squares-2x2'],
                            5 => ['label' => __('SEO'),      'icon' => 'magnifying-glass'],
                        ];
                    @endphp

                    @foreach ($steps as $num => $info)
                        <div class="flex flex-1 items-center">
                            <button type="button" wire:click="goToStep({{ $num }})" class="flex flex-col items-center gap-1">
                                <div class="flex size-8 items-center justify-center rounded-full border-2 transition-all
                                    {{ $step === $num
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : ($step > $num
                                            ? 'border-blue-500 bg-blue-50 text-blue-600 dark:bg-blue-950/30'
                                            : 'border-zinc-300 bg-white text-zinc-400 dark:border-zinc-600 dark:bg-zinc-900') }}"
                                >
                                    @if ($step > $num)
                                        <flux:icon name="check" class="size-4" />
                                    @else
                                        <flux:icon name="{{ $info['icon'] }}" class="size-3.5" />
                                    @endif
                                </div>
                                <span class="text-xs font-medium
                                    {{ $step === $num ? 'text-blue-600 dark:text-blue-400' : ($step > $num ? 'text-blue-500' : 'text-zinc-400') }}">
                                    {{ $info['label'] }}
                                </span>
                            </button>
                            @if (!$loop->last)
                                <div class="mx-1 mb-4 h-0.5 flex-1 rounded transition-colors
                                    {{ $step > $num ? 'bg-blue-500' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Step content ── --}}
            <div wire:transition="stepper-content-edit" class="min-h-[400px]">

                {{-- ═══ STEP 1 : Informations ═══ --}}
                @if ($step === 1)
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model.live="name" label="{{ __('Product name') }} *" placeholder="Amazing Product" />
                            <flux:input wire:model="slug" label="{{ __('Slug') }}" placeholder="amazing-product" description="{{ __('Auto-generated from name') }}" />
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <flux:input wire:model="sku" label="{{ __('SKU') }}" placeholder="ABC-1234" />
                            </div>
                            <flux:button type="button" variant="ghost" size="sm" icon="arrow-path" wire:click="generateSku">
                                {{ __('Generate') }}
                            </flux:button>
                        </div>

                        <flux:textarea wire:model="short_description" label="{{ __('Short description') }}" placeholder="{{ __('Brief product summary...') }}" rows="2" />
                        <flux:textarea wire:model="description"       label="{{ __('Description') }}"       placeholder="{{ __('Full product description...') }}" rows="3" />

                        {{-- Supplier --}}
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                    {{ __('Supplier') }} <span class="text-red-400">*</span>
                                </p>
                                @if ($this->selectedSupplier)
                                    <button type="button" wire:click="$set('supplier_id', null)" class="text-xs text-zinc-400 hover:text-red-500">{{ __('Change') }}</button>
                                @endif
                            </div>
                            <div class="p-3">
                                @if ($this->selectedSupplier)
                                    <div class="flex items-center gap-3 rounded-lg bg-blue-50 px-3 py-2 dark:bg-blue-950/20">
                                        <flux:avatar size="xs" src="{{ $this->selectedSupplier->logo }}" name="{{ $this->selectedSupplier->shop_name }}" />
                                        <p class="flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->selectedSupplier->shop_name }}</p>
                                        <flux:badge size="sm" color="blue" inset="top bottom">{{ __('Current') }}</flux:badge>
                                    </div>
                                @else
                                    <flux:input wire:model.live.debounce.200ms="supplierSearch" icon="magnifying-glass" placeholder="{{ __('Search supplier...') }}" size="sm" />
                                    @if (strlen($supplierSearch) >= 1)
                                        <div class="mt-2 max-h-36 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            @forelse ($this->supplierResults as $supplier)
                                                <button type="button"
                                                    wire:click="$set('supplier_id', {{ $supplier->id }}); $set('supplierSearch', '')"
                                                    class="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                                >
                                                    <flux:avatar size="xs" src="{{ $supplier->logo }}" name="{{ $supplier->shop_name }}" />
                                                    <span class="text-sm">{{ $supplier->shop_name }}</span>
                                                </button>
                                            @empty
                                                <p class="px-3 py-2 text-sm text-zinc-400">{{ __('No suppliers found.') }}</p>
                                            @endforelse
                                        </div>
                                    @endif
                                @endif
                                <flux:error name="supplier_id" />
                            </div>
                        </div>

                        {{-- Category + Brand --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Category') }}</p>
                                    @if ($this->selectedCategory)
                                        <button type="button" wire:click="$set('category_id', null)" class="text-xs text-zinc-400 hover:text-red-500">✕</button>
                                    @endif
                                </div>
                                <div class="p-3">
                                    @if ($this->selectedCategory)
                                        <div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/50">
                                            <span>{{ $this->selectedCategory->icon ?? '📁' }}</span>
                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->selectedCategory->name }}</p>
                                        </div>
                                    @else
                                        <flux:input wire:model.live.debounce.200ms="categorySearch" icon="magnifying-glass" placeholder="{{ __('Search...') }}" size="sm" />
                                        @if (strlen($categorySearch) >= 1)
                                            <div class="mt-2 max-h-32 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                @forelse ($this->categoryResults as $cat)
                                                    <button type="button"
                                                        wire:click="$set('category_id', {{ $cat->id }}); $set('categorySearch', '')"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                                    >
                                                        <span>{{ $cat->icon ?? '📁' }}</span>
                                                        <span class="flex-1 text-sm">{{ $cat->name }}</span>
                                                        <flux:badge size="sm" color="zinc" inset="top bottom">L{{ $cat->depth }}</flux:badge>
                                                    </button>
                                                @empty
                                                    <p class="px-3 py-2 text-sm text-zinc-400">{{ __('No results.') }}</p>
                                                @endforelse
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Brand') }}</p>
                                    @if ($this->selectedBrand)
                                        <button type="button" wire:click="$set('brand_id', null)" class="text-xs text-zinc-400 hover:text-red-500">✕</button>
                                    @endif
                                </div>
                                <div class="p-3">
                                    @if ($this->selectedBrand)
                                        <div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/50">
                                            @if ($this->selectedBrand->logo)
                                                <img src="{{ $this->selectedBrand->logo }}" class="size-5 object-contain" alt="" />
                                            @endif
                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->selectedBrand->name }}</p>
                                        </div>
                                    @else
                                        <flux:input wire:model.live.debounce.200ms="brandSearch" icon="magnifying-glass" placeholder="{{ __('Search...') }}" size="sm" />
                                        <div class="mt-2 max-h-32 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            @forelse ($this->brandResults as $brand)
                                                <button type="button"
                                                    wire:click="$set('brand_id', {{ $brand->id }}); $set('brandSearch', '')"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                                >
                                                    @if ($brand->logo)
                                                        <img src="{{ $brand->logo }}" class="size-5 object-contain" alt="" />
                                                    @endif
                                                    <span class="text-sm">{{ $brand->name }}</span>
                                                </button>
                                            @empty
                                                <p class="px-3 py-2 text-sm text-zinc-400">{{ __('No results.') }}</p>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model="status" label="{{ __('Status') }}">
                                @foreach ($this->statusOptions as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div class="flex flex-col justify-end gap-2 pb-1">
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

                {{-- ═══ STEP 2 : Pricing ═══ --}}
                @elseif ($step === 2)
                    <div class="space-y-4">
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Pricing') }}</p>
                            </div>
                            <div class="space-y-4 p-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <flux:input wire:model="base_price"       label="{{ __('Base price') }} *" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <flux:input wire:model="compare_at_price" label="{{ __('Compare at') }}"   type="number" min="0" step="0.01" placeholder="0.00" description="{{ __('Original price') }}" />
                                    <flux:input wire:model="cost_price"       label="{{ __('Cost price') }}"   type="number" min="0" step="0.01" placeholder="0.00" description="{{ __('Your cost') }}" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:select wire:model="currency" label="{{ __('Currency') }}">
                                        <flux:select.option value="USD">USD — US Dollar</flux:select.option>
                                        <flux:select.option value="EUR">EUR — Euro</flux:select.option>
                                        <flux:select.option value="MAD">MAD — Dirham</flux:select.option>
                                        <flux:select.option value="GBP">GBP — Pound</flux:select.option>
                                    </flux:select>
                                    <flux:input wire:model="barcode" label="{{ __('Barcode') }}" placeholder="EAN-13..." />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Shipping & Inventory') }}</p>
                            </div>
                            <div class="space-y-4 p-4">
                                <div class="flex flex-wrap items-center gap-6">
                                    <flux:field variant="inline">
                                        <flux:label>{{ __('Requires shipping') }}</flux:label>
                                        <flux:switch wire:model="requires_shipping" />
                                    </flux:field>
                                    <flux:field variant="inline">
                                        <flux:label>{{ __('Digital product') }}</flux:label>
                                        <flux:switch wire:model="is_digital" />
                                    </flux:field>
                                    <flux:field variant="inline">
                                        <flux:label>{{ __('Track inventory') }}</flux:label>
                                        <flux:switch wire:model="track_inventory" />
                                    </flux:field>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <flux:input wire:model="weight_kg"           label="{{ __('Weight (kg)') }}"    type="number" min="0" step="0.01" placeholder="0.00" />
                                    <flux:input wire:model="low_stock_threshold" label="{{ __('Low stock alert') }}" type="number" min="0" placeholder="5" />
                                    <flux:input wire:model="origin_country"      label="{{ __('Origin country') }}"  placeholder="US" maxlength="2" />
                                </div>
                                <flux:input wire:model="hs_code" label="{{ __('HS Code') }}" placeholder="1234.56.78" description="{{ __('Harmonized System code for customs') }}" />
                            </div>
                        </div>

                        @if ($base_price && $cost_price)
                            @php
                                $margin = $base_price > 0 ? round((($base_price - $cost_price) / $base_price) * 100, 1) : 0;
                                $profit = round($base_price - $cost_price, 2);
                            @endphp
                            <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-950/20">
                                <flux:icon name="arrow-trending-up" class="size-4 text-green-600" />
                                <p class="text-sm text-green-700 dark:text-green-400">
                                    {{ __('Margin') }}: <span class="font-semibold">{{ $margin }}%</span>
                                    · {{ __('Profit per unit') }}: <span class="font-semibold">{{ $profit }} {{ $currency }}</span>
                                </p>
                            </div>
                        @endif
                    </div>

                {{-- ═══ STEP 3 : Images ═══ --}}
                @elseif ($step === 3)
                    <div class="space-y-4">
                        @php
                            $totalImages = count($existingImages) + count($uploadedImages);
                        @endphp

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Product images') }}</p>
                                <p class="text-xs text-zinc-400">{{ __('Click ★ to set primary. Hover to delete.') }}</p>
                            </div>
                            @if ($totalImages > 0)
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ $totalImages }}/10</flux:badge>
                            @endif
                        </div>

                        {{-- Drop zone --}}
                        @if ($totalImages < 10)
                            <div
                                x-data="{ dragging: false }"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="
                                    dragging = false;
                                    const files = $event.dataTransfer.files;
                                    if (files.length) { $wire.upload('newImages', files[0]); }
                                "
                                :class="dragging
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/10'
                                    : 'border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/30'"
                                class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed py-8 transition-all"
                            >
                                <flux:icon name="cloud-arrow-up" class="mb-2 size-8 text-zinc-400" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Drag & drop or browse') }}</p>
                                <p class="mb-3 text-xs text-zinc-400">{{ __('PNG, JPG, WEBP up to 5MB') }}</p>

                                <label x-data class="cursor-pointer">
                                    <input type="file" multiple accept="image/*" class="hidden" wire:model="newImages" />
                                    <span
                                        x-on:click.prevent="$el.previousElementSibling.click()"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        <flux:icon name="photo" class="size-4" />
                                        {{ __('Browse files') }}
                                    </span>
                                </label>
                            </div>
                        @endif

                        <div wire:loading wire:target="newImages" class="flex items-center gap-2 text-sm text-zinc-500">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-blue-500" />
                            <span>{{ __('Uploading...') }}</span>
                        </div>

                        {{-- Images existantes --}}
                        @if (!empty($existingImages) || !empty($uploadedImages))
                            <div class="grid grid-cols-4 gap-3 sm:grid-cols-5">

                                {{-- Images en DB --}}
                                @foreach ($existingImages as $i => $img)
                                    <div
                                        wire:key="existing-{{ $img['id'] }}"
                                        class="group relative overflow-hidden rounded-xl border-2 transition-all
                                            {{ $img['is_primary']
                                                ? 'border-blue-500 ring-2 ring-blue-500/20'
                                                : 'border-zinc-200 dark:border-zinc-700' }}"
                                    >
                                        <div class="aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                            <img src="{{ $img['image_path'] }}" class="size-full object-cover" alt="{{ $img['alt_text'] }}" />
                                        </div>

                                        @if ($img['is_primary'])
                                            <div class="absolute left-1 top-1">
                                                <span class="inline-flex items-center rounded-full bg-blue-500 px-1.5 py-0.5 text-xs font-medium text-white">★</span>
                                            </div>
                                        @endif

                                        <div class="absolute inset-0 flex items-center justify-center gap-1.5 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                            @if (!$img['is_primary'])
                                                <button type="button" wire:click="setPrimaryExisting({{ $i }})" title="{{ __('Set as primary') }}" class="flex size-7 items-center justify-center rounded-full bg-blue-500 text-white hover:bg-blue-600">
                                                    <flux:icon name="star" class="size-3.5" />
                                                </button>
                                            @endif
                                            <button type="button" wire:click="removeExistingImage({{ $i }})" title="{{ __('Remove') }}" class="flex size-7 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600">
                                                <flux:icon name="trash" class="size-3.5" />
                                            </button>
                                        </div>

                                        <div class="absolute bottom-1 left-1 flex size-5 items-center justify-center rounded-full bg-zinc-700/70 text-xs text-white">
                                            <flux:icon name="photo" class="size-3" />
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Nouvelles images (non encore sauvegardées) --}}
                                @foreach ($uploadedImages as $i => $image)
                                    <div
                                        wire:key="new-img-{{ $i }}"
                                        class="group relative overflow-hidden rounded-xl border-2 border-dashed border-blue-300 dark:border-blue-700"
                                    >
                                        <div class="aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                            <img src="{{ $image->temporaryUrl() }}" class="size-full object-cover" alt="New {{ $i + 1 }}" />
                                        </div>

                                        <div class="absolute left-1 top-1">
                                            <span class="inline-flex items-center rounded-full bg-blue-400 px-1.5 py-0.5 text-xs font-medium text-white">{{ __('New') }}</span>
                                        </div>

                                        <div class="absolute inset-0 flex items-center justify-center gap-1.5 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                            @if (empty($existingImages))
                                                <button type="button" wire:click="setPrimaryNew({{ $i }})" title="{{ __('Set as primary') }}" class="flex size-7 items-center justify-center rounded-full bg-blue-500 text-white hover:bg-blue-600">
                                                    <flux:icon name="star" class="size-3.5" />
                                                </button>
                                            @endif
                                            <button type="button" wire:click="removeNewImage({{ $i }})" title="{{ __('Remove') }}" class="flex size-7 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600">
                                                <flux:icon name="trash" class="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (!empty($deletedImageIds))
                                <p class="text-xs text-red-400">
                                    {{ count($deletedImageIds) }} {{ __('image(s) will be deleted on save.') }}
                                </p>
                            @endif
                        @endif
                    </div>

                {{-- ═══ STEP 4 : Variants ═══ --}}
                @elseif ($step === 4)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('This product has variants') }}</p>
                                <p class="text-xs text-zinc-400">{{ __('e.g. sizes, colors, storage options') }}</p>
                            </div>
                            <flux:switch wire:model.live="hasVariants" />
                        </div>

                        @if ($hasVariants)
                            <div class="space-y-3">
                                @forelse ($variants as $i => $variant)
                                    <div
                                        wire:key="variant-{{ $i }}"
                                        class="rounded-xl border dark:border-zinc-700
                                            {{ empty($variant['id']) ? 'border-blue-200 dark:border-blue-800' : 'border-zinc-200' }}"
                                    >
                                        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                                            <div class="flex items-center gap-2">
                                                <p class="text-xs font-semibold text-zinc-500">{{ __('Variant') }} #{{ $i + 1 }}</p>
                                                @if (empty($variant['id']))
                                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ __('New') }}</flux:badge>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <flux:field variant="inline">
                                                    <flux:label class="text-xs">{{ __('Active') }}</flux:label>
                                                    <flux:switch wire:model="variants.{{ $i }}.is_active" />
                                                </flux:field>
                                                <flux:button type="button" variant="ghost" size="sm" icon="document-duplicate" wire:click="duplicateVariant({{ $i }})" />
                                                <flux:button type="button" variant="ghost" size="sm" icon="trash" wire:click="removeVariant({{ $i }})" class="text-red-400 hover:text-red-500" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3 p-4">
                                            <flux:input wire:model="variants.{{ $i }}.name"             label="{{ __('Name') }} *"      placeholder="Black / XL" />
                                            <flux:input wire:model="variants.{{ $i }}.sku"              label="{{ __('SKU') }}"           placeholder="ABC-001" />
                                            <flux:input wire:model="variants.{{ $i }}.price"            label="{{ __('Price') }} *"     type="number" min="0" step="0.01" placeholder="0.00" />
                                            <flux:input wire:model="variants.{{ $i }}.compare_at_price" label="{{ __('Compare at') }}"  type="number" min="0" step="0.01" placeholder="0.00" />
                                            <flux:input wire:model="variants.{{ $i }}.cost_price"       label="{{ __('Cost') }}"         type="number" min="0" step="0.01" placeholder="0.00" />
                                            <flux:input wire:model="variants.{{ $i }}.weight_kg"        label="{{ __('Weight (kg)') }}" type="number" min="0" step="0.01" placeholder="0.00" />
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-8 dark:border-zinc-700">
                                        <flux:icon name="squares-2x2" class="mb-2 size-8 text-zinc-300" />
                                        <p class="text-sm text-zinc-400">{{ __('No variants. Add one below.') }}</p>
                                    </div>
                                @endforelse
                            </div>

                            @if (!empty($deletedVariantIds))
                                <p class="text-xs text-red-400">
                                    {{ count($deletedVariantIds) }} {{ __('variant(s) will be deleted on save.') }}
                                </p>
                            @endif

                            <flux:button type="button" variant="ghost" icon="plus" wire:click="addVariant" class="w-full">
                                {{ __('Add variant') }}
                            </flux:button>
                        @else
                            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                                <flux:icon name="information-circle" class="size-4 shrink-0 text-zinc-400" />
                                <p class="text-sm text-zinc-500">{{ __('Single product without variants. Enable the toggle above to add variants.') }}</p>
                            </div>
                        @endif
                    </div>

                {{-- ═══ STEP 5 : SEO ═══ --}}
                @elseif ($step === 5)
                    <div class="space-y-4">
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('SEO & Meta') }}</p>
                            </div>
                            <div class="space-y-4 p-4">
                                <flux:input    wire:model="meta_title"       label="{{ __('Meta title') }}"       placeholder="{{ $name ?: __('Product name') }}" />
                                <flux:textarea wire:model="meta_description" label="{{ __('Meta description') }}" placeholder="{{ __('Brief description for search engines...') }}" rows="3" />
                                <flux:input    wire:model="meta_keywords"    label="{{ __('Meta keywords') }}"    placeholder="{{ __('keyword1, keyword2, keyword3') }}" description="{{ __('Comma separated') }}" />
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/30">
                            <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Summary of changes') }}</p>
                            </div>
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Name') }}</span>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $name ?: '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Price') }}</span>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $base_price ? number_format((float)$base_price, 2) . ' ' . $currency : '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Supplier') }}</span>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $this->selectedSupplier?->shop_name ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Images') }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <flux:badge size="sm" color="blue" inset="top bottom">{{ count($existingImages) }} {{ __('kept') }}</flux:badge>
                                        @if (!empty($uploadedImages))
                                            <flux:badge size="sm" color="green" inset="top bottom">+{{ count($uploadedImages) }} {{ __('new') }}</flux:badge>
                                        @endif
                                        @if (!empty($deletedImageIds))
                                            <flux:badge size="sm" color="red" inset="top bottom">-{{ count($deletedImageIds) }} {{ __('deleted') }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Variants') }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <flux:badge size="sm" :color="$hasVariants && count($variants) > 0 ? 'blue' : 'zinc'" inset="top bottom">
                                            {{ count($variants) }} {{ __('variants') }}
                                        </flux:badge>
                                        @if (!empty($deletedVariantIds))
                                            <flux:badge size="sm" color="red" inset="top bottom">-{{ count($deletedVariantIds) }} {{ __('deleted') }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <span class="text-xs text-zinc-400">{{ __('Status') }}</span>
                                    <flux:badge
                                        size="sm"
                                        :color="match($status) {
                                            'approved'  => 'green',
                                            'pending'   => 'yellow',
                                            'rejected'  => 'red',
                                            'suspended' => 'zinc',
                                            default     => 'zinc'
                                        }"
                                        inset="top bottom"
                                    >
                                        {{ ucfirst($status) }}
                                    </flux:badge>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            {{-- ── Navigation footer ── --}}
            <div class="mt-6 flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($step > 1)
                    <flux:button type="button" variant="ghost" icon="arrow-left" wire:click="previous">
                        {{ __('Back') }}
                    </flux:button>
                @endif
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                @if ($step < $totalSteps)
                    <flux:button type="button" variant="primary" wire:click="next" icon-trailing="arrow-right">
                        {{ __('Continue') }}
                    </flux:button>
                @else
                    <flux:button type="button" variant="primary" icon="check" wire:click="save">
                        {{ __('Save changes') }}
                    </flux:button>
                @endif
            </div>

        </div>
    </flux:modal>
</div>
