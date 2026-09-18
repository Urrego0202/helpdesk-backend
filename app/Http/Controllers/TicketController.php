<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role_id === 1) {
            $tickets = Ticket::with(['status', 'creator', 'assignee'])->get(); //admin ve todos los tickets
        } elseif ($user->role_id === 2) {
            $tickets = Ticket::with(['status', 'creator', 'assignee'])->where('assigned_to', $user->id)->get(); //agente ve tickets asignados a él
        } else {
            $tickets = Ticket::with(['status', 'creator', 'assignee'])->where('created_by', $user->id)->get(); //cliente ve los tickets que ha creado
        }
        return response()->json($tickets);
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:baja,media,alta',
        ]);

        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status_id' => 1,
            'created_by' => Auth::id(),
        ]);
        return response()->json($ticket, 201);
    }
    public function show($id)
    {
        $ticket = Ticket::with(['status', 'creator', 'assignee', 'comments.user', 'attachments', 'histories.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user   = Auth::user();

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority'    => 'sometimes|in:baja,media,alta',
            'status_id'   => 'sometimes|exists:ticket_status,id',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        $trackedFields = ['priority', 'status_id', 'assigned_to'];
        $oldValues = $ticket->only($trackedFields);

        // Solo el creador puede editar título y descripción
        if ($user->id === $ticket->created_by) {
            $ticket->fill($request->only(['title', 'description', 'priority']));
        }

        // Admin y agente pueden cambiar prioridad, estado y asignación
        if (in_array($user->role_id, [1, 2])) {
            $ticket->fill($request->only(['priority', 'status_id', 'assigned_to']));
        }

        $ticket->save();

        foreach ($trackedFields as $field) {
            if ($oldValues[$field] != $ticket->$field) {
                \App\Models\TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $user->id,
                    'field'     => $field,
                    'old_value' => $oldValues[$field],
                    'new_value' => $ticket->$field,
                ]);
            }
        }

        return response()->json($ticket);
    }

    public function agents()
    {
        $agents = \App\Models\User::where('role_id', 2)->get(['id', 'name']);
        return response()->json($agents);
    }
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'message' => 'Ticket Eliminado'
        ]);
    }

    public function stats()
    {
        $user = Auth::user();

        $query = Ticket::query();

        if ($user->role_id === 2) {
            $query->where('assigned_to', $user->id);
        } elseif ($user->role_id === 3) {
            $query->where('created_by', $user->id);
        }

        $baseQuery = clone $query;

        return response()->json([
            'total'       => $baseQuery->count(),
            'abiertos'    => (clone $query)->where('status_id', 1)->count(),
            'en_proceso'  => (clone $query)->where('status_id', 2)->count(),
            'cerrados'    => (clone $query)->where('status_id', 3)->count(),
            'baja'        => (clone $query)->where('priority', 'baja')->count(),
            'media'       => (clone $query)->where('priority', 'media')->count(),
            'alta'        => (clone $query)->where('priority', 'alta')->count(),
        ]);
    }
}
