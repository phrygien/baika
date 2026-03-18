<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\City;
use Flux\Flux;

new class extends Component
{
    public ?int $cityId = null;
    public string $cityName = '';
    public string $stateName = '';

    #[On('delete-city')]
    public function loadCity(int $id): void
    {
        $city = City::with('state')->findOrFail($id);

        $this->cityId    = $city->id;
        $this->cityName  = $city->name;
        $this->stateName = $city->state?->name ?? '';

        Flux::modal('delete-city')->show();
    }

    public function delete(): void
    {
        try {
            City::findOrFail($this->cityId)->delete();

            $this->dispatch('city-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('City deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->cityName]),
            );

            Flux::modal('delete-city')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the city.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-city" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete city?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $cityName }}</span>
                        @if ($stateName)
                            <span class="text-zinc-400"> · {{ $stateName }}</span>
                        @endif
                        .<br>
                        {{ __('This action cannot be reversed.') }}
                    </flux:text>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete city') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
