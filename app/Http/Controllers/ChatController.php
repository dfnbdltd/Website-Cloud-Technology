<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\User;
use Illuminate\Http\Request;

/**
 * Live chat used by the client-area widget (customer side) and the
 * admin console (agent side). Guests can chat too via a session key.
 */
class ChatController extends Controller
{
    public function poll(Request $request)
    {
        $session = $this->customerSessionId($request);

        $messages = ChatMessage::where('session_id', $session)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (ChatMessage $message) {
                return [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'message' => $message->message,
                    'time' => $message->created_at->format('d M, h:i A'),
                ];
            });

        // Customer has seen the admin's replies.
        ChatMessage::where('session_id', $session)
            ->where('sender', 'admin')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $session = $this->customerSessionId($request);

        ChatMessage::create([
            'session_id' => $session,
            'user_id' => session('customer.id'),
            'sender' => 'customer',
            'message' => $data['message'],
        ]);

        $latest = ChatMessage::where('session_id', $session)
            ->where('sender', 'customer')
            ->orderByDesc('id')
            ->value('id');

        return response()->json(['status' => 'ok', 'last_id' => $latest]);
    }

    public function adminPoll(Request $request)
    {
        $sessionId = (string) $request->query('session', '');
        if ($sessionId === '') {
            return response()->json(['messages' => []]);
        }

        $messages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (ChatMessage $message) {
                return [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'message' => $message->message,
                    'time' => $message->created_at->format('d M, h:i A'),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function adminSend(Request $request)
    {
        $data = $request->validate([
            'session' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ChatMessage::create([
            'session_id' => $data['session'],
            'sender' => 'admin',
            'message' => $data['message'],
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Stable conversation key: logged-in customers chat under their own
     * channel; guests get a per-session channel that upgrades automatically
     * after login because the same key derivation runs on every request.
     */
    private function customerSessionId(Request $request)
    {
        if ($user = $request->user()) {
            return $user->chatSessionId();
        }
        if (session('customer.id')) {
            return 'user-' . session('customer.id');
        }

        return 'guest-' . $request->session()->getId();
    }
}
