@php
    /** @var \App\Reports\Report $report */
    /** @var array<int, \App\Reports\Column> $columns */
    $tones = [
        'positive' => ['#187744', '#E2F3E8'],
        'negative' => ['#941B2B', '#F9ECEE'],
        'warning' => ['#8A5A00', '#FDF6E7'],
        'neutral' => ['#525960', '#F1F2F4'],
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title() }}</title>
    <style>
        @page { margin: 26px 28px 44px 28px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #292D32; margin: 0; }

        .header { width: 100%; border-bottom: 2px solid #941B2B; padding-bottom: 10px; margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .mark { width: 38px; height: 38px; background: #941B2B; border-radius: 8px; }
        .mark td { color: #fff; font-size: 13px; font-weight: bold; text-align: center; vertical-align: middle; height: 38px; }
        .university { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #941B2B; font-weight: bold; }
        .institution { font-size: 10px; font-weight: bold; margin-top: 1px; }
        .title { font-size: 17px; font-weight: bold; margin-top: 5px; color: #1F2226; }
        .description { color: #737982; margin-top: 2px; }
        .meta { text-align: right; color: #737982; font-size: 7.5px; line-height: 1.6; }
        .meta strong { color: #292D32; }

        .filters { margin-bottom: 10px; }
        .chip { display: inline-block; background: #F7F8FA; border: 1px solid #E3E5E8; border-radius: 10px; padding: 2px 8px; margin: 0 4px 4px 0; color: #525960; font-size: 7.5px; }

        .kpis { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 12px -6px; }
        .kpi { border: 1px solid #E3E5E8; border-radius: 8px; padding: 7px 9px; background: #FFFFFF; }
        .kpi-label { font-size: 7px; text-transform: uppercase; letter-spacing: .6px; color: #737982; }
        .kpi-value { font-size: 13px; font-weight: bold; margin-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data thead { display: table-header-group; }
        table.data th { background: #941B2B; color: #fff; font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; text-align: left; padding: 6px 5px; font-weight: bold; }
        table.data td { padding: 5px; border-bottom: 1px solid #ECEDEF; vertical-align: top; }
        table.data tbody tr:nth-child(even) td { background: #FAFAFB; }
        table.data tr { page-break-inside: avoid; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #737982; }
        .pill { display: inline-block; border-radius: 8px; padding: 1px 6px; font-size: 7.5px; font-weight: bold; white-space: nowrap; }
        .neg { color: #941B2B; }
        .excluded { color: #A3A8AF; text-decoration: line-through; }
        tfoot td { border-top: 1.5px solid #292D32; font-weight: bold; padding: 6px 5px; background: #F7F8FA; }

        .empty { text-align: center; color: #737982; padding: 30px 0; border: 1px dashed #E3E5E8; border-radius: 8px; }
        .note { margin-top: 8px; color: #8A5A00; background: #FDF6E7; border: 1px solid #F1DDB0; padding: 6px 8px; border-radius: 6px; }

        .footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 7px; color: #858B93; border-top: 1px solid #E3E5E8; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $institution }} · {{ $report->title() }} · Generado el {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}
    </div>

    <table class="header">
        <tr>
            <td style="width: 46px;"><table class="mark" cellspacing="0" cellpadding="0"><tr><td>FO</td></tr></table></td>
            <td>
                @if ($university)
                    <div class="university">{{ $university }}</div>
                @endif
                <div class="institution">{{ $institution }}</div>
                <div class="title">{{ $report->title() }}</div>
                <div class="description">{{ $report->description() }}</div>
            </td>
            <td class="meta" style="width: 170px;">
                <div>Sección <strong>{{ $report->section() }}</strong></div>
                <div>Generado <strong>{{ $generatedAt->format('d/m/Y H:i') }}</strong></div>
                <div>Por <strong>{{ $generatedBy }}</strong></div>
            </td>
        </tr>
    </table>

    @if ($filterLines)
        <div class="filters">
            @foreach ($filterLines as $line)
                <span class="chip">{{ $line }}</span>
            @endforeach
        </div>
    @endif

    @if ($summary)
        <table class="kpis">
            <tr>
                @foreach ($summary as $stat)
                    @php [$color] = $tones[$stat['tone'] ?? 'neutral'] ?? $tones['neutral']; @endphp
                    <td class="kpi" style="width: {{ round(100 / count($summary), 2) }}%; border-top: 3px solid {{ isset($stat['tone']) ? $color : '#C9CCD1' }};">
                        <div class="kpi-label">{{ $stat['label'] }}</div>
                        <div class="kpi-value" style="color: {{ isset($stat['tone']) ? $color : '#292D32' }};">{{ $stat['value'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if ($rows)
        <table class="data">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="{{ $column->numeric() ? 'num' : '' }}">{{ $column->label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            @php $text = $row['cells'][$column->key]; $raw = $row['raw'][$column->key] ?? null; @endphp
                            <td class="{{ $column->numeric() ? 'num' : '' }} {{ $column->muted ? 'muted' : '' }} {{ $raw === 'negative' ? 'neg' : '' }} {{ $column->total && $row['excluded'] ? 'excluded' : '' }}">
                                @if ($column->type === 'status' && $raw !== null && $raw !== '')
                                    @php [$fg, $bg] = $tones[\App\Support\Labels::tone((string) $raw)]; @endphp
                                    <span class="pill" style="color: {{ $fg }}; background: {{ $bg }};">{{ $text }}</span>
                                @else
                                    {{ $text }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if (array_filter($totals))
                <tfoot>
                    <tr>
                        @foreach ($columns as $index => $column)
                            <td class="{{ $column->numeric() ? 'num' : '' }}">{{ $totals[$column->key] ?? ($index === 0 ? 'Totales' : '') }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>

        @if ($truncated)
            <div class="note">
                El PDF muestra los primeros {{ number_format(count($rows)) }} de {{ number_format($truncated) }} registros (los totales corresponden a esos renglones).
                Descarga el CSV para obtener la lista completa o acota los filtros.
            </div>
        @endif
    @else
        <div class="empty">No hay registros con los filtros seleccionados.</div>
    @endif
</body>
</html>
