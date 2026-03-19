<?php

use Livewire\Component;

new class extends Component
{
    function save()
    {
        dd('Saved !');
    }
};
?>

<div>
    <flux:button variant="primary" wire:click="save">
        {{ __('save') }}
    </flux:button>
</div>
