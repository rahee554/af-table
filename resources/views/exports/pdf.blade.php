{{--
    Plain, print-oriented table used by the PDF exporter.
    Kept deliberately styleless-but-legible: PDF renderers (dompdf, wkhtmltopdf)
    support only a narrow slice of CSS, so this avoids flexbox/grid entirely.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body  { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #111; }
        h1    { font-size: 14pt; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td{ border: 1px solid #bbb; padding: 4px 6px; text-align: left; vertical-align: top; }
        thead tr { background: #f0f0f0; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .meta { font-size: 8pt; color: #666; margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">{{ count($rows) }} rows &middot; generated {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
