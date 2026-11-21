@php
    /** @var \App\Support\WeeklyScheduleGrid $grid */
    use App\Support\WeeklyScheduleGrid;

    // Ancho en % de la columna de horas y de cada día; alto del cuerpo para que quepa en una hoja.
    $timeWidth = 7;
    $dayWidth = round((100 - $timeWidth) / max(count($grid->days), 1), 3);
    $bodyHeight = 440;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 24px 26px 40px 26px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #292D32; margin: 0; }

        .header { width: 100%; border-bottom: 2px solid #941B2B; padding-bottom: 8px; margin-bottom: 10px; }
        .header td { vertical-align: top; }
        .mark { width: 36px; height: 36px; background: #941B2B; border-radius: 8px; }
        .mark td { color: #fff; font-size: 13px; font-weight: bold; text-align: center; vertical-align: middle; height: 36px; }
        .university { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #941B2B; font-weight: bold; }
        .institution { font-size: 10px; font-weight: bold; margin-top: 1px; }
        .title { font-size: 16px; font-weight: bold; margin-top: 4px; color: #1F2226; }
        .subtitle { color: #737982; margin-top: 2px; }
        .meta { text-align: right; color: #737982; font-size: 7.5px; line-height: 1.6; }
        .meta strong { color: #292D32; }

        .week { position: relative; width: 100%; border: 1px solid #E3E5E8; }
        .week-head { position: absolute; top: 0; height: 15px; padding-top: 7px; background: #941B2B; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; text-align: center; }
        .hour-line { position: absolute; left: 0; right: 0; height: 0; border-top: 1px solid #ECEDEF; }
        .hour-label { position: absolute; left: 0; font-size: 7px; color: #737982; text-align: center; }
        .day-line { position: absolute; width: 0; border-left: 1px solid #E3E5E8; }
        .block { position: absolute; }
        .block-in { padding: 2px 4px; border-left: 3px solid; overflow: hidden; }
        .block-time { font-size: 6.5px; color: #525960; }
        .block-title { font-size: 7.5px; font-weight: bold; margin-top: 1px; }
        .block-line { font-size: 6.5px; color: #525960; margin-top: 1px; }

        h2 { font-size: 10px; color: #941B2B; margin: 14px 0 6px 0; }
        table.summary { width: 100%; border-collapse: collapse; }
        table.summary th { text-align: left; font-size: 7px; text-transform: uppercase; letter-spacing: .4px; color: #737982; border-bottom: 1.5px solid #292D32; padding: 4px; }
        table.summary td { padding: 4px; border-bottom: 1px solid #ECEDEF; vertical-align: top; }
        table.summary tfoot td { font-weight: bold; border-top: 1.5px solid #292D32; border-bottom: 0; }
        .swatch { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: 4px; }
        .num { text-align: right; white-space: nowrap; }

        .empty { text-align: center; color: #737982; padding: 30px 0; border: 1px dashed #E3E5E8; border-radius: 8px; }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 7px; color: #858B93; border-top: 1px solid #E3E5E8; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $institution }} · {{ $title }} · Generado el {{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}
    </div>

    <table class="header">
        <tr>
            <td style="width: 44px;"><table class="mark" cellspacing="0" cellpadding="0"><tr><td>FO</td></tr></table></td>
            <td>
                @if ($university)
                    <div class="university">{{ $university }}</div>
                @endif
                <div class="institution">{{ $institution }}</div>
                <div class="title">{{ $title }}</div>
                <div class="subtitle">{{ $subtitle }}</div>
            </td>
            <td class="meta" style="width: 170px;">
                <div>Periodo <strong>{{ $period ?? 'Sin periodo vigente' }}</strong></div>
                <div>Horas a la semana <strong>{{ rtrim(rtrim(number_format($totalHours, 1), '0'), '.') ?: '0' }}</strong></div>
                <div>Generado <strong>{{ $generatedAt->format('d/m/Y H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    @if ($grid->isEmpty())
        <div class="empty">No hay clases programadas en este periodo.</div>
    @else
        @php
            $head = 22;
            $scale = $bodyHeight / $grid->totalMinutes();
            $dayKeys = array_keys($grid->days);
        @endphp
        <div class="week" style="height: {{ $head + $bodyHeight }}px;">
            <div class="week-head" style="left: 0; width: {{ $timeWidth }}%;">Hora</div>
            @foreach ($dayKeys as $i => $day)
                <div class="week-head" style="left: {{ $timeWidth + $i * $dayWidth }}%; width: {{ $dayWidth }}%;">{{ $grid->days[$day] }}</div>
            @endforeach

            @foreach ($grid->hours() as $hour)
                @php $top = $head + ($hour - $grid->firstHour) * 60 * $scale; @endphp
                <div class="hour-line" style="top: {{ $top }}px;"></div>
                <div class="hour-label" style="top: {{ $top + 2 }}px; width: {{ $timeWidth }}%;">{{ sprintf('%02d:00', $hour) }}</div>
            @endforeach
            <div class="hour-line" style="top: {{ $head + $bodyHeight }}px;"></div>

            @foreach ($dayKeys as $i => $day)
                <div class="day-line" style="left: {{ $timeWidth + $i * $dayWidth }}%; top: {{ $head }}px; height: {{ $bodyHeight }}px;"></div>
            @endforeach

            @foreach ($dayKeys as $i => $day)
                @foreach ($grid->blocks[$day] ?? [] as $block)
                    @php
                        [$bg, $fg] = WeeklyScheduleGrid::color($block['color']);
                        $laneWidth = $dayWidth / $block['lanes'];
                        $height = WeeklyScheduleGrid::minutes($block['start'], $block['end']) * $scale - 2;
                    @endphp
                    <div class="block" style="
                        top: {{ $head + $grid->offset($block['start']) * $scale + 1 }}px;
                        left: {{ $timeWidth + $i * $dayWidth + $block['lane'] * $laneWidth + 0.15 }}%;
                        width: {{ $laneWidth - 0.3 }}%;">
                        <div class="block-in" style="height: {{ max($height - 4, 6) }}px; background: {{ $bg }}; border-left-color: {{ $fg }};">
                        <div class="block-time">{{ $block['start'] }}–{{ $block['end'] }}</div>
                        <div class="block-title" style="color: {{ $fg }};">{{ $block['title'] }}</div>
                        @if ($height > 34)
                            @foreach ($block['lines'] as $line)
                                <div class="block-line">{{ $line }}</div>
                            @endforeach
                        @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        <h2>Resumen</h2>
        <table class="summary">
            <thead>
                <tr>
                    <th style="width: 30%;">Materia</th>
                    <th style="width: 22%;">{{ $secondaryLabel }}</th>
                    <th>Horario</th>
                    <th class="num" style="width: 8%;">Horas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary as $row)
                    @php [$bg, $fg] = WeeklyScheduleGrid::color($row['color']); @endphp
                    <tr>
                        <td><span class="swatch" style="background: {{ $fg }};"></span>{{ $row['subject'] }}</td>
                        <td>{{ $row['secondary'] }}</td>
                        <td>{{ $row['when'] }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($row['hours'], 1), '0'), '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total a la semana</td>
                    <td class="num">{{ rtrim(rtrim(number_format($totalHours, 1), '0'), '.') ?: '0' }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
