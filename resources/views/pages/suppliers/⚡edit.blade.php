<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Supplier;
use App\Models\Country;
use Illuminate\Support\Str;
use App\Models\User;
use Flux\Flux;

new class extends Component
{
    public ?int $supplierId = null;
    public ?int $userId = null;
    public string $ownerName = '';
    public string $ownerEmail = '';

    // ── Shop ───────────────────────────────────────────────────────────────
    public string $shop_name = '';
    public string $slug = '';
    public string $description = '';
    public string $business_type = '';
    public string $registration_number = '';
    public string $tax_number = '';
    public string $website = '';

    // ── Settings ───────────────────────────────────────────────────────────
    public string $status = 'pending';
    public string $commission_rate = '10';
    public bool $is_featured = false;
    public bool $is_verified = false;
    public ?int $country_id = null;

    // ── Country search ─────────────────────────────────────────────────────
    public string $countrySearch = '';

    // ── Change owner ───────────────────────────────────────────────────────
    public bool $showChangeOwner = false;
    public string $ownerSearch = '';
    public ?int $newOwnerId = null;

    #[On('edit-supplier')]
    public function loadSupplier(int $id): void
    {
        $supplier = Supplier::with(['user', 'country'])->findOrFail($id);

        $this->supplierId          = $supplier->id;
        $this->userId              = $supplier->user_id;
        $this->ownerName           = $supplier->user?->fullName() ?: ($supplier->user?->name ?? '');
        $this->ownerEmail          = $supplier->user?->email ?? '';
        $this->shop_name           = $supplier->shop_name;
        $this->slug                = $supplier->slug;
        $this->description         = $supplier->description ?? '';
        $this->business_type       = $supplier->business_type ?? '';
        $this->registration_number = $supplier->registration_number ?? '';
        $this->tax_number          = $supplier->tax_number ?? '';
        $this->website             = $supplier->website ?? '';
        $this->status              = $supplier->status;
        $this->commission_rate     = (string) $supplier->commission_rate;
        $this->is_featured         = $supplier->is_featured;
        $this->is_verified         = $supplier->is_verified;
        $this->country_id          = $supplier->country_id;
        $this->countrySearch       = '';

        $this->resetValidation();
        Flux::modal('edit-supplier')->show();
    }

    public function updatedShopName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    #[Computed]
    public function countries()
    {
        return Country::query()
            ->when($this->countrySearch, fn($q) =>
                $q->where('name', 'like', "%{$this->countrySearch}%")
                  ->orWhere('code', 'like', "%{$this->countrySearch}%")
            )
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedCountry()
    {
        if (!$this->country_id) return null;
        return Country::find($this->country_id);
    }

    public function save(): void
    {
        $this->validate([
            'shop_name'           => "required|string|max:255|unique:suppliers,shop_name,{$this->supplierId}",
            'slug'                => "required|string|max:255|unique:suppliers,slug,{$this->supplierId}",
            'description'         => 'nullable|string|max:5000',
            'business_type'       => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'tax_number'          => 'nullable|string|max:100',
            'website'             => 'nullable|url|max:500',
            'status'              => 'required|in:pending,approved,rejected,suspended',
            'commission_rate'     => 'required|numeric|min:0|max:100',
            'country_id'          => 'nullable|integer|exists:countries,id',
        ]);

        try {
            Supplier::findOrFail($this->supplierId)->update([
                'shop_name'           => $this->shop_name,
                'slug'                => $this->slug,
                'description'         => $this->description ?: null,
                'business_type'       => $this->business_type ?: null,
                'registration_number' => $this->registration_number ?: null,
                'tax_number'          => $this->tax_number ?: null,
                'website'             => $this->website ?: null,
                'status'              => $this->status,
                'commission_rate'     => $this->commission_rate,
                'is_featured'         => $this->is_featured,
                'is_verified'         => $this->is_verified,
                'country_id'          => $this->country_id,
                'approved_at'         => $this->status === 'approved'
                    ? now()
                    : Supplier::find($this->supplierId)?->approved_at,
                'approved_by'         => $this->status === 'approved'
                    ? auth()->id()
                    : Supplier::find($this->supplierId)?->approved_by,
            ]);

            $this->dispatch('supplier-updated');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Supplier updated'),
                message: __(':name has been updated successfully.', ['name' => $this->shop_name]),
            );

            Flux::modal('edit-supplier')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Update failed'),
                message: __('An error occurred while updating the supplier.'),
            );
        }
    }

    #[Computed]
    public function ownerResults()
    {
        if (strlen($this->ownerSearch) < 2) return collect();

        return User::query()
            ->where(fn($q) =>
                $q->where('first_name', 'like', "%{$this->ownerSearch}%")
                  ->orWhere('last_name', 'like', "%{$this->ownerSearch}%")
                  ->orWhere('email', 'like', "%{$this->ownerSearch}%")
            )
            ->whereDoesntHave('supplier', fn($q) => $q->where('id', '!=', $this->supplierId))
            ->limit(8)
            ->get();
    }

    public function selectNewOwner(int $id): void
    {
        $this->newOwnerId  = $id;
        $this->ownerSearch = '';
        unset($this->ownerResults);
    }

    public function confirmChangeOwner(): void
    {
        if (!$this->newOwnerId) return;

        try {
            $newUser  = User::findOrFail($this->newOwnerId);
            $supplier = Supplier::findOrFail($this->supplierId);

            $supplier->update(['user_id' => $this->newOwnerId]);

            $this->userId           = $newUser->id;
            $this->ownerName        = $newUser->fullName() ?: $newUser->name;
            $this->ownerEmail       = $newUser->email;
            $this->newOwnerId       = null;
            $this->showChangeOwner  = false;
            $this->ownerSearch      = '';

            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Owner changed'),
                message: __('The supplier owner has been updated to :name.', ['name' => $newUser->fullName()]),
            );

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Change failed'),
                message: __('An error occurred while changing the owner.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="edit-supplier" class="w-full max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-6">

            {{-- Header --}}
            <div class="flex items-center gap-4 pb-2">
                <flux:avatar
                    size="lg"
                    src="{{ $logo ?? null }}"
                    name="{{ $shop_name ?: 'Supplier' }}"
                    class="shrink-0"
                />
                <div class="min-w-0 flex-1 pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ __('Edit Supplier') }}</flux:heading>
                        @if ($status)
                            <flux:badge
                                size="sm"
                                :color="match($status) {
                                    'approved'  => 'green',
                                    'pending'   => 'yellow',
                                    'rejected'  => 'red',
                                    'suspended' => 'zinc',
                                    default     => 'zinc',
                                }"
                                inset="top bottom"
                            >
                                {{ ucfirst($status) }}
                            </flux:badge>
                        @endif
                        @if ($is_verified)
                            <flux:badge size="sm" color="blue" inset="top bottom" icon="check-badge">
                                {{ __('Verified') }}
                            </flux:badge>
                        @endif
                        @if ($is_featured)
                            <flux:badge size="sm" color="yellow" inset="top bottom" icon="star">
                                {{ __('Featured') }}
                            </flux:badge>
                        @endif
                    </div>
                    @if ($shop_name)
                        <p class="mt-0.5 text-sm text-zinc-400">
                            {{ $shop_name }}
                            @if ($slug)
                                <span class="text-zinc-300 dark:text-zinc-600"> · </span>
                                <span class="font-mono text-xs">{{ $slug }}</span>
                            @endif
                        </p>
                    @endif

                    {{-- Owner + bouton change --}}
                    <div class="mt-1 flex items-center gap-2">
                        <flux:icon name="user-circle" class="size-3.5 shrink-0 text-zinc-400" />
                        <p class="text-xs text-zinc-400">
                            {{ $ownerName }}
                            @if ($ownerEmail)
                                <span class="text-zinc-300 dark:text-zinc-600"> · </span>
                                {{ $ownerEmail }}
                            @endif
                        </p>
                        <button
                            type="button"
                            x-on:click="$wire.set('showChangeOwner', {{ $showChangeOwner ? 'false' : 'true' }})"
                            class="flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs transition-colors
                                {{ $showChangeOwner
                                    ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'
                                    : 'text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300' }}"
                        >
                            <flux:icon
                                name="{{ $showChangeOwner ? 'x-mark' : 'arrow-path' }}"
                                class="size-3"
                            />
                            <span class="text-rose-600">{{ $showChangeOwner ? __('Cancel') : __('Change') }}</span>
                        </button>
                    </div>

                    {{-- Formulaire changement owner --}}
                    @if ($showChangeOwner)
                        <div class="mt-3 space-y-2">
                            <flux:input
                                wire:model.live.debounce.300ms="ownerSearch"
                                icon="magnifying-glass"
                                placeholder="{{ __('Search new owner...') }}"
                                size="sm"
                            />

                            @if (strlen($ownerSearch) >= 2)
                                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    @forelse ($this->ownerResults as $user)
                                        <button
                                            type="button"
                                            wire:key="owner-{{ $user->id }}"
                                            x-on:click="$wire.selectNewOwner({{ $user->id }})"
                                            class="flex w-full items-center gap-3 border-b border-zinc-50 px-4 py-2.5 text-left last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/30"
                                        >
                                            <flux:avatar size="xs" src="{{ $user->avatar }}" name="{{ $user->fullName() }}" />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                    {{ $user->fullName() ?: $user->name }}
                                                </p>
                                                <p class="text-xs text-zinc-400">{{ $user->email }}</p>
                                            </div>
                                            <flux:icon name="chevron-right" class="size-4 text-zinc-300" />
                                        </button>
                                    @empty
                                        <div class="flex items-center justify-center py-4">
                                            <p class="text-sm text-zinc-400">{{ __('No users found.') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            @elseif ($newOwnerId)
                                @php $newOwner = \App\Models\User::find($newOwnerId); @endphp
                                @if ($newOwner)
                                    <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-950/20">
                                        <flux:avatar size="sm" src="{{ $newOwner->avatar }}" name="{{ $newOwner->fullName() }}" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                {{ $newOwner->fullName() ?: $newOwner->name }}
                                            </p>
                                            <p class="text-xs text-zinc-400">{{ $newOwner->email }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <flux:badge size="sm" color="green" inset="top bottom">{{ __('New owner') }}</flux:badge>
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                x-on:click="$wire.set('newOwnerId', null)"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            size="sm"
                                            icon="check"
                                            x-on:click="$wire.confirmChangeOwner()"
                                        >
                                            {{ __('Confirm change') }}
                                        </flux:button>
                                    </div>
                                @endif
                            @else
                                <p class="text-xs text-zinc-400">{{ __('Type at least 2 characters to search.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

                {{-- Section : Shop --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Shop information') }}
                        </p>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model.live="shop_name"
                                label="{{ __('Shop name') }}"
                                placeholder="My Awesome Shop"
                            />
                            <flux:input
                                wire:model="slug"
                                label="{{ __('Slug') }}"
                                placeholder="my-awesome-shop"
                                description="{{ __('Auto-generated from shop name') }}"
                            />
                        </div>

                        <flux:textarea
                            wire:model="description"
                            label="{{ __('Description') }}"
                            placeholder="{{ __('Describe the supplier\'s business...') }}"
                            rows="2"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <flux:select
                                wire:model="business_type"
                                label="{{ __('Business type') }}"
                                placeholder="{{ __('Select type') }}"
                            >
                                <flux:select.option value="">{{ __('Not specified') }}</flux:select.option>
                                <flux:select.option value="individual">{{ __('Individual') }}</flux:select.option>
                                <flux:select.option value="company">{{ __('Company') }}</flux:select.option>
                                <flux:select.option value="freelance">{{ __('Freelance') }}</flux:select.option>
                                <flux:select.option value="agency">{{ __('Agency') }}</flux:select.option>
                            </flux:select>

                            <flux:input
                                wire:model="website"
                                label="{{ __('Website') }}"
                                placeholder="https://..."
                                type="url"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="registration_number"
                                label="{{ __('Registration number') }}"
                                placeholder="REG-1234567"
                            />
                            <flux:input
                                wire:model="tax_number"
                                label="{{ __('Tax number') }}"
                                placeholder="TAX-1234567"
                            />
                        </div>
                    </div>
                </div>

                {{-- Section : Country --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Country') }}
                        </p>
                        @if ($this->selectedCountry)
                            <div class="flex items-center gap-2">
                                @if ($this->selectedCountry->flag_url)
                                    <img src="{{ $this->selectedCountry->flag_url }}" alt="{{ $this->selectedCountry->name }}" class="h-4 w-6 rounded-sm object-cover shadow-sm" />
                                @endif
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $this->selectedCountry->name }}
                                </span>
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $this->selectedCountry->code }}
                                </flux:badge>
                            </div>
                        @endif
                    </div>
                    <div class="p-4 space-y-2">
                        <flux:input
                            wire:model.live.debounce.200ms="countrySearch"
                            icon="magnifying-glass"
                            placeholder="{{ __('Search country...') }}"
                            size="sm"
                        />
                        <div class="max-h-36 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @forelse ($this->countries as $country)
                                <label
                                    wire:key="country-{{ $country->id }}"
                                    class="flex cursor-pointer items-center gap-3 border-b border-zinc-50 px-4 py-2.5 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/30
                                        {{ (int) $country_id === $country->id ? 'bg-blue-50 dark:bg-blue-950/20' : '' }}"
                                >
                                    <input
                                        type="radio"
                                        name="country_id"
                                        value="{{ $country->id }}"
                                        x-on:change="$wire.set('country_id', {{ $country->id }})"
                                        {{ (int) $country_id === $country->id ? 'checked' : '' }}
                                        class="text-blue-600"
                                    />
                                    <div class="flex min-w-0 flex-1 items-center gap-2">
                                        @if ($country->flag_url)
                                            <img src="{{ $country->flag_url }}" alt="{{ $country->name }}" class="h-4 w-6 shrink-0 rounded-sm object-cover shadow-sm" />
                                        @endif
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $country->name }}
                                        </span>
                                    </div>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">
                                        {{ $country->code }}
                                    </flux:badge>
                                </label>
                            @empty
                                <div class="flex items-center justify-center py-4">
                                    <p class="text-sm text-zinc-400">{{ __('No countries found.') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Section : Settings --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Settings') }}
                        </p>
                    </div>
                    <div class="space-y-4 p-4">

                        {{-- Status selector --}}
                        <div>
                            <flux:label>{{ __('Status') }}</flux:label>
                            <div class="mt-2 grid grid-cols-4 gap-2">
                                @foreach (['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red', 'suspended' => 'zinc'] as $value => $color)
                                    <label
                                        x-on:click="$wire.set('status', '{{ $value }}')"
                                        class="flex cursor-pointer items-center justify-center rounded-lg border-2 p-2.5 transition-all
                                            {{ $status === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}"
                                    >
                                        <flux:badge size="sm" color="{{ $color }}">{{ ucfirst($value) }}</flux:badge>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <flux:input
                            wire:model="commission_rate"
                            label="{{ __('Commission rate') }}"
                            placeholder="10"
                            type="number"
                            min="0"
                            max="100"
                            step="0.5"
                            description="{{ __('Percentage (%)') }}"
                        />

                        <div class="flex items-center gap-6">
                            <flux:field variant="inline">
                                <flux:label>{{ __('Featured') }}</flux:label>
                                <flux:switch wire:model="is_featured" />
                            </flux:field>
                            <flux:field variant="inline">
                                <flux:label>{{ __('Verified') }}</flux:label>
                                <flux:switch wire:model="is_verified" />
                            </flux:field>
                        </div>

                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
