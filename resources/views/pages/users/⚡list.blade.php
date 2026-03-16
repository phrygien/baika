<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\User;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'created_at';

    #[Url(history: true)]
    public string $sortDirection = 'desc';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterRole = '';

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

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterRole(): void { $this->resetPage(); }

    #[On('user-created')]
    #[On('user-updated')]
    #[On('user-deleted')]
    public function refreshUsers(): void
    {
        unset($this->users);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['primaryRole.role', 'activeUserRoles.role'])
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('phone', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when($this->filterRole, fn($q) =>
                $q->withRole($this->filterRole)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function statusColor(string $status): string
    {
        return match($status) {
            'active'   => 'green',
            'inactive' => 'zinc',
            'banned'   => 'red',
            'pending'  => 'yellow',
            default    => 'zinc',
        };
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your users') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search users...') }}"
            />
        </div>

        <flux:select wire:model.live="filterStatus" placeholder="{{ __('All statuses') }}" class="w-40">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            <flux:select.option value="banned">{{ __('Banned') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filterRole" placeholder="{{ __('All roles') }}" class="w-40">
            <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
            <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
            <flux:select.option value="supplier">{{ __('Supplier') }}</flux:select.option>
            <flux:select.option value="transporter">{{ __('Transporter') }}</flux:select.option>
            <flux:select.option value="customer">{{ __('Customer') }}</flux:select.option>
        </flux:select>

        <flux:spacer />

        <flux:button variant="primary" wire:click="$dispatch('open-create-user')">
            {{ __('Add User') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'first_name'"
                :direction="$sortDirection"
                wire:click="sort('first_name')"
            >
                {{ __('User') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'email'"
                :direction="$sortDirection"
                wire:click="sort('email')"
            >
                {{ __('Email') }}
            </flux:table.column>

            <flux:table.column>{{ __('Roles') }}</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'status'"
                :direction="$sortDirection"
                wire:click="sort('status')"
            >
                {{ __('Status') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'last_login_at'"
                :direction="$sortDirection"
                wire:click="sort('last_login_at')"
            >
                {{ __('Last login') }}
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'created_at'"
                :direction="$sortDirection"
                wire:click="sort('created_at')"
            >
                {{ __('Created') }}
            </flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">

                    {{-- Avatar + Nom --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                size="sm"
                                src="{{ $user->avatar }}"
                                name="{{ $user->fullName() }}"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $user->fullName() ?: $user->name }}
                                </p>
                                <p class="text-xs text-zinc-400">{{ $user->phone ?? '—' }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Email --}}
                    <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $user->email }}
                    </flux:table.cell>

                    {{-- Roles --}}
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->activeUserRoles as $userRole)
                                <flux:badge
                                    size="sm"
                                    :color="$userRole->is_primary ? 'blue' : 'zinc'"
                                    inset="top bottom"
                                >
                                    {{ $userRole->role?->display_name ?? $userRole->role?->name }}
                                </flux:badge>
                            @empty
                                <span class="text-sm text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <flux:badge
                            size="sm"
                            :color="$this->statusColor($user->status ?? 'inactive')"
                            inset="top bottom"
                        >
                            {{ ucfirst($user->status ?? 'inactive') }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Last login --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                    </flux:table.cell>

                    {{-- Created at --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $user->created_at->format('d M Y') }}
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="eye"
                                    wire:click="$dispatch('view-user', { id: {{ $user->id }} })"
                                >
                                    {{ __('View') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="pencil-square"
                                    wire:click="$dispatch('edit-user', { id: {{ $user->id }} })"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="shield-check"
                                    wire:click="$dispatch('manage-roles', { id: {{ $user->id }} })"
                                >
                                    {{ __('Manage roles') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="$dispatch('delete-user', { id: {{ $user->id }} })"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="users" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            @if ($this->search || $this->filterStatus || $this->filterRole)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('Aucun résultat pour les filtres appliqués.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Essayez de modifier votre recherche ou vos filtres.') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('Aucun utilisateur trouvé.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('Commencez par ajouter un utilisateur.') }}
                                </p>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::users.create />
</div>
