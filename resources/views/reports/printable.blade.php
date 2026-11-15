<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['title'] }} - {{ $institution->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 30px;
            background-color: #f8fafc;
        }
        .report-wrapper {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #059669;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .institution-title {
            font-size: 22px;
            font-weight: 800;
            color: #065f46;
            margin: 0;
        }
        .institution-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        .report-title-box {
            text-align: right;
        }
        .report-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .period-badge {
            font-size: 11px;
            color: #047857;
            font-weight: bold;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 12px;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 9px 12px;
            border-bottom: 2px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 14px;
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }
        .actions {
            max-width: 900px;
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            background-color: #059669;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-outline {
            background-color: white;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .report-wrapper {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-outline" onclick="window.close()">Close</button>
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="report-wrapper">
        <div class="header">
            <div>
                <h1 class="institution-title">{{ $institution->name }}</h1>
                <div class="institution-sub">
                    {{ $institution->contact['email'] ?? '' }}
                    @if(isset($institution->contact['phone'])) · {{ $institution->contact['phone'] }} @endif
                </div>
            </div>
            <div class="report-title-box">
                <h2 class="report-title">{{ $report['title'] }}</h2>
                @if(!empty($report['period']))
                    <div class="period-badge">Period: {{ $report['period'] }}</div>
                @endif
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Total Records: {{ $report['count'] }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach($report['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($report['headers']) }}" style="text-align: center; color: #94a3b8; padding: 24px;">
                            No records found matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <span>Generated on {{ now()->format('Y-m-d H:i') }}</span>
            <span>Beyan Madrasa Management SaaS</span>
        </div>
    </div>
</body>
</html>
