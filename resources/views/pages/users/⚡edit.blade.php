<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Flux\Flux;

new class extends Component
{
    public ?int $userId = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $gender = '';
    public string $date_of_birth = '';
    public string $status = 'active';

    public array $assignedRoles = [];
    public ?int $newRoleId = null;
    public string $newExpiresAt = '';
    public string $newNotes = '';
    public bool $newIsPrimary = false;
    public string $roleSearch = '';

    #[On('edit-user')]
    public function loadUser(int $id): void
    {
        $user = User::with('activeUserRoles.role')->findOrFail($id);

        $this->userId        = $user->id;
        $this->first_name    = $user->first_name ?? '';
        $this->last_name     = $user->last_name ?? '';
        $this->email         = $user->email;
        $this->phone         = $user->phone ?? '';
        $this->gender        = $user->gender ?? '';
        $this->date_of_birth = $user->date_of_birth?->format('Y-m-d') ?? '';
        $this->status        = $user->status ?? 'active';

        $this->syncAssignedRoles($user);

        $this->newRoleId    = null;
        $this->newExpiresAt = '';
        $this->newNotes     = '';
        $this->newIsPrimary = false;
        $this->roleSearch   = '';

        $this->resetValidation();
        Flux::modal('edit-user')->show();
    }

    protected function syncAssignedRoles(User $user): void
    {
        $user->load('activeUserRoles.role');

        $this->assignedRoles = $user->activeUserRoles
            ->mapWithKeys(fn(UserRole $ur) => [
                $ur->role_id => [
                    'name'       => $ur->role?->display_name ?? $ur->role?->name,
                    'key'        => $ur->role?->name,
                    'is_primary' => (bool) $ur->is_primary,
                    'expires_at' => $ur->expires_at?->format('Y-m-d') ?? '',
                    'notes'      => $ur->notes ?? '',
                ],
            ])
            ->toArray();

        unset($this->availableRoles);
    }

    #[Computed]
    public function availableRoles()
    {
        $assignedIds = array_map('intval', array_keys($this->assignedRoles));

        return Role::query()
            ->whereNotIn('id', $assignedIds)
            ->when($this->roleSearch, fn($q) =>
                $q->where('display_name', 'like', "%{$this->roleSearch}%")
                  ->orWhere('name', 'like', "%{$this->roleSearch}%")
            )
            ->orderBy('display_name')
            ->get();
    }

    public function addRole(): void
    {
        $this->validate([
            'newRoleId'    => 'required|integer|exists:roles,id',
            'newExpiresAt' => 'nullable|date|after:today',
            'newNotes'     => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($this->userId);

        $user->assignRole(
            role:        (int) $this->newRoleId,
            assignedBy:  auth()->id(),
            expiresAt:   $this->newExpiresAt ?: null,
            makePrimary: $this->newIsPrimary,
            notes:       $this->newNotes ?: null,
        );

        $this->syncAssignedRoles($user);

        $this->newRoleId    = null;
        $this->newExpiresAt = '';
        $this->newNotes     = '';
        $this->newIsPrimary = false;
    }

    public function setPrimary(int $roleId): void
    {
        $user = User::findOrFail($this->userId);
        $user->setPrimaryRole($roleId);

        foreach ($this->assignedRoles as $id => $data) {
            $this->assignedRoles[$id]['is_primary'] = ((int) $id === $roleId);
        }
    }

    public function revokeRole(int $roleId): void
    {
        $user = User::findOrFail($this->userId);
        $user->revokeRole($roleId, auth()->id());
        $this->syncAssignedRoles($user);
    }

    public function save(): void
    {
        $this->status = trim((string) $this->status);

        $this->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => "required|email|max:255|unique:users,email,{$this->userId}",
            'phone'         => 'nullable|string|max:20',
            'gender'        => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'status'        => 'required|in:active,inactive,pending,banned',
        ]);

        User::findOrFail($this->userId)->update([
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'name'          => trim("{$this->first_name} {$this->last_name}"),
            'email'         => $this->email,
            'phone'         => $this->phone ?: null,
            'gender'        => $this->gender ?: null,
            'date_of_birth' => $this->date_of_birth ?: null,
            'status'        => trim((string) $this->status),
        ]);

        $this->dispatch('user-updated');
        Flux::modal('edit-user')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('User updated'),
            message: __('The user has been updated successfully.'),
        );
    }
};
?>

