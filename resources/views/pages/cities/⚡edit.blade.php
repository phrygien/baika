<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\City;
use App\Models\State;
use Flux\Flux;

new class extends Component
{
    public ?int $cityId = null;
    public string $cityName = '';

    public string $name = '';
    public string $postal_code = '';
    public string $latitude = '';
    public string $longitude = '';
    public bool $is_active = true;

    #[On('edit-city')]
    public function loadCity(int $id): void
    {
        $city = City::findOrFail($id);

        $this->cityId      = $city->id;
        $this->cityName    = $city->name;
        $this->name        = $city->name;
        $this->postal_code = $city->postal_code ?? '';
        $this->latitude    = $city->latitude !== null ? (string) $city->latitude : '';
        $this->longitude   = $city->longitude !== null ? (string) $city->longitude : '';
        $this->is_active   = $city->is_active;

        $this->resetValidation();
        Flux::modal('edit-city')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'is_active'   => 'boolean',
        ]);

        try {
            City::findOrFail($this->cityId)->update([
                'name'        => $this->name,
                'postal_code' => $this->postal_code ?: null,
                'latitude'    => $this->latitude !== '' ? $this->latitude : null,
                'longitude'   => $this->longitude !== '' ? $this->longitude : null,
                'is_active'   => $this->is_active,
            ]);

            $this->dispatch('city-updated');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('City updated'),
                message: __(':name has been updated successfully.', ['name' => $this->name]),
            );

            Flux::modal('edit-city')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Update failed'),
                message: __('An error occurred while updating the city.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="edit-city" class="w-full max-w-lg">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Edit City') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Update the city details.') }}</flux:text>
                </div>

                {{-- Nom --}}
                <flux:input
                    wire:model="name"
                    label="{{ __('City name') }}"
                    placeholder="Paris"
                />

                {{-- Postal code --}}
                <flux:input
                    wire:model="postal_code"
                    label="{{ __('Postal code') }}"
                    placeholder="75000"
                />

                {{-- Coordonnées --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="latitude"
                        label="{{ __('Latitude') }}"
                        placeholder="48.8566"
                        type="number"
                        step="any"
                    />
                    <flux:input
                        wire:model="longitude"
                        label="{{ __('Longitude') }}"
                        placeholder="2.3522"
                        type="number"
                        step="any"
                    />
                </div>

                {{-- Status --}}
                <flux:field variant="inline">
                    <flux:label>{{ __('Active') }}</flux:label>
                    <flux:switch wire:model="is_active" />
                </flux:field>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
