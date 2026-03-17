<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\User;
use Flux\Flux;

new class extends Component
{
    public ?int $userId = null;
    public string $userName = '';
    public string $userEmail = '';

    #[On('delete-user')]
    public function loadUser(int $id): void
    {
        $user = User::findOrFail($id);

        $this->userId    = $user->id;
        $this->userName  = $user->fullName() ?: $user->name;
        $this->userEmail = $user->email;

        Flux::modal('delete-user')->show();
    }

    public function delete(): void
    {
        try {
            $user = User::findOrFail($this->userId);
            $user->delete();

            $this->dispatch('user-deleted');
            $this->dispatch(
                'notify',
                variant: 'success',
                title: __('User deleted'),
                message: __('The user has been deleted successfully.'),
            );

            Flux::modal('delete-user')->close();
            $this->reset();

        } catch (\Throwable $e) {
            $this->dispatch(
                'notify',
                variant: 'warning',
                title: __('Delete failed'),
                message: __('An error occurred while deleting the user.'),
            );
        }
    }
};
?>

<div>
    <flux:modal name="delete-user" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">

                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ __('Delete user?') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('You\'re about to delete') }}
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $userName }}</span>
                        <span class="text-zinc-400"> · {{ $userEmail }}</span>.<br>
                        {{ __('The user will be soft deleted and can be restored later.') }}
                    </flux:text>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger">
                        {{ __('Delete user') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </flux:modal>
</div>
