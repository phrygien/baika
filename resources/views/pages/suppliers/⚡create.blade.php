<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Country;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Flux\Flux;

new class extends Component
{
    // ── Mode utilisateur ───────────────────────────────────────────────────
    public string $userMode = 'existing'; // existing | new

    // Utilisateur existant
    public ?int $selectedUserId = null;
    public string $userSearch = '';

    // Nouvel utilisateur
    public string $new_first_name = '';
    public string $new_last_name = '';
    public string $new_email = '';
    public string $new_phone = '';
    public string $new_password = '';

    // ── Infos supplier ─────────────────────────────────────────────────────
    public string $shop_name = '';
    public string $slug = '';
    public string $description = '';
    public string $business_type = '';
    public string $registration_number = '';
    public string $tax_number = '';
    public string $website = '';
    public string $status = 'pending';
    public string $commission_rate = '10';
    public bool $is_featured = false;
    public bool $is_verified = false;
    public ?int $country_id = null;

    // ── Country search ─────────────────────────────────────────────────────
    public string $countrySearch = '';

    #[On('create-supplier')]
    public function open(): void
    {
        $this->reset();
        $this->userMode      = 'existing';
        $this->status        = 'pending';
        $this->commission_rate = '10';
        $this->resetValidation();
        Flux::modal('create-supplier')->show();
    }

    #[Computed]
    public function users()
    {
        if (strlen($this->userSearch) < 2) return collect();

        return User::query()
            ->whereDoesntHave('supplier')
            ->where(fn($q) =>
                $q->where('first_name', 'like', "%{$this->userSearch}%")
                  ->orWhere('last_name', 'like', "%{$this->userSearch}%")
                  ->orWhere('email', 'like', "%{$this->userSearch}%")
            )
            ->limit(8)
            ->get();
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
    public function selectedUser()
    {
        if (!$this->selectedUserId) return null;
        return User::find($this->selectedUserId);
    }

    public function selectUser(int $id): void
    {
        $this->selectedUserId = $id;
        $this->userSearch     = '';
        unset($this->users);
    }

    public function clearUser(): void
    {
        $this->selectedUserId = null;
        $this->userSearch     = '';
    }

    public function updatedShopName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        if ($this->userMode === 'existing') {
            $this->validate([
                'selectedUserId' => 'required|integer|exists:users,id',
                'shop_name'      => 'required|string|max:255|unique:suppliers,shop_name',
                'slug'           => 'required|string|max:255|unique:suppliers,slug',
                'country_id'     => 'nullable|integer|exists:countries,id',
                'commission_rate'=> 'required|numeric|min:0|max:100',
                'business_type'  => 'nullable|string|max:100',
                'registration_number' => 'nullable|string|max:100',
                'tax_number'     => 'nullable|string|max:100',
                'website'        => 'nullable|url|max:500',
                'description'    => 'nullable|string|max:5000',
                'status'         => 'required|in:pending,approved,rejected,suspended',
            ]);

            $userId = $this->selectedUserId;

        } else {
            $this->validate([
                'new_first_name' => 'required|string|max:255',
                'new_last_name'  => 'required|string|max:255',
                'new_email'      => 'required|email|unique:users,email',
                'new_phone'      => 'nullable|string|max:20|unique:users,phone',
                'new_password'   => 'required|string|min:8',
                'shop_name'      => 'required|string|max:255|unique:suppliers,shop_name',
                'slug'           => 'required|string|max:255|unique:suppliers,slug',
                'country_id'     => 'nullable|integer|exists:countries,id',
                'commission_rate'=> 'required|numeric|min:0|max:100',
                'business_type'  => 'nullable|string|max:100',
                'registration_number' => 'nullable|string|max:100',
                'tax_number'     => 'nullable|string|max:100',
                'website'        => 'nullable|url|max:500',
                'description'    => 'nullable|string|max:5000',
                'status'         => 'required|in:pending,approved,rejected,suspended',
            ]);

            // Créer le nouvel utilisateur
            $user = User::create([
                'first_name'        => $this->new_first_name,
                'last_name'         => $this->new_last_name,
                'name'              => trim("{$this->new_first_name} {$this->new_last_name}"),
                'email'             => $this->new_email,
                'phone'             => $this->new_phone ?: null,
                'password'          => Hash::make($this->new_password),
                'status'            => 'active',
                'email_verified_at' => now(),
                'local'             => 'fr',
                'currency'          => 'USD',
            ]);

            // Assigner le rôle supplier
            $supplierRole = Role::where('name', 'supplier')->first();
            if ($supplierRole) {
                $user->assignRole(
                    role:       $supplierRole->id,
                    assignedBy: auth()->id(),
                    makePrimary: true,
                );
            }

            $userId = $user->id;
        }

        try {
            Supplier::create([
                'user_id'             => $userId,
                'country_id'          => $this->country_id,
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
                'average_rating'      => 0,
                'total_reviews'       => 0,
                'total_sales'         => 0,
                'total_products'      => 0,
            ]);

            $this->dispatch('supplier-created');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Supplier created'),
                message: __(':name has been created successfully.', ['name' => $this->shop_name]),
            );

            Flux::modal('create-supplier')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Creation failed'),
                message: __('An error occurred: ') . $e->getMessage(),
            );
        }
    }
};
?>

