<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 40px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .header {
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 12px;
        }

        .doc-title {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .doc-date {
            float: right;
            font-size: 12px;
            font-weight: normal;
            color: #666;
        }

        .filters {
            font-size: 11px;
            color: #555;
            margin-bottom: 10px;
            padding: 4px 8px;
            background-color: #f0f4ff;
            border-left: 3px solid #3b82f6;
        }

        .operator-block {
            margin-bottom: 18px;
        }

        .operator-block + .operator-block {
            page-break-before: auto;
            border-top: 1px solid #e5e5e5;
            padding-top: 12px;
            margin-top: 8px;
        }

        .operator-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #1e3a5f;
        }

        .operator-meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 10px 0;
        }

        .operator-meta td {
            border: none;
            padding: 2px 8px 2px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .operator-meta td.label {
            font-weight: bold;
            width: 70px;
            color: #444;
            white-space: nowrap;
        }

        .section-label {
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #555;
        }

        table.mafundi-table {
            border-collapse: collapse;
            width: 100%;
            margin: 0;
        }

        .mafundi-table th,
        .mafundi-table td {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: left;
            font-size: 10px;
            vertical-align: top;
        }

        .mafundi-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .mafundi-table thead {
            display: table-header-group;
        }

        .mafundi-table tr {
            page-break-inside: avoid;
        }

        .empty {
            font-size: 11px;
            color: #888;
            font-style: italic;
            margin: 4px 0 0 0;
        }

        .footer {
            text-align: center;
            margin-top: 16px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ assetToBase64('images/logo.png') }}" height="28" alt="Logo">
    </div>

    @if (!empty($filters))
        <div class="filters">Filters: {{ $filters }}</div>
    @endif

    <div class="doc-title">
        {{ $title }}
        <span class="doc-date">{{ $date }}</span>
    </div>

    @forelse ($operators as $op)
        <div class="operator-block">
            <div class="operator-title">{{ $op['name'] }}</div>

            <table class="operator-meta">
                <tr>
                    <td class="label">Phone</td>
                    <td>{{ $op['phone'] ?: 'N/A' }}</td>
                    <td class="label">Status</td>
                    <td>{{ $op['status'] }}</td>
                    <td class="label">Mafundi</td>
                    <td>{{ count($op['fundis']) }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td>{{ $op['email'] ?: 'N/A' }}</td>
                    <td class="label">Role</td>
                    <td>{{ $op['role'] }}</td>
                    <td class="label">Districts</td>
                    <td>{{ $op['districts'] ?: 'N/A' }}</td>
                </tr>
            </table>

            <div class="section-label">Mafundi Registered</div>

            @if (count($op['fundis']) > 0)
                <table class="mafundi-table">
                    <thead>
                        <tr>
                            <th style="width: 24px;">#</th>
                            <th>Name</th>
                            <th style="width: 52px;">Gender</th>
                            <th style="width: 95px;">Primary Phone</th>
                            <th style="width: 95px;">Trans. Phone</th>
                            <th>Address</th>
                            <th style="width: 52px;">Visits</th>
                            <th style="width: 48px;">Putty Bags</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($op['fundis'] as $index => $fundi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $fundi['name'] }}</td>
                                <td>{{ $fundi['gender'] }}</td>
                                <td>{{ $fundi['primary_phone'] }}</td>
                                <td>{{ $fundi['transaction_phone'] }}</td>
                                <td>{{ $fundi['address'] }}</td>
                                <td>{{ $fundi['site_visits'] }}</td>
                                <td>{{ $fundi['bags_total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty">No mafundi registered by this operator.</p>
            @endif
        </div>
    @empty
        <p class="empty">No operators found.</p>
    @endforelse

    <div class="footer">
        <p>Generated by Magic Builders on {{ date('Y-m-d H:i:s') }}</p>
        <p>This document is confidential and intended for internal use only.</p>
    </div>
</body>

</html>
