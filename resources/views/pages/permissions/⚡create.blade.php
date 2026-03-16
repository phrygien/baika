<?php
use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Models\Permission;

new class extends Component
{
    #[Validate('required|string|min:3|max:255|unique:permissions,name')]
    public string $name = '';

    #[Validate('required|string|min:3|max:255')]
    public string $display_name = '';

    #[Validate('required|string|max:100')]
    public string $group = '';

    public function save(): void
    {
        $this->validate();

        Permission::create([
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'group'        => $this->group,
        ]);

        $this->reset();
        $this->dispatch('permission-created');
        Flux::modal('create-permission')->close();

        $this->dispatch(
            'notify',
            variant: 'success', // success | danger | warning | info
            title: __('Permission created'),
            message: __('The permission has been created successfully.'),
        );
    }
};
?>

<flux:modal name="create-permission" class="w-full max-w-2xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Create Permission') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Create a new permission') }}</flux:text>
        </div>

        <flux:input
            wire:model.live="name"
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
            <flux:button wire:click="save" variant="primary">
                {{ __('Save changes') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
