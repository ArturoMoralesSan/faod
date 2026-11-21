<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\People;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fotografía de una persona (alumno, docente, paciente). Se guarda en el disco
 * privado y solo se sirve a usuarios con permiso.
 */
class PeoplePhotoController extends Controller
{
    /** Quien puede ver o cambiar la foto según el perfil que consulta. */
    private const VIEW = ['people.view', 'students.view', 'teachers.view', 'patients.view'];

    private const EDIT = ['people.edit', 'students.edit', 'teachers.edit', 'patients.edit'];

    public function show(Request $request, People $people): StreamedResponse
    {
        abort_unless($request->user()->canAny(self::VIEW), 403);
        abort_unless($people->photo_path && Storage::disk('local')->exists($people->photo_path), 404);

        return Storage::disk('local')->response($people->photo_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function update(Request $request, People $people): RedirectResponse
    {
        abort_unless($request->user()->canAny(self::EDIT), 403);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=100,min_height=100'],
        ], [
            'photo.dimensions' => 'La fotografía debe medir al menos 100 × 100 píxeles.',
        ]);

        $previous = $people->photo_path;
        $path = $request->file('photo')->store("photos/{$people->id}", 'local');

        $people->update(['photo_path' => $path]);

        if ($previous && $previous !== $path) {
            Storage::disk('local')->delete($previous);
        }

        return back()->with('success', 'Fotografía actualizada.');
    }

    public function destroy(Request $request, People $people): RedirectResponse
    {
        abort_unless($request->user()->canAny(self::EDIT), 403);

        if ($people->photo_path) {
            Storage::disk('local')->delete($people->photo_path);
            $people->update(['photo_path' => null]);
        }

        return back()->with('success', 'Fotografía eliminada.');
    }
}
