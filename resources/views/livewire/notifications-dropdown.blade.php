<?php

use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component {

    #[Computed]
    public function notifications()
    {
        return auth()->user()->unreadNotifications()->take(5)->get();
    }

    public function markAsRead($id)
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
}; ?>

<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" size="sm" icon="bell" class="relative text-zinc-500">
        @if($this->notifications->count() > 0)
            <span class="absolute top-1.5 right-2 size-2 bg-red-500 rounded-full border-2 border-white"></span>
        @endif
    </flux:button>

    <flux:menu class="w-80 max-h-96 overflow-y-auto">
        <div
            class="px-3 py-2 border-b border-zinc-100 flex justify-between items-center bg-zinc-50/50 sticky top-0 z-10 backdrop-blur-sm">
            <span class="text-sm font-medium text-zinc-600">Notifications</span>
            @if($this->notifications->count() > 0)
                <button wire:click="markAllAsRead"
                    class="text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors">
                    mark all as read
                </button>
            @endif
        </div>

        @if($this->notifications->isEmpty())
            <div class="px-4 py-8 text-center text-zinc-400">
                <flux:icon name="bell-slash" class="size-8 mx-auto mb-2 opacity-50" />
                <p class="text-sm">No new notifications</p>
            </div>
        @else
            @foreach($this->notifications as $notification)
                <flux:menu.item wire:click="markAsRead('{{ $notification->id }}')"
                    class="flex flex-col items-start gap-1 py-3 px-3">
                    <div class="flex items-start gap-3 w-full">
                        <div class="mt-1 p-1.5 bg-blue-50 text-blue-600 rounded-full">
                            <flux:icon name="information-circle" size="xs" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 truncate">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </p>
                            <p class="text-xs text-zinc-500 line-clamp-2 mt-0.5">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            <p class="text-[10px] text-zinc-400 mt-1.5">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </flux:menu.item>
            @endforeach
        @endif
    </flux:menu>
</flux:dropdown>