<div>
    <flux:modal name="create-supplier" class="w-full max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Create Supplier') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Add a new supplier and link it to a user account.') }}</flux:text>
                </div>

                {{-- Section : Utilisateur --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('User account') }}
                        </p>
                        {{-- Toggle mode --}}
                        <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            <button
                                type="button"
                                x-on:click="$wire.set('userMode', 'existing')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-all
                                    {{ $userMode === 'existing' ? 'bg-white text-zinc-800 shadow dark:bg-zinc-700 dark:text-zinc-200' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                {{ __('Existing user') }}
                            </button>
                            <button
                                type="button"
                                x-on:click="$wire.set('userMode', 'new')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-all
                                    {{ $userMode === 'new' ? 'bg-white text-zinc-800 shadow dark:bg-zinc-700 dark:text-zinc-200' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                {{ __('New user') }}
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        @if ($userMode === 'existing')
                            {{-- Utilisateur sélectionné --}}
                            @if ($this->selectedUser)
                                <div class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950/20">
                                    <flux:avatar
                                        size="sm"
                                        src="{{ $this->selectedUser->avatar }}"
                                        name="{{ $this->selectedUser->fullName() }}"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $this->selectedUser->fullName() ?: $this->selectedUser->name }}
                                        </p>
                                        <p class="text-xs text-zinc-400">{{ $this->selectedUser->email }}</p>
                                    </div>
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                        x-on:click="$wire.clearUser()"
                                    />
                                </div>
                            @else
                                {{-- Recherche utilisateur --}}
                                <div class="space-y-2">
                                    <flux:input
                                        wire:model.live.debounce.300ms="userSearch"
                                        icon="magnifying-glass"
                                        placeholder="{{ __('Search by name or email...') }}"
                                        size="sm"
                                    />
                                    <flux:error name="selectedUserId" />

                                    @if (strlen($userSearch) >= 2)
                                        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                            @forelse ($this->users as $user)
                                                <button
                                                    type="button"
                                                    wire:key="user-{{ $user->id }}"
                                                    x-on:click="$wire.selectUser({{ $user->id }})"
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
                                                <div class="flex items-center justify-center py-6">
                                                    <p class="text-sm text-zinc-400">{{ __('No users found without a supplier.') }}</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    @else
                                        <p class="text-xs text-zinc-400">{{ __('Type at least 2 characters to search.') }}</p>
                                    @endif
                                </div>
                            @endif

                        @else
                            {{-- Nouveau utilisateur --}}
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        wire:model="new_first_name"
                                        label="{{ __('First name') }}"
                                        placeholder="John"
                                    />
                                    <flux:input
                                        wire:model="new_last_name"
                                        label="{{ __('Last name') }}"
                                        placeholder="Doe"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        wire:model="new_email"
                                        type="email"
                                        label="{{ __('Email') }}"
                                        placeholder="john@example.com"
                                    />
                                    <flux:input
                                        wire:model="new_phone"
                                        label="{{ __('Phone') }}"
                                        placeholder="+1 234 567 890"
                                    />
                                </div>
                                <flux:input
                                    wire:model="new_password"
                                    type="password"
                                    label="{{ __('Password') }}"
                                    placeholder="••••••••"
                                    viewable
                                />
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
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Country') }}
                        </p>
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
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $country->name }}</span>
                                    </div>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $country->code }}</flux:badge>
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
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model="status" label="{{ __('Status') }}">
                                <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                                <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                                <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
                                <flux:select.option value="suspended">{{ __('Suspended') }}</flux:select.option>
                            </flux:select>

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
                        </div>

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
                    <flux:button type="submit" variant="primary" icon="plus">
                        {{ __('Create Supplier') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
