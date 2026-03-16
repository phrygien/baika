<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Permission;
use Livewire\Attributes\On;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'group';

    #[Url(history: true)]
    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->with('roles')
            ->when($this->search, fn($query) =>
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('display_name', 'like', "%{$this->search}%")
                      ->orWhere('group', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[On('permission-created')]
    public function handlePermissionCreated(): void
    {
        unset($this->permissions);
    }

    #[On('permission-updated')]
    public function refreshPermissions(): void
    {
        unset($this->permissions);
    }

    #[On('permission-deleted')]
    public function refreshPermissionss(): void
    {
        unset($this->permissions);
    }
};
?>

<div>

    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Permissions') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Permissions') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>


    <div class="mb-4 flex items-center justify-between">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search permissions"
            />
        </div>

        <flux:modal.trigger name="create-permission">
            <flux:button variant="primary">
                {{ __('Add Permission') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table :paginate="$this->permissions">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'display_name'"
                :direction="$sortDirection"
                wire:click="sort('display_name')"
            >
                Nom affiché
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                Clé technique
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'group'"
                :direction="$sortDirection"
                wire:click="sort('group')"
            >
                Groupe
            </flux:table.column>

            <flux:table.column>Rôles</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->permissions as $permission)
                <flux:table.row :key="$permission->id">

                    <flux:table.cell variant="strong">
                        {{ $permission->display_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $permission->name }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $permission->group }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($permission->roles as $role)
                                <flux:badge size="sm" color="green" inset="top bottom">
                                    {{ $role->name }}
                                </flux:badge>
                            @empty
                                <span class="text-sm text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-permission', { id: {{ $permission->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-permission', { id: {{ $permission->id }} })"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="shield-exclamation" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('Aucun résultat pour') }} <span class="text-zinc-700 dark:text-zinc-200">"{{ $this->search }}"</span>
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Essayez un autre terme de recherche.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('Aucune permission trouvée.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Commencez par ajouter une permission.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>


    <livewire:pages::permissions.create />
    <livewire:pages::permissions.edit />
    <livewire:pages::permissions.delete />
</div>
