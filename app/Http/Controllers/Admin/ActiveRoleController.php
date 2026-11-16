<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Selector de rol: cambia el menú según el rol elegido.
 */
class ActiveRoleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $request->user()->switchRole(Role::findOrFail($data['role_id']));

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Rol cambiado correctamente.');
    }
}
