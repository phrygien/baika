<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Brand;
use Illuminate\Support\Str;
use Flux\Flux;

new class extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $website = '';
    public string $logo = '';
    public string $description = '';
    public bool $is_active = true;
    public bool $is_featured = false;

    #[On('create-brand')]
    public function open(): void
    {
        $this->reset();
        $this->is_active = true;
        $this->resetValidation();
        Flux::modal('create-brand')->show();
    }

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255|unique:brands,name',
            'slug'        => 'required|string|max:255|unique:brands,slug',
            'website'     => 'nullable|url|max:500',
            'logo'        => 'nullable|url|max:500',
            'description' => 'nullable|string|max:5000',
        ]);

        try {
            Brand::create([
                'name'        => $this->name,
                'slug'        => $this->slug,
                'website'     => $this->website ?: null,
                'logo'        => $this->logo ?: null,
                'description' => $this->description ?: null,
                'is_active'   => $this->is_active,
                'is_featured' => $this->is_featured,
            ]);

            $this->dispatch('brand-created');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Brand created'),
                message: __(':name has been created successfully.', ['name' => $this->name]),
            );

            Flux::modal('create-brand')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Creation failed'),
                message: __('An error occurred while creating the brand.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="create-brand" class="w-full max-w-lg">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div class="flex items-center gap-4 pr-8">
                    <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        @if ($logo)
                            <img
                                src="{{ $logo }}"
                                alt="Logo preview"
                                class="size-full object-contain p-1"
                                onerror="this.style.display='none'"
                            />
                        @else
                            <flux:icon name="building-storefront" class="size-5 text-zinc-300" />
                        @endif
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Add Brand') }}</flux:heading>
                        <flux:text class="mt-0.5">{{ __('Create a new product brand.') }}</flux:text>
                    </div>
                </div>

                {{-- Section : Informations --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Information') }}
                        </p>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model.live="name"
                                label="{{ __('Brand name') }}"
                                placeholder="Nike"
                            />
                            <flux:input
                                wire:model="slug"
                                label="{{ __('Slug') }}"
                                placeholder="nike"
                                description="{{ __('Auto-generated') }}"
                            />
                        </div>

                        <flux:input
                            wire:model="website"
                            label="{{ __('Website') }}"
                            placeholder="https://nike.com"
                            type="url"
                            icon="globe-alt"
                        />

                        <flux:input
                            wire:model.live="logo"
                            label="{{ __('Logo URL') }}"
                            placeholder="https://..."
                            type="url"
                            icon="photo"
                            description="{{ __('Logo preview updates in real time') }}"
                        />

                        <flux:textarea
                            wire:model="description"
                            label="{{ __('Description') }}"
                            placeholder="{{ __('Describe this brand...') }}"
                            rows="2"
                        />
                    </div>
                </div>

                {{-- Section : Settings --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Settings') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-6 p-4">
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

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="plus">
                        {{ __('Create Brand') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
