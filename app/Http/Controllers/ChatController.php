<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Get all user's conversations with latest message
        $conversations = $user->conversations()->with(['users', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])->get();

        // Get all other users to start a new chat
        $allUsers = User::where('id', '!=', $user->id)->get();

        return view('chat.index', compact('conversations', 'allUsers'));
    }

    public function show(Conversation $conversation)
    {
        if (!$conversation->users->contains(Auth::id())) {
            abort(403);
        }

        $user = Auth::user();
        $conversations = $user->conversations()->with(['users', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])->get();

        $messages = $conversation->messages()->with('user')->get();
        $allUsers = User::where('id', '!=', $user->id)->get();

        return view('chat.show', compact('conversation', 'messages', 'conversations', 'allUsers'));
    }

    public function storePrivate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user1 = Auth::user();
        $user2 = User::findOrFail($request->user_id);

        // Check if private conversation already exists
        $conversation = $user1->conversations()
            ->where('is_group', false)
            ->whereHas('users', function ($query) use ($user2) {
                $query->where('users.id', $user2->id);
            })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'name' => null, // Private chat doesn't need a specific name
                'is_group' => false,
            ]);
            $conversation->users()->attach([$user1->id, $user2->id]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function store(Request $request, Conversation $conversation)
    {
        if (!$conversation->users->contains(Auth::id())) {
            abort(403);
        }

        $request->validate([
            'body' => 'required|string',
        ]);

        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        $message->load('user');

        // Broadcast the message to other users
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
