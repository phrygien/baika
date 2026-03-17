<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;

new class extends Component
{
    public ?int $userId = null;
    public string $userName = '';
    public array $assignedRoles = [];

    public ?int $newRoleId = null;
    public string $newExpiresAt = '';
    public string $newNotes = '';
    public bool $newIsPrimary = false;
    public string $roleSearch = '';

    #[On('manage-roles')]
    public function loadUser(int $id): void
    {
        $user = User::with('activeUserRoles.role')->findOrFail($id);

        $this->userId   = $user->id;
        $this->userName = $user->fullName() ?: $user->name;

        $this->refreshAssignedRoles($user);

        $this->newRoleId    = null;
        $this->newExpiresAt = '';
        $this->newNotes     = '';
        $this->newIsPrimary = false;
        $this->roleSearch   = '';

        $this->resetValidation();

        // Notifier le parent d'ouvrir le modal
        $this->dispatch('open-manage-roles-modal')->to('pages::users.list');
    }

    protected function refreshAssignedRoles(User $user): void
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

        $this->refreshAssignedRoles($user);

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

        $this->refreshAssignedRoles($user);
    }

    public function save(): void
    {
        $this->dispatch('close-manage-roles-modal')->to('pages::users.list');
        $this->dispatch('user-updated')->to('pages::users.list');
    }

    public function render()
    {
        return <<<'BLADE'
        <div></div>
        BLADE;
    }
};
?>