<div>
    <flux:modal name="edit-user" class="w-full max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-0">

                {{-- Header avec avatar --}}
                <div class="mb-6 flex items-center gap-4">
                    <flux:avatar
                        size="lg"
                        name="{{ trim($first_name . ' ' . $last_name) ?: 'User' }}"
                        class="shrink-0"
                    />
                    <div>
                        <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
                        <flux:text class="mt-0.5">
                            {{ trim($first_name . ' ' . $last_name) ?: __('New User') }}
                            @if ($email)
                                · <span class="text-zinc-400">{{ $email }}</span>
                            @endif
                        </flux:text>
                    </div>
                </div>

                {{-- Section : Informations --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Personal information') }}
                        </p>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="first_name" label="{{ __('First name') }}" placeholder="John" />
                            <flux:input wire:model="last_name" label="{{ __('Last name') }}" placeholder="Doe" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="email" type="email" label="{{ __('Email') }}" placeholder="john@example.com" />
                            <flux:input wire:model="phone" label="{{ __('Phone') }}" placeholder="+1 234 567 890" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model="gender" label="{{ __('Gender') }}" placeholder="{{ __('Select gender') }}">
                                <flux:select.option value="">{{ __('Not specified') }}</flux:select.option>
                                <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                                <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                                <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                            </flux:select>
                            <flux:input wire:model="date_of_birth" type="date" label="{{ __('Date of birth') }}" />
                        </div>
                    </div>
                </div>

                {{-- Section : Status --}}
                <div class="mt-3 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Account status') }}
                        </p>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-4 gap-2">
                            @foreach (['active' => 'green', 'inactive' => 'zinc', 'pending' => 'yellow', 'banned' => 'red'] as $value => $color)
                                <label
                                    wire:key="status-{{ $value }}"
                                    x-on:click="$wire.set('status', '{{ $value }}')"
                                    class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border-2 p-3 transition-all
                                        {{ $status === $value
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                            : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}"
                                >
                                    <flux:badge size="sm" color="{{ $color }}">{{ ucfirst($value) }}</flux:badge>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Section : Rôles --}}
                <div class="mt-3 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                            {{ __('Roles') }}
                        </p>
                        @if (count($assignedRoles))
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ count($assignedRoles) }} {{ __('assigned') }}
                            </flux:badge>
                        @endif
                    </div>
                    <div class="p-4 space-y-3">

                        {{-- Rôles assignés --}}
                        @if (count($assignedRoles))
                            <div class="space-y-1.5">
                                @foreach ($assignedRoles as $roleId => $data)
                                    <div
                                        wire:key="assigned-{{ $roleId }}"
                                        class="flex items-center gap-3 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/50"
                                    >
                                        {{-- Star primary --}}
                                        <div class="shrink-0">
                                            @if ($data['is_primary'])
                                                <flux:icon name="star" variant="solid" class="size-4 text-yellow-400" />
                                            @else
                                                <flux:icon name="shield-check" class="size-4 text-zinc-300 dark:text-zinc-600" />
                                            @endif
                                        </div>

                                        {{-- Infos --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                    {{ $data['name'] }}
                                                </span>
                                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                                    {{ $data['key'] }}
                                                </flux:badge>
                                                @if ($data['is_primary'])
                                                    <flux:badge size="sm" color="yellow" inset="top bottom">
                                                        {{ __('Primary') }}
                                                    </flux:badge>
                                                @endif
                                            </div>
                                            @if ($data['expires_at'])
                                                <p class="mt-0.5 text-xs text-zinc-400">
                                                    <flux:icon name="clock" class="mr-0.5 inline size-3" />
                                                    {{ __('Expires') }} {{ \Carbon\Carbon::parse($data['expires_at'])->format('d M Y') }}
                                                </p>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex shrink-0 items-center gap-1">
                                            @if (!$data['is_primary'])
                                                <flux:button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="star"
                                                    inset="top bottom"
                                                    x-on:click="$wire.setPrimary({{ $roleId }})"
                                                    title="{{ __('Set as primary') }}"
                                                />
                                            @endif
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                inset="top bottom"
                                                x-on:click="confirm('{{ __('Revoke this role?') }}') && $wire.revokeRole({{ $roleId }})"
                                                class="text-red-400 hover:text-red-500"
                                            />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-zinc-200 py-5 dark:border-zinc-700">
                                <flux:icon name="shield-exclamation" class="mb-1.5 size-7 text-zinc-300 dark:text-zinc-600" />
                                <p class="text-sm text-zinc-400">{{ __('No roles assigned yet.') }}</p>
                            </div>
                        @endif

                        {{-- Ajouter un rôle --}}
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
                                <input
                                    type="text"
                                    placeholder="{{ __('Search available roles...') }}"
                                    x-on:input="$wire.set('roleSearch', $event.target.value)"
                                    class="w-full bg-transparent text-sm text-zinc-700 placeholder-zinc-400 outline-none dark:text-zinc-300"
                                />
                            </div>

                            <div class="max-h-36 overflow-y-auto">
                                @forelse ($this->availableRoles as $role)
                                    <label
                                        wire:key="available-{{ $role->id }}"
                                        class="flex cursor-pointer items-center gap-3 border-b border-zinc-50 px-3 py-2.5 last:border-0 hover:bg-zinc-50 dark:border-zinc-800/50 dark:hover:bg-zinc-800/30"
                                    >
                                        <input
                                            type="radio"
                                            name="newRoleId"
                                            value="{{ $role->id }}"
                                            x-on:change="$wire.set('newRoleId', {{ $role->id }})"
                                            {{ (int) $newRoleId === $role->id ? 'checked' : '' }}
                                            class="text-blue-600"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                {{ $role->display_name }}
                                            </p>
                                            @if ($role->description)
                                                <p class="text-xs text-zinc-400">{{ $role->description }}</p>
                                            @endif
                                        </div>
                                        <flux:badge size="sm" color="zinc" inset="top bottom">
                                            {{ $role->name }}
                                        </flux:badge>
                                    </label>
                                @empty
                                    <div class="flex items-center justify-center py-4">
                                        <p class="text-sm text-zinc-400">
                                            {{ count($assignedRoles) ? __('All roles already assigned.') : __('No roles available.') }}
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Options du nouveau rôle --}}
                        @if ($newRoleId)
                            <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 dark:bg-blue-950/20">
                                <flux:input
                                    type="date"
                                    label="{{ __('Expires at') }}"
                                    description="{{ __('Optional') }}"
                                    x-on:change="$wire.set('newExpiresAt', $event.target.value)"
                                    x-bind:value="$wire.newExpiresAt"
                                />
                                <flux:input
                                    label="{{ __('Notes') }}"
                                    placeholder="{{ __('Reason...') }}"
                                    x-on:input="$wire.set('newNotes', $event.target.value)"
                                    x-bind:value="$wire.newNotes"
                                />
                                <div class="col-span-2 flex items-center justify-between">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            x-on:change="$wire.set('newIsPrimary', $event.target.checked)"
                                            x-bind:checked="$wire.newIsPrimary"
                                            class="rounded border-zinc-300 text-blue-600"
                                        />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ __('Set as primary role') }}
                                        </span>
                                    </label>
                                    <flux:button
                                        type="button"
                                        x-on:click="$wire.addRole()"
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                    >
                                        {{ __('Add role') }}
                                    </flux:button>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
