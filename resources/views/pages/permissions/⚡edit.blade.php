<?php
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Permission;

new class extends Component
{
    public ?int $permissionId = null;

    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('required|string|min:3|max:255')]
    public string $display_name = '';

    #[Validate('required|string|max:100')]
    public string $group = '';

    #[On('edit-permission')]
    public function loadPermission(int $id): void
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId = $permission->id;
        $this->name         = $permission->name;
        $this->display_name = $permission->display_name;
        $this->group        = $permission->group;

        $this->resetValidation();
        Flux::modal('edit-permission')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name'         => "required|string|min:3|max:255|unique:permissions,name,{$this->permissionId}",
            'display_name' => 'required|string|min:3|max:255',
            'group'        => 'required|string|max:100',
        ]);

        Permission::findOrFail($this->permissionId)->update([
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'group'        => $this->group,
        ]);

        $this->reset();
        $this->dispatch('permission-updated');
        Flux::modal('edit-permission')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('Permission updated'),
            message: __('The permission has been updateted successfully.'),
        );
    }
};
?>

<flux:modal name="edit-permission" class="w-full max-w-2xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Edit Permission') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Update the permission details') }}</flux:text>
        </div>

        <flux:input
            wire:model="name"
            label="{{ __('Clé technique') }}"
            placeholder="ex: orders.cancel"
            description="{{ __('Identifiant unique, minuscules avec point') }}"
        />

        <flux:input
            wire:model="display_name"
            label="{{ __('Nom affiché') }}"
            placeholder="ex: Annuler une commande"
        />

        <flux:input
            wire:model="group"
            label="{{ __('Groupe') }}"
            placeholder="ex: orders"
        />

        <div class="flex">
            <flux:spacer />
            <flux:button variant="ghost" x-on:click="$flux.modal('edit-permission').close()">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button wire:click="save" variant="primary">
                {{ __('Save changes') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
