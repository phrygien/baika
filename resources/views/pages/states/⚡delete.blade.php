<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\State;
use Flux\Flux;

new class extends Component
{
    public ?int $stateId = null;
    public string $stateName = '';
    public string $stateCode = '';
    public string $countryName = '';

    #[On('delete-state')]
    public function loadState(int $id): void
    {
        $state = State::with('country')->findOrFail($id);

        $this->stateId     = $state->id;
        $this->stateName   = $state->name;
        $this->stateCode   = $state->code ?? '';
        $this->countryName = $state->country?->name ?? '';

        Flux::modal('delete-state')->show();
    }

    public function delete(): void
    {
        try {
            State::findOrFail($this->stateId)->delete();

            $this->dispatch('state-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('State deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->stateName]),
            );

            Flux::modal('delete-state')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the state. It may have related cities.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-state" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete state?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $stateName }}</span>
                        @if ($stateCode)
                            <flux:badge size="sm" color="zinc" inset="top bottom" class="ml-1">
                                {{ $stateCode }}
                            </flux:badge>
                        @endif
                        @if ($countryName)
                            <span class="text-zinc-400"> · {{ $countryName }}</span>
                        @endif
                        .<br>
                        {{ __('This will also delete all related cities.') }}
                    </flux:text>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete state') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
