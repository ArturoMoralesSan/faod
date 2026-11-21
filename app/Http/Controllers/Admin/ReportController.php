<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Reports\Column;
use App\Reports\Report;
use App\Reports\ReportRegistry;
use App\Support\PdfRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes con vista previa y descarga en PDF y CSV (se abre en Excel).
 */
class ReportController extends Controller
{
    /** Renglones de la vista previa por página. */
    private const PREVIEW_ROWS = 50;

    /** Máximo de renglones en el PDF; para más, el CSV. */
    public const PDF_LIMIT = 2000;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/reports/Index', [
            'reports' => ReportRegistry::availableFor($request->user())->map->toArray(),
        ]);
    }

    public function show(Request $request, string $report): Response
    {
        $report = $this->resolve($request, $report);
        $filters = $report->resolveFilters($request);
        $query = $report->query($filters);
        $columns = $report->columns();

        $page = (clone $query)->paginate(self::PREVIEW_ROWS)->withQueryString();

        return Inertia::render('admin/reports/Show', [
            'report' => $report->toArray(),
            'filters' => $report->filters(),
            'values' => $filters + ['all_dates' => $request->boolean('all_dates') ? '1' : ''],
            'columns' => array_map(fn (Column $column) => $column->toArray(), $columns),
            'rows' => $page->through(fn ($record) => $this->displayRow($report->row($record), $columns)),
            'totals' => $this->totals($report, $query, $columns),
            'summary' => $report->summary($query, $filters),
            'pdfLimit' => self::PDF_LIMIT,
        ]);
    }

    public function pdf(Request $request, string $report): HttpResponse
    {
        $report = $this->resolve($request, $report);
        $filters = $report->resolveFilters($request);
        $query = $report->query($filters);
        $columns = $report->columns();

        @set_time_limit(180);

        $total = (clone $query)->reorder()->count();
        $rows = [];
        $sums = array_fill_keys(array_map(fn ($c) => $c->key, array_filter($columns, fn ($c) => $c->total)), 0.0);

        foreach ($query->limit(self::PDF_LIMIT)->get() as $record) {
            $values = $report->row($record);

            foreach ($sums as $key => $sum) {
                $sums[$key] = $sum + (empty($values[Report::EXCLUDE_FROM_TOTALS]) ? (float) ($values[$key] ?? 0) : 0);
            }

            $rows[] = $this->displayRow($values, $columns);
        }

        $html = view('reports.pdf', [
            'report' => $report,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $sums ? collect($columns)->mapWithKeys(fn ($c) => [$c->key => array_key_exists($c->key, $sums) ? $c->format($sums[$c->key]) : null])->all() : [],
            'summary' => $report->summary($report->query($filters), $filters),
            'filterLines' => $report->describeFilters($filters),
            'truncated' => $total > self::PDF_LIMIT ? $total : null,
            'institution' => Setting::get('institution.name', 'Facultad de Odontología'),
            'university' => Setting::get('institution.university', ''),
            'generatedBy' => $request->user()->name,
            'generatedAt' => now(),
        ])->render();

        return PdfRenderer::render($html, $this->filename($report, 'pdf'), $report->landscape());
    }

    public function csv(Request $request, string $report): StreamedResponse
    {
        $report = $this->resolve($request, $report);
        $query = $report->query($report->resolveFilters($request));
        $columns = $report->columns();

        return response()->streamDownload(function () use ($report, $query, $columns) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel abra los acentos correctamente.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn (Column $column) => $column->label, $columns), escape: '');

            foreach ($query->lazy(500) as $record) {
                $values = $report->row($record);
                fputcsv($out, array_map(fn (Column $column) => $column->csv($values[$column->key] ?? null), $columns), escape: '');
            }

            fclose($out);
        }, $this->filename($report, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function resolve(Request $request, string $key): Report
    {
        $report = ReportRegistry::find($key);

        abort_unless($report, 404);
        abort_unless($request->user()->can($report->permission()), 403);

        return $report;
    }

    /**
     * Renglón para pantalla/PDF: texto formateado y, en columnas de estado, el valor original (para el color).
     *
     * @param  array<int, Column>  $columns
     */
    private function displayRow(array $values, array $columns): array
    {
        $row = ['cells' => [], 'raw' => [], 'excluded' => ! empty($values[Report::EXCLUDE_FROM_TOTALS])];

        foreach ($columns as $column) {
            $value = $values[$column->key] ?? null;
            $row['cells'][$column->key] = $column->format($value);

            if ($column->type === 'status') {
                $row['raw'][$column->key] = $value;
            }

            if ($column->numeric() && is_numeric($value) && $value < 0) {
                $row['raw'][$column->key] = 'negative';
            }
        }

        return $row;
    }

    /**
     * Totales de las columnas que se suman, sobre todos los registros filtrados.
     *
     * @param  array<int, Column>  $columns
     */
    private function totals(Report $report, Builder $query, array $columns): ?array
    {
        $totalColumns = array_filter($columns, fn (Column $column) => $column->total);

        if (! $totalColumns) {
            return null;
        }

        $sums = array_fill_keys(array_map(fn (Column $c) => $c->key, $totalColumns), 0.0);

        foreach ((clone $query)->lazy(500) as $record) {
            $values = $report->row($record);

            foreach ($sums as $key => $sum) {
                $sums[$key] = $sum + (empty($values[Report::EXCLUDE_FROM_TOTALS]) ? (float) ($values[$key] ?? 0) : 0);
            }
        }

        return collect($totalColumns)->mapWithKeys(fn (Column $c) => [$c->key => $c->format($sums[$c->key])])->all();
    }

    private function filename(Report $report, string $extension): string
    {
        return Str::slug($report->title()).'-'.now()->format('Y-m-d').'.'.$extension;
    }
}
