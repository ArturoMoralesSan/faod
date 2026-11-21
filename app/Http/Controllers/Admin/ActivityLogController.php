<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consulta del historial de cambios (solo lectura).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $names = ActivityLog::subjectNames();
        // En la URL se usa el nombre corto de la clase (p. ej. "Patient").
        $types = collect($names)->mapWithKeys(fn ($label, $class) => [class_basename($class) => ['class' => $class, 'label' => $label]]);

        $subjectClass = $types->get($request->subject_type)['class'] ?? null;

        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($subjectClass, fn ($q, $class) => $q->where('subject_type', $class))
            ->when($request->subject_id, fn ($q, $id) => $q->where('subject_id', $id))
            ->when(in_array($request->event, ActivityLog::EVENTS, true), fn ($q) => $q->where('event', $request->event))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->search, fn ($q, $search) => $q->where('subject_label', 'like', "%{$search}%"))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'subject_type' => class_basename($log->subject_type),
                'subject_name' => $names[$log->subject_type] ?? class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'subject_label' => $log->subject_label,
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $log->user?->only('id', 'name'),
            ]);

        return Inertia::render('admin/activity-log/Index', [
            'logs' => $logs,
            'filters' => $request->only('search', 'subject_type', 'subject_id', 'event', 'user_id', 'date_from', 'date_to'),
            'subjectTypes' => $types->map(fn ($type, $key) => ['value' => $key, 'label' => $type['label']])->sortBy('label')->values(),
            'events' => ActivityLog::EVENTS,
            'users' => User::whereIn('id', ActivityLog::query()->whereNotNull('user_id')->distinct()->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Bitácora de accesos: inicios y cierres de sesión, e intentos fallidos.
     */
    public function logins(Request $request): Response
    {
        $logs = LoginEvent::query()
            ->with('user:id,name,email')
            ->when(in_array($request->event, LoginEvent::EVENTS, true), fn ($q) => $q->where('event', $request->event))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('email', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/activity-log/Logins', [
            'logs' => $logs,
            'filters' => $request->only('search', 'event', 'user_id', 'date_from', 'date_to'),
            'events' => LoginEvent::EVENTS,
            'users' => User::whereIn('id', LoginEvent::query()->whereNotNull('user_id')->distinct()->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'failedToday' => LoginEvent::where('event', 'failed')->whereDate('created_at', today())->count(),
        ]);
    }
}
