<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm; }
        html, body { margin: 0; padding: 0; }
        .page {
            page-break-after: always;
            text-align: center;
        }
        .page:last-child { page-break-after: auto; }
        img {
            max-width: 100%;
            max-height: 260mm;
        }
    </style>
</head>
<body>
    @foreach($pages as $src)
        <div class="page">
            <img src="{{ $src }}" alt="Vehicle condition">
        </div>
    @endforeach
</body>
</html>
