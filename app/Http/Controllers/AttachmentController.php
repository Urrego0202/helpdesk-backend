<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('attachments', 'public');
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        $attachment = Attachment::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return response()->json($attachment, 201);
    }

    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $attachments = Attachment::where('ticket_id', $ticket->id)->get();

        return response()->json($attachments);
    }

    public function destroy($id)
    {
        $attachment = Attachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json([
            'message' => 'Archivo Eliminado'
        ]);
    }
}
