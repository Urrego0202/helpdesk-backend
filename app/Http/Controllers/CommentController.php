<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $request->validate([
            'body' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'body' => $request->body,
            'is_internal' => $request->is_internal ?? false,
        ]);

        $comment->load('user');

        return response()->json($comment, 201);
    }

    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $user = Auth::user();
        $comments = Comment::with('user')
            ->where('ticket_id', $ticket->id)
            ->when($user->role_id === 3, function ($query) {
                $query->where('is_internal', false);
            })
            ->get();

        return response()->json($comments);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'message' => 'Comentario Eliminado'
        ]);
    }
}
