<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parámetros generales del sistema.
 */
class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/settings/Index', [
            'settings' => Setting::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100', 'exists:settings,key'],
            'settings.*.value' => ['nullable'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['settings'] as $setting) {
                Setting::where('key', $setting['key'])->first()
                    ?->update(['value' => $setting['value']]);
            }
        });

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
