<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex h-[600px]">
                <!-- Sidebar -->
                <div class="w-1/3 border-r flex flex-col">
                    <div class="p-4 border-b font-bold text-lg">Conversations</div>
                    <div class="flex-1 overflow-y-auto">
                        @forelse($conversations as $conv)
                            <a href="{{ route('chat.show', $conv) }}" class="block p-4 border-b hover:bg-gray-50">
                                <div class="font-semibold">{{ $conv->name ?? 'Group Chat' }}</div>
                                <div class="text-sm text-gray-500 truncate">
                                    {{ $conv->messages->first()->body ?? 'No messages yet' }}
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-gray-500">No conversations found.</div>
                        @endforelse
                    </div>
                </div>
                <!-- Main Chat Area -->
                <div class="w-2/3 flex items-center justify-center bg-gray-50 text-gray-400">
                    Select a conversation to start chatting
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
