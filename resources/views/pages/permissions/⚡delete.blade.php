<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Permission;

new class extends Component
{
    public ?int $permissionId = null;
    public string $permissionName = '';

    #[On('delete-permission')]
    public function loadPermission(int $id): void
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId   = $permission->id;
        $this->permissionName = $permission->display_name;

        Flux::modal('delete-permission')->show();
    }

    public function delete(): void
    {
        Permission::findOrFail($this->permissionId)->delete();

        $this->reset();
        $this->dispatch('permission-deleted');
        Flux::modal('delete-permission')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Permission deleted'),
            message: __('The permission has been delete successfully.'),
        );
    }
};
?>

<flux:modal name="delete-permission" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete permission?') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('You\'re about to delete') }}
                <span class="font-medium text-zinc-800 dark:text-white">{{ $permissionName }}</span>.<br>
                {{ __('This action cannot be reversed.') }}
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger">
                {{ __('Delete permission') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
