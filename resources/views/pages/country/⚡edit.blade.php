<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Country;
use Flux\Flux;

new class extends Component
{
    public ?int $countryId = null;

    public string $name = '';
    public string $code = '';
    public string $dial_code = '';
    public string $currency_code = '';
    public string $currency_symbol = '';
    public string $flag_url = '';
    public bool $is_active = true;

    #[On('edit-country')]
    public function loadCountry(int $id): void
    {
        $country = Country::findOrFail($id);

        $this->countryId       = $country->id;
        $this->name            = $country->name;
        $this->code            = $country->code;
        $this->dial_code       = $country->dial_code ?? '';
        $this->currency_code   = $country->currency_code ?? '';
        $this->currency_symbol = $country->currency_symbol ?? '';
        $this->flag_url        = $country->flag_url ?? '';
        $this->is_active       = $country->is_active;

        $this->resetValidation();
        Flux::modal('edit-country')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name'            => 'required|string|max:255',
            'code'            => "required|string|size:2|unique:countries,code,{$this->countryId}",
            'dial_code'       => 'nullable|string|max:10',
            'currency_code'   => 'nullable|string|max:3',
            'currency_symbol' => 'nullable|string|max:10',
            'flag_url'        => 'nullable|url|max:500',
            'is_active'       => 'boolean',
        ]);

        try {
            Country::findOrFail($this->countryId)->update([
                'name'            => $this->name,
                'code'            => strtoupper($this->code),
                'dial_code'       => $this->dial_code ?: null,
                'currency_code'   => $this->currency_code ? strtoupper($this->currency_code) : null,
                'currency_symbol' => $this->currency_symbol ?: null,
                'flag_url'        => $this->flag_url ?: null,
                'is_active'       => $this->is_active,
            ]);

            $this->dispatch('country-updated');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('Country updated'),
                message: __(':name has been updated successfully.', ['name' => $this->name]),
            );

            Flux::modal('edit-country')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Update failed'),
                message: __('An error occurred while updating the country.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="edit-country" class="w-full max-w-lg">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Edit Country') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Update the country details.') }}</flux:text>
                </div>

                {{-- Nom + Code --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <flux:input
                            wire:model="name"
                            label="{{ __('Country name') }}"
                            placeholder="France"
                        />
                    </div>
                    <flux:input
                        wire:model="code"
                        label="{{ __('ISO code') }}"
                        placeholder="FR"
                        description="{{ __('2 letters') }}"
                        class="uppercase"
                    />
                </div>

                {{-- Dial code + Currency --}}
                <div class="grid grid-cols-3 gap-4">
                    <flux:input
                        wire:model="dial_code"
                        label="{{ __('Dial code') }}"
                        placeholder="+33"
                    />
                    <flux:input
                        wire:model="currency_code"
                        label="{{ __('Currency code') }}"
                        placeholder="EUR"
                        description="{{ __('3 letters') }}"
                        class="uppercase"
                    />
                    <flux:input
                        wire:model="currency_symbol"
                        label="{{ __('Symbol') }}"
                        placeholder="€"
                    />
                </div>

                {{-- Flag URL --}}
                <flux:input
                    wire:model="flag_url"
                    label="{{ __('Flag URL') }}"
                    placeholder="https://..."
                    icon="photo"
                />

                {{-- Preview flag --}}
                @if ($flag_url)
                    <div class="flex items-center gap-3 rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                        <img
                            src="{{ $flag_url }}"
                            alt="Flag preview"
                            class="h-5 w-8 rounded-sm object-cover shadow-sm"
                            onerror="this.style.display='none'"
                        />
                        <span class="text-sm text-zinc-500">{{ __('Flag preview') }}</span>
                    </div>
                @endif

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
