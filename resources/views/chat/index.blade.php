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
                    <div class="p-4 border-b font-bold text-lg flex justify-between items-center relative">
                        <span>Conversations</span>
                        <div class="flex gap-2">
                            <!-- Private Chat Dropdown -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="text-sm bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                                    + Private
                                </button>
                                
                                <div x-show="open" @click.away="open = false" class="absolute left-0 top-14 mt-2 w-56 bg-white border rounded shadow-lg z-10" style="display: none;">
                                    <div class="p-2 border-b font-semibold text-sm bg-gray-50">Start Private Chat</div>
                                    <form action="{{ route('chat.storePrivate') }}" method="POST" class="p-2">
                                        @csrf
                                        <select name="user_id" class="w-full text-sm border-gray-300 rounded mb-2" required>
                                            <option value="">Select User</option>
                                            @foreach($allUsers as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="w-full bg-blue-500 text-white text-xs px-2 py-2 rounded hover:bg-blue-600">Start</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Group Chat Dropdown -->
                            <div x-data="{ openGroup: false }" class="relative">
                                <button @click="openGroup = !openGroup" class="text-sm bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">
                                    + Group
                                </button>
                                
                                <div x-show="openGroup" @click.away="openGroup = false" class="absolute left-0 top-14 mt-2 w-56 bg-white border rounded shadow-lg z-10" style="display: none;">
                                    <div class="p-2 border-b font-semibold text-sm bg-gray-50">Create Group Chat</div>
                                    <form action="{{ route('chat.storeGroup') }}" method="POST" class="p-2">
                                        @csrf
                                        <input type="text" name="name" placeholder="Group Name" class="w-full text-sm border-gray-300 rounded mb-2" required>
                                        <div class="max-h-32 overflow-y-auto mb-2 border rounded p-1">
                                            @foreach($allUsers as $u)
                                                <label class="flex items-center text-sm p-1 hover:bg-gray-50">
                                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="mr-2 rounded border-gray-300">
                                                    {{ $u->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="w-full bg-green-500 text-white text-xs px-2 py-2 rounded hover:bg-green-600">Create</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
