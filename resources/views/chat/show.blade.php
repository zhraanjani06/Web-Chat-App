<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat: ') }} {{ $conversation->name ?? 'Group Chat' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex h-[600px]">
                <!-- Sidebar -->
                <div class="w-1/3 border-r flex flex-col">
                    <div class="p-4 border-b font-bold text-lg flex justify-between items-center relative" x-data="{ open: false }">
                        <span>Conversations</span>
                        <button @click="open = !open" class="text-sm bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                            + New
                        </button>
                        
                        <!-- New Chat Dropdown -->
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
                                <button type="submit" class="w-full bg-blue-500 text-white text-xs px-2 py-2 rounded hover:bg-blue-600">Start Chat</button>
                            </form>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        @foreach($conversations as $conv)
                            <a href="{{ route('chat.show', $conv) }}" class="block p-4 border-b hover:bg-gray-50 {{ $conv->id === $conversation->id ? 'bg-blue-50' : '' }}">
                                <div class="font-semibold">{{ $conv->name ?? 'Group Chat' }}</div>
                                <div class="text-sm text-gray-500 truncate">
                                    {{ $conv->messages->first()->body ?? 'No messages yet' }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                
                <!-- Main Chat Area -->
                <div class="w-2/3 flex flex-col bg-gray-50" x-data="chatComponent({{ $conversation->id }}, {{ auth()->id() }})">
                    <div class="p-4 border-b bg-white font-bold flex justify-between items-center">
                        <span>{{ $conversation->name ?? 'Group Chat' }}</span>
                        <!-- Presence indicator -->
                        <span class="text-xs text-gray-500">
                            <span x-show="isOnline" class="w-2 h-2 rounded-full bg-green-500 inline-block mr-1"></span>
                            <span x-show="!isOnline" class="w-2 h-2 rounded-full bg-gray-300 inline-block mr-1"></span>
                            <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                        </span>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                        <template x-for="message in messages" :key="message.id">
                            <div class="flex" :class="message.user_id === currentUserId ? 'justify-end' : 'justify-start'">
                                <div :class="message.user_id === currentUserId ? 'bg-blue-500 text-white rounded-l-lg rounded-tr-lg' : 'bg-gray-200 text-gray-800 rounded-r-lg rounded-tl-lg'" class="p-3 max-w-xs shadow">
                                    <div class="text-xs opacity-75 mb-1" x-text="message.user ? message.user.name : 'Unknown'" x-show="message.user_id !== currentUserId"></div>
                                    <div x-text="message.body"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 bg-white border-t">
                        <form @submit.prevent="sendMessage" class="flex gap-2">
                            <input type="text" x-model="newMessage" class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Type your message..." required>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatComponent', (conversationId, currentUserId) => ({
                messages: @json($messages),
                newMessage: '',
                conversationId: conversationId,
                currentUserId: currentUserId,

                // Presence tracking variables
                onlineUsers: [],
                isOnline: false,

                init() {
                    this.scrollToBottom();
                    
                    // Listen for new messages using Laravel Echo Presence Channel
                    if (window.Echo) {
                        window.Echo.join(`presence-chat.${this.conversationId}`)
                            .here((users) => {
                                this.onlineUsers = users;
                                this.updatePresenceStatus();
                            })
                            .joining((user) => {
                                this.onlineUsers.push(user);
                                this.updatePresenceStatus();
                            })
                            .leaving((user) => {
                                this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
                                this.updatePresenceStatus();
                            })
                            .listen('MessageSent', (e) => {
                                this.messages.push(e.message);
                                this.scrollToBottom();
                            });
                    }
                },

                updatePresenceStatus() {
                    // Check if anyone else is online besides the current user
                    this.isOnline = this.onlineUsers.length > 1;
                },

                sendMessage() {
                    if (this.newMessage.trim() === '') return;

                    axios.post(`/chat/${this.conversationId}/messages`, {
                        body: this.newMessage
                    }).then(response => {
                        // Let's push to messages directly or wait for broadcast? 
                        // Wait, if we push it, and also listen to broadcast, it might duplicate.
                        // Actually, broadcast('chat.x') goes to others by default if we use `broadcast(new MessageSent())->toOthers()` in controller.
                        // But we didn't use `toOthers()` in controller! So we will receive our own broadcast.
                        // Thus, we shouldn't push it manually here, OR we should use `toOthers()` in controller.
                        // For simplicity, let's just clear the input and let Echo handle pushing the message to the screen.
                        // Wait, if Echo is slow, UX is bad. Let's push it manually, but only if we add a unique ID or check.
                        // Since this is 'sederhana', let's push manually and ignore our own broadcast, or just use `toOthers()`.
                        // I will update the controller later to use `toOthers()`. For now, I'll push it.
                        
                        // Check if message already exists
                        if(!this.messages.find(m => m.id === response.data.id)) {
                            this.messages.push(response.data);
                        }
                        this.newMessage = '';
                        this.scrollToBottom();
                    }).catch(error => {
                        console.error("Error sending message", error);
                    });
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const container = document.getElementById('messages-container');
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }, 50);
                }
            }));
        });
    </script>
</x-app-layout>
