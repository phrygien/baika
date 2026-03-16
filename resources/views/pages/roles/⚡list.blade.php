<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Role;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'name';

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

    #[On('role-created')]
    #[On('role-updated')]
    #[On('role-deleted')]
    public function refreshRoles(): void
    {
        unset($this->roles);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount('permissions')
            ->when($this->search, fn($query) =>
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('display_name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Roles') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Roles') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex items-center justify-between">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search roles') }}"
            />
        </div>
        <flux:modal.trigger name="edit-profile">
            <flux:button variant="primary" x-on:click="$flux.modal('create-role').show()">
                {{ __('Add Role') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table :paginate="$this->roles">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'display_name'"
                :direction="$sortDirection"
                wire:click="sort('display_name')"
            >
                {{ __('Nom affiché') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                {{ __('Clé technique') }}
            </flux:table.column>

            <flux:table.column>{{ __('Description') }}</flux:table.column>

            <flux:table.column>{{ __('Permissions') }}</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->roles as $role)
                <flux:table.row :key="$role->id">

                    <flux:table.cell variant="strong">
                        {{ $role->display_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $role->name }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                        {{ $role->description ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $role->permissions_count }} {{ __('permissions') }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-role', { id: {{ $role->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="document-duplicate"
                                    wire:click="$dispatch('duplicate-role', { id: {{ $role->id }} })"
                                >
                                    {{ __('Duplicate') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-role', { id: {{ $role->id }} })"
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
                                    {{ __('Aucun rôle trouvé.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Commencez par ajouter un rôle.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::roles.create />
    <livewire:pages::roles.edit />
    <livewire:pages::roles.delete />
</div>
