<?php
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Role;
use App\Models\Permission;

new class extends Component
{
    public ?int $roleId = null;

    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public array $selectedPermissions = [];
    public string $permissionSearch = '';

    #[On('edit-role')]
    public function loadRole(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId              = $role->id;
        $this->name                = $role->name;
        $this->display_name        = $role->display_name;
        $this->description         = $role->description ?? '';
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->permissionSearch    = '';

        $this->resetValidation();
        Flux::modal('edit-role')->show();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->when($this->permissionSearch, fn($q) =>
                $q->where('name', 'like', "%{$this->permissionSearch}%")
                  ->orWhere('display_name', 'like', "%{$this->permissionSearch}%")
                  ->orWhere('group', 'like', "%{$this->permissionSearch}%")
            )
            ->orderBy('group')
            ->orderBy('display_name')
            ->get()
            ->groupBy('group');
    }

    public function toggleGroup(string $group): void
    {
        $groupIds = Permission::where('group', $group)->pluck('id')->toArray();
        $allSelected = count(array_intersect($groupIds, $this->selectedPermissions)) === count($groupIds);

        if ($allSelected) {
            $this->selectedPermissions = array_values(
                array_filter($this->selectedPermissions, fn($p) => !in_array($p, $groupIds))
            );
        } else {
            $this->selectedPermissions = array_values(
                array_unique(array_merge($this->selectedPermissions, $groupIds))
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'         => "required|string|min:2|max:255|unique:roles,name,{$this->roleId}",
            'display_name' => 'required|string|min:2|max:255',
            'description'  => 'nullable|string|max:500',
            'selectedPermissions' => 'array',
        ]);

        $role = Role::findOrFail($this->roleId);

        $role->update([
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'description'  => $this->description ?: null,
        ]);

        $role->permissions()->sync($this->selectedPermissions);

        $this->reset();
        $this->dispatch('role-updated');
        Flux::modal('edit-role')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Role updated'),
            message: __('The role has been updated successfully.'),
        );
    }
};
?>

<flux:modal name="edit-role" class="w-full max-w-2xl">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <flux:heading size="lg">{{ __('Edit Role') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Update role details and permissions.') }}</flux:text>
        </div>

        {{-- Informations --}}
        <div class="space-y-4">
            <flux:input
                wire:model="name"
                label="{{ __('Clé technique') }}"
                placeholder="ex: moderator"
                description="{{ __('Identifiant unique, minuscules sans espace') }}"
            />

            <flux:input
                wire:model="display_name"
                label="{{ __('Nom affiché') }}"
                placeholder="ex: Modérateur"
            />

            <flux:textarea
                wire:model="description"
                label="{{ __('Description') }}"
                placeholder="{{ __('Décrivez le rôle et ses responsabilités...') }}"
                rows="2"
            />
        </div>

        <flux:separator variant="subtle" />

        {{-- Permissions --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Permissions') }}</flux:heading>
                <flux:badge size="sm" color="blue" inset="top bottom">
                    {{ count($this->selectedPermissions) }} {{ __('sélectionnées') }}
                </flux:badge>
            </div>

            <flux:input
                wire:model.live.debounce.200ms="permissionSearch"
                icon="magnifying-glass"
                placeholder="{{ __('Rechercher une permission...') }}"
                size="sm"
            />

            <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                @forelse ($this->permissions as $group => $permissions)
                    <div class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">

                        {{-- Group header --}}
                        <button
                            wire:click="toggleGroup('{{ $group }}')"
                            type="button"
                            class="flex w-full items-center justify-between bg-zinc-50 px-4 py-2 text-left dark:bg-zinc-800/50"
                        >
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ $group }}
                            </span>
                            <span class="text-xs text-zinc-400">
                                {{ $permissions->whereIn('id', $this->selectedPermissions)->count() }}/{{ $permissions->count() }}
                            </span>
                        </button>

                        {{-- Permissions list --}}
                        <div class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                            @foreach ($permissions as $permission)
                                <label
                                    wire:key="edit-perm-{{ $permission->id }}"
                                    class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
                                >
                                    <flux:checkbox
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->id }}"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $permission->display_name }}
                                        </p>
                                        <p class="text-xs text-zinc-400">{{ $permission->name }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon name="magnifying-glass" class="mb-2 size-6 text-zinc-300" />
                        <p class="text-sm text-zinc-400">{{ __('Aucune permission trouvée.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary">
                {{ __('Save changes') }}
            </flux:button>
        </div>

    </div>
</flux:modal>
