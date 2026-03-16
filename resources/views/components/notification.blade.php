@props([
    'position' => 'top-center',
])

@php
$isCenter = str_contains($position, 'center');
$isTop    = str_starts_with($position, 'top');

$positionClass = match($position) {
    'top-right'     => 'top-0 right-0',
    'top-center'    => 'top-0 left-0 right-0',
    'top-left'      => 'top-0 left-0',
    'bottom-right'  => 'bottom-0 right-0',
    'bottom-center' => 'bottom-0 left-0 right-0',
    'bottom-left'   => 'bottom-0 left-0',
    default         => 'top-0 left-0 right-0',
};

$enterStart = $isTop ? '-translate-y-4 opacity-0' : 'translate-y-4 opacity-0';
$leaveEnd   = str_ends_with($position, 'left') ? '-translate-x-24 opacity-0' : 'translate-x-24 opacity-0';
@endphp

<div
    x-data="{
        notifications: [],
        displayDuration: 5000,

        addNotification({ variant = 'info', title = null, message = null }) {
            const id = Date.now()
            if (this.notifications.length >= 20) {
                this.notifications.splice(0, this.notifications.length - 19)
            }
            this.notifications.push({ id, variant, title, message })
        },

        removeNotification(id) {
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id)
            }, 400)
        },
    }"
    x-on:notify.window="addNotification({
        variant: $event.detail.variant,
        title: $event.detail.title,
        message: $event.detail.message,
    })"
>
    <div
        x-on:mouseenter="$dispatch('pause-auto-dismiss')"
        x-on:mouseleave="$dispatch('resume-auto-dismiss')"
        class="pointer-events-none fixed z-[9999] flex flex-col p-6 {{ $positionClass }} {{ $isCenter ? 'items-center' : ($position === 'top-right' || $position === 'bottom-right' ? 'items-end' : 'items-start') }}"
    >
        <template x-for="notification in notifications" x-bind:key="notification.id">
            <div class="w-full max-w-sm">

                {{-- Success --}}
                <template x-if="notification.variant === 'success'">
                    <div
                        x-data="{ isVisible: false, timeout: null }"
                        x-cloak x-show="isVisible"
                        x-init="$nextTick(() => { isVisible = true }), (timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration))"
                        x-on:pause-auto-dismiss.window="clearTimeout(timeout)"
                        x-on:resume-auto-dismiss.window="timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration)"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="{{ $enterStart }}"
                        x-transition:enter-end="translate-y-0 translate-x-0 opacity-100"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="{{ $leaveEnd }}"
                        class="pointer-events-auto mb-2 rounded-xl border border-green-200 bg-white shadow-lg dark:border-green-800 dark:bg-zinc-900"
                        role="alert"
                    >
                        <div class="flex items-center gap-3 rounded-xl bg-green-50 p-4 dark:bg-green-950/30">
                            <div class="rounded-full bg-green-100 p-1 text-green-600 dark:bg-green-900 dark:text-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col gap-0.5 flex-1">
                                <h3 x-show="notification.title" class="text-sm font-semibold text-green-700 dark:text-green-400" x-text="notification.title"></h3>
                                <p x-show="notification.message" class="text-sm text-zinc-600 dark:text-zinc-300" x-text="notification.message"></p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Danger --}}
                <template x-if="notification.variant === 'danger'">
                    <div
                        x-data="{ isVisible: false, timeout: null }"
                        x-cloak x-show="isVisible"
                        x-init="$nextTick(() => { isVisible = true }), (timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration))"
                        x-on:pause-auto-dismiss.window="clearTimeout(timeout)"
                        x-on:resume-auto-dismiss.window="timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration)"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="{{ $enterStart }}"
                        x-transition:enter-end="translate-y-0 translate-x-0 opacity-100"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="{{ $leaveEnd }}"
                        class="pointer-events-auto mb-2 rounded-xl border border-red-200 bg-white shadow-lg dark:border-red-800 dark:bg-zinc-900"
                        role="alert"
                    >
                        <div class="flex items-center gap-3 rounded-xl bg-red-50 p-4 dark:bg-red-950/30">
                            <div class="rounded-full bg-red-100 p-1 text-red-600 dark:bg-red-900 dark:text-red-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col gap-0.5 flex-1">
                                <h3 x-show="notification.title" class="text-sm font-semibold text-red-700 dark:text-red-400" x-text="notification.title"></h3>
                                <p x-show="notification.message" class="text-sm text-zinc-600 dark:text-zinc-300" x-text="notification.message"></p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Warning --}}
                <template x-if="notification.variant === 'warning'">
                    <div
                        x-data="{ isVisible: false, timeout: null }"
                        x-cloak x-show="isVisible"
                        x-init="$nextTick(() => { isVisible = true }), (timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration))"
                        x-on:pause-auto-dismiss.window="clearTimeout(timeout)"
                        x-on:resume-auto-dismiss.window="timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration)"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="{{ $enterStart }}"
                        x-transition:enter-end="translate-y-0 translate-x-0 opacity-100"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="{{ $leaveEnd }}"
                        class="pointer-events-auto mb-2 rounded-xl border border-yellow-200 bg-white shadow-lg dark:border-yellow-800 dark:bg-zinc-900"
                        role="alert"
                    >
                        <div class="flex items-center gap-3 rounded-xl bg-yellow-50 p-4 dark:bg-yellow-950/30">
                            <div class="rounded-full bg-yellow-100 p-1 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col gap-0.5 flex-1">
                                <h3 x-show="notification.title" class="text-sm font-semibold text-yellow-700 dark:text-yellow-400" x-text="notification.title"></h3>
                                <p x-show="notification.message" class="text-sm text-zinc-600 dark:text-zinc-300" x-text="notification.message"></p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Info --}}
                <template x-if="notification.variant === 'info'">
                    <div
                        x-data="{ isVisible: false, timeout: null }"
                        x-cloak x-show="isVisible"
                        x-init="$nextTick(() => { isVisible = true }), (timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration))"
                        x-on:pause-auto-dismiss.window="clearTimeout(timeout)"
                        x-on:resume-auto-dismiss.window="timeout = setTimeout(() => { isVisible = false; removeNotification(notification.id) }, displayDuration)"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="{{ $enterStart }}"
                        x-transition:enter-end="translate-y-0 translate-x-0 opacity-100"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="{{ $leaveEnd }}"
                        class="pointer-events-auto mb-2 rounded-xl border border-blue-200 bg-white shadow-lg dark:border-blue-800 dark:bg-zinc-900"
                        role="alert"
                    >
                        <div class="flex items-center gap-3 rounded-xl bg-blue-50 p-4 dark:bg-blue-950/30">
                            <div class="rounded-full bg-blue-100 p-1 text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col gap-0.5 flex-1">
                                <h3 x-show="notification.title" class="text-sm font-semibold text-blue-700 dark:text-blue-400" x-text="notification.title"></h3>
                                <p x-show="notification.message" class="text-sm text-zinc-600 dark:text-zinc-300" x-text="notification.message"></p>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </template>
    </div>
</div>
