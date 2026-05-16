<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
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

        return view('chat.index', compact('conversations'));
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

        return view('chat.show', compact('conversation', 'messages', 'conversations'));
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
