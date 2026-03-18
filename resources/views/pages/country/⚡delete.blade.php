<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Country;
use Flux\Flux;

new class extends Component
{
    public ?int $countryId = null;
    public string $countryName = '';
    public string $countryCode = '';

    #[On('delete-country')]
    public function loadCountry(int $id): void
    {
        $country = Country::findOrFail($id);

        $this->countryId   = $country->id;
        $this->countryName = $country->name;
        $this->countryCode = $country->code;

        Flux::modal('delete-country')->show();
    }

    public function delete(): void
    {
        try {
            Country::findOrFail($this->countryId)->delete();

            $this->dispatch('country-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Country deleted'),
                message: __(':name has been deleted successfully.', ['name' => $this->countryName]),
            );

            Flux::modal('delete-country')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the country. It may have related states or cities.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-country" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete country?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">
                            {{ $countryName }}
                        </span>
                        @if ($countryCode)
                            <flux:badge size="sm" color="zinc" inset="top bottom" class="ml-1">
                                {{ $countryCode }}
                            </flux:badge>
                        @endif
                        .<br>
                        {{ __('This will also delete all related states and cities.') }}
                    </flux:text>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="trash">
                        {{ __('Delete country') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
