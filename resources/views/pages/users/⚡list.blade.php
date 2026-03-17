<?php
use Livewire\Component;
use Livewire\WithPagination;
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

    #[Url(history: true)]
    public bool $showTrashed = false;

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

    public function toggleTrashed(): void
    {
        $this->showTrashed = !$this->showTrashed;
        $this->filterStatus = '';
        $this->filterRole = '';
        $this->search = '';
        $this->resetPage();
    }

    #[On('user-created')]
    #[On('user-updated')]
    #[On('user-deleted')]
    #[On('user-restored')]
    #[On('user-force-deleted')]
    public function refreshUsers(): void
    {
        $this->resetPage();
    }

    public function restore(int $id): void
    {
        try {
            User::withTrashed()->findOrFail($id)->restore();

            $this->dispatch('user-restored');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('User restored'),
                message: __('The user has been restored successfully.'),
            );
        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Restore failed'),
                message: __('An error occurred while restoring the user.'),
            );
        }
    }

    public function forceDelete(int $id): void
    {
        try {
            User::withTrashed()->findOrFail($id)->forceDelete();

            $this->dispatch('user-force-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('User permanently deleted'),
                message: __('The user has been permanently deleted.'),
            );
        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the user.'),
            );
        }
    }

    public function getUsersProperty()
    {
        return User::query()
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->with(['primaryRole.role', 'activeUserRoles.role'])
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('phone', 'like', "%{$this->search}%")
                )
            )
            ->when(!$this->showTrashed && $this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when(!$this->showTrashed && $this->filterRole, fn($q) => $q->withRole($this->filterRole))
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

        @if (!$showTrashed)
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
        @endif

        <flux:spacer />

        {{-- Toggle Trash --}}
        <flux:button
            wire:click="toggleTrashed"
            :variant="$showTrashed ? 'danger' : 'ghost'"
            icon="trash"
            size="sm"
        >
            {{ $showTrashed ? __('View active') : __('View trash') }}
        </flux:button>

        @if (!$showTrashed)
            <flux:button variant="primary" wire:click="$dispatch('open-create-user')">
                {{ __('Add User') }}
            </flux:button>
        @endif
    </div>

    {{-- Banner trash --}}
    @if ($showTrashed)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <flux:icon name="trash" class="size-4 text-red-500" />
            <p class="text-sm text-red-700 dark:text-red-400">
                {{ __('Showing deleted users. You can restore or permanently delete them.') }}
            </p>
        </div>
    @endif

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

            @if (!$showTrashed)
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'status'"
                    :direction="$sortDirection"
                    wire:click="sort('status')"
                >
                    {{ __('Status') }}
                </flux:table.column>
            @endif

            <flux:table.column
                sortable
                :sorted="$sortBy === 'last_login_at'"
                :direction="$sortDirection"
                wire:click="sort('last_login_at')"
            >
                {{ $showTrashed ? __('Deleted') : __('Last login') }}
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
                <flux:table.row :key="$user->id" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                    {{-- Avatar + Nom --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                size="sm"
                                src="{{ $user->avatar }}"
                                name="{{ $user->fullName() }}"
                                class="{{ $showTrashed ? 'grayscale' : '' }}"
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

                    @if (!$showTrashed)
                        {{-- Roles --}}
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->activeUserRoles as $userRole)
                                    <flux:badge size="sm" :color="$userRole->is_primary ? 'blue' : 'zinc'" inset="top bottom">
                                        {{ $userRole->role?->display_name ?? $userRole->role?->name }}
                                    </flux:badge>
                                @empty
                                    <span class="text-sm text-zinc-400">—</span>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$this->statusColor($user->status ?? 'inactive')" inset="top bottom">
                                {{ ucfirst($user->status ?? 'inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                    @endif

                    {{-- Last login / Deleted at --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        @if ($showTrashed)
                            <span class="text-red-500">{{ $user->deleted_at?->diffForHumans() ?? '—' }}</span>
                        @else
                            {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                        @endif
                    </flux:table.cell>

                    {{-- Created at --}}
                    <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $user->created_at->format('d M Y') }}
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        @if ($showTrashed)
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="arrow-path"
                                        wire:click="restore({{ $user->id }})"
                                    >
                                        {{ __('Restore') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="forceDelete({{ $user->id }})"
                                        wire:confirm="{{ __('Permanently delete this user? This action cannot be undone.') }}"
                                    >
                                        {{ __('Delete permanently') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @else
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="pencil-square"
                                        wire:click="$dispatch('edit-user', { id: {{ $user->id }} })"
                                    >
                                        {{ __('Edit') }}
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
                        @endif
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon
                                name="{{ $showTrashed ? 'trash' : 'users' }}"
                                class="mb-3 size-10 text-zinc-300 dark:text-zinc-600"
                            />
                            @if ($showTrashed)
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('No deleted users found.') }}
                                </p>
                            @elseif ($this->search || $this->filterStatus || $this->filterRole)
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
    <livewire:pages::users.edit />
    <livewire:pages::users.delete />
</div>
