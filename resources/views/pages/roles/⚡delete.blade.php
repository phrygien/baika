<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Role;

new class extends Component
{
    public ?int $roleId = null;
    public string $roleName = '';

    #[On('delete-role')]
    public function loadRole(int $id): void
    {
        $role = Role::findOrFail($id);

        $this->roleId   = $role->id;
        $this->roleName = $role->display_name;

        Flux::modal('delete-role')->show();
    }

    public function delete(): void
    {
        $role = Role::findOrFail($this->roleId);
        $role->permissions()->detach();
        $role->delete();

        $this->reset();
        $this->dispatch('role-deleted');
        Flux::modal('delete-role')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Role Deleted'),
            message: __('The role has been deleted successfully.'),
        );
    }
};
?>

<flux:modal name="delete-role" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete role?') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('You\'re about to delete') }}
                <span class="font-medium text-zinc-800 dark:text-white">{{ $roleName }}</span>.<br>
                {{ __('This action cannot be reversed.') }}
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger">
                {{ __('Delete role') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
