<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Supplier;
use Flux\Flux;

new class extends Component
{
    public ?int $supplierId = null;
    public string $shopName = '';
    public string $ownerName = '';
    public string $currentStatus = '';

    public string $action = 'approved'; // approved | rejected | suspended
    public string $rejectionReason = '';
    public string $commissionRate = '10';
    public bool $isVerified = false;

    #[On('approve-supplier')]
    public function loadSupplier(int $id): void
    {
        $supplier = Supplier::with('user')->findOrFail($id);

        $this->supplierId      = $supplier->id;
        $this->shopName        = $supplier->shop_name;
        $this->ownerName       = $supplier->user?->fullName() ?: $supplier->user?->name ?? '';
        $this->currentStatus   = $supplier->status;
        $this->commissionRate  = (string) $supplier->commission_rate;
        $this->isVerified      = $supplier->is_verified;
        $this->rejectionReason = '';

        // Pré-sélectionner l'action logique selon le status actuel
        $this->action = match($supplier->status) {
            'approved'  => 'suspended',
            'suspended' => 'approved',
            default     => 'approved',
        };

        $this->resetValidation();
        Flux::modal('approve-supplier')->show();
    }

    public function save(): void
    {
        $this->validate([
            'action'           => 'required|in:approved,rejected,suspended',
            'rejectionReason'  => 'required_if:action,rejected|nullable|string|max:1000',
            'commissionRate'   => 'required_if:action,approved|nullable|numeric|min:0|max:100',
        ]);

        try {
            $data = [
                'status'           => $this->action,
                'rejection_reason' => $this->action === 'rejected' ? $this->rejectionReason : null,
            ];

            if ($this->action === 'approved') {
                $data['approved_at']  = now();
                $data['approved_by']  = auth()->id();
                $data['is_verified']  = $this->isVerified;
                $data['commission_rate'] = $this->commissionRate;
            }

            if ($this->action === 'suspended') {
                $data['approved_at'] = null;
                $data['approved_by'] = null;
            }

            Supplier::findOrFail($this->supplierId)->update($data);

            $label = match($this->action) {
                'approved'  => __('approved'),
                'rejected'  => __('rejected'),
                'suspended' => __('suspended'),
            };

            $this->dispatch('supplier-updated');
            $this->dispatch(
                'notify',
                variant: $this->action === 'approved' ? 'success' : 'warning',
                title: __('Supplier :action', ['action' => $label]),
                message: __(':name has been :action successfully.', [
                    'name'   => $this->shopName,
                    'action' => $label,
                ]),
            );

            Flux::modal('approve-supplier')->close();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Action failed'),
                message: __('An error occurred while processing the supplier.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="approve-supplier" class="w-full max-w-lg">
        <form wire:submit="save">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Approve / Reject Supplier') }}</flux:heading>
                    <flux:text class="mt-1">
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $shopName }}</span>
                        @if ($ownerName)
                            <span class="text-zinc-400"> · {{ $ownerName }}</span>
                        @endif
                    </flux:text>
                </div>

                {{-- Status actuel --}}
                <div class="flex items-center gap-3 rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                    <flux:icon name="information-circle" class="size-4 shrink-0 text-zinc-400" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Current status') }} :
                        <span class="font-medium">
                            @switch($currentStatus)
                                @case('approved') <span class="text-green-600 dark:text-green-400">{{ __('Approved') }}</span> @break
                                @case('pending') <span class="text-yellow-600 dark:text-yellow-400">{{ __('Pending') }}</span> @break
                                @case('rejected') <span class="text-red-600 dark:text-red-400">{{ __('Rejected') }}</span> @break
                                @case('suspended') <span class="text-zinc-600 dark:text-zinc-400">{{ __('Suspended') }}</span> @break
                            @endswitch
                        </span>
                    </p>
                </div>

                {{-- Action selector --}}
                <div class="space-y-2">
                    <flux:label>{{ __('Action') }}</flux:label>
                    <div class="grid grid-cols-3 gap-3">

                        {{-- Approve --}}
                        <label
                            x-on:click="$wire.set('action', 'approved')"
                            class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 transition-all
                                {{ $action === 'approved'
                                    ? 'border-green-500 bg-green-50 dark:bg-green-950/20'
                                    : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}"
                        >
                            <div class="flex size-9 items-center justify-center rounded-full {{ $action === 'approved' ? 'bg-green-100 dark:bg-green-900/40' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <flux:icon name="check-circle" class="size-5 {{ $action === 'approved' ? 'text-green-600' : 'text-zinc-400' }}" />
                            </div>
                            <span class="text-sm font-medium {{ $action === 'approved' ? 'text-green-700 dark:text-green-400' : 'text-zinc-500' }}">
                                {{ __('Approve') }}
                            </span>
                        </label>

                        {{-- Reject --}}
                        <label
                            x-on:click="$wire.set('action', 'rejected')"
                            class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 transition-all
                                {{ $action === 'rejected'
                                    ? 'border-red-500 bg-red-50 dark:bg-red-950/20'
                                    : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}"
                        >
                            <div class="flex size-9 items-center justify-center rounded-full {{ $action === 'rejected' ? 'bg-red-100 dark:bg-red-900/40' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <flux:icon name="x-circle" class="size-5 {{ $action === 'rejected' ? 'text-red-600' : 'text-zinc-400' }}" />
                            </div>
                            <span class="text-sm font-medium {{ $action === 'rejected' ? 'text-red-700 dark:text-red-400' : 'text-zinc-500' }}">
                                {{ __('Reject') }}
                            </span>
                        </label>

                        {{-- Suspend --}}
                        <label
                            x-on:click="$wire.set('action', 'suspended')"
                            class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 transition-all
                                {{ $action === 'suspended'
                                    ? 'border-zinc-500 bg-zinc-100 dark:bg-zinc-800'
                                    : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}"
                        >
                            <div class="flex size-9 items-center justify-center rounded-full {{ $action === 'suspended' ? 'bg-zinc-200 dark:bg-zinc-700' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <flux:icon name="pause-circle" class="size-5 {{ $action === 'suspended' ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400' }}" />
                            </div>
                            <span class="text-sm font-medium {{ $action === 'suspended' ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-500' }}">
                                {{ __('Suspend') }}
                            </span>
                        </label>

                    </div>
                    <flux:error name="action" />
                </div>

                {{-- Options conditionnelles --}}
                @if ($action === 'approved')
                    <div class="space-y-4 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/10">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="commissionRate"
                                label="{{ __('Commission rate') }}"
                                type="number"
                                min="0"
                                max="100"
                                step="0.5"
                                description="{{ __('Percentage (%)') }}"
                            />
                            <div class="flex items-end pb-1">
                                <flux:field variant="inline">
                                    <flux:label>{{ __('Mark as verified') }}</flux:label>
                                    <flux:switch wire:model="isVerified" />
                                </flux:field>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($action === 'rejected')
                    <div class="space-y-2 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/10">
                        <flux:textarea
                            wire:model="rejectionReason"
                            label="{{ __('Rejection reason') }}"
                            placeholder="{{ __('Explain why this supplier is being rejected...') }}"
                            rows="3"
                            description="{{ __('This reason will be recorded.') }}"
                        />
                        <flux:error name="rejectionReason" />
                    </div>
                @endif

                @if ($action === 'suspended')
                    <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-zinc-500" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('The supplier will be suspended and will no longer be visible. You can reactivate it at any time.') }}
                        </p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="submit"
                        :variant="$action === 'approved' ? 'primary' : ($action === 'rejected' ? 'danger' : 'filled')"
                        :icon="$action === 'approved' ? 'check' : ($action === 'rejected' ? 'x-mark' : 'pause')"
                    >
                        {{ match($action) {
                            'approved'  => __('Approve supplier'),
                            'rejected'  => __('Reject supplier'),
                            'suspended' => __('Suspend supplier'),
                        } }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
