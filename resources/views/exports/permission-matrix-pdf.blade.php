<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permission Matrix - Chabrin Lease System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #1f2937; }

        .header {
            background: #1a365d;
            color: white;
            padding: 20px 30px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { font-size: 10px; opacity: 0.8; }

        .meta {
            display: flex;
            justify-content: space-between;
            padding: 0 10px 15px;
            font-size: 9px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 15px;
        }

        .stats-grid {
            padding: 0 10px 15px;
            margin-bottom: 15px;
        }
        .stats-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-grid td {
            padding: 6px 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .stats-grid .role-name { font-weight: bold; background: #f3f4f6; }
        .stats-grid .count { font-size: 14px; font-weight: bold; color: #1a365d; }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 10px;
            font-size: 7px;
        }
        .matrix-table th {
            background: #1a365d;
            color: white;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 7px;
            white-space: nowrap;
        }
        .matrix-table th:first-child {
            text-align: left;
            min-width: 120px;
        }
        .matrix-table td {
            padding: 3px 4px;
            border: 1px solid #d1d5db;
            text-align: center;
        }
        .matrix-table td:first-child {
            text-align: left;
            font-weight: 600;
            background: #f9fafb;
            white-space: nowrap;
        }
        .matrix-table tr:nth-child(even) td { background: #f9fafb; }
        .matrix-table tr:nth-child(even) td:first-child { background: #f3f4f6; }

        .yes { color: #166534; font-weight: bold; background: #dcfce7 !important; }
        .no { color: #991b1b; background: #fee2e2 !important; }

        .footer {
            margin-top: 20px;
            padding: 10px 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rights Allocation Matrix</h1>
        <p>Chabrin Lease System - Permission & Role Overview</p>
    </div>

    <div class="meta">
        <span>Generated: {{ $generatedAt }}</span>
        <span>By: {{ $generatedBy }}</span>
        <span>Roles: {{ count($roles) }} | Permissions: {{ count($permissions) }}</span>
    </div>

    {{-- Role Summary --}}
    <div class="stats-grid">
        <table>
            <tr>
                @foreach ($roleStats as $stat)
                    <td>
                        <div class="role-name">{{ ucwords(str_replace('_', ' ', $stat['name'])) }}</div>
                        <div class="count">{{ $stat['users_count'] }}</div>
                        <div>users | {{ $stat['permissions_count'] }} perms</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- Permission Matrix --}}
    <table class="matrix-table">
        <thead>
            <tr>
                <th>Permission</th>
                @foreach ($roles as $role)
                    <th>{{ ucwords(str_replace('_', ' ', $role)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($permissions as $perm)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $perm)) }}</td>
                    @foreach ($roles as $role)
                        @if ($matrix[$role][$perm] ?? false)
                            <td class="yes">&#10003;</td>
                        @else
                            <td class="no">&#10007;</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Chabrin Lease System &mdash; Rights Allocation Matrix &mdash; Confidential
    </div>
</body>
</html>
