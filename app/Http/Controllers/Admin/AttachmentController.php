<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Archivos adjuntos de cualquier registro (comprobantes, documentos, materiales).
 */
class AttachmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attachable_type' => ['required', Rule::in(array_keys(Attachment::ATTACHABLE_TYPES))],
            'attachable_id' => ['required', 'integer'],
            'document_type_id' => ['nullable', 'exists:catalog_items,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        $modelClass = Attachment::ATTACHABLE_TYPES[$data['attachable_type']];
        $attachable = $modelClass::findOrFail($data['attachable_id']);

        $file = $request->file('file');
        $path = $file->store("attachments/{$data['attachable_type']}/{$attachable->getKey()}", 'local');

        $attachable->attachments()->create([
            'document_type_id' => $data['document_type_id'] ?? null,
            'title' => $data['title'] ?? $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Archivo adjuntado correctamente.');
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)
            ->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }
}
