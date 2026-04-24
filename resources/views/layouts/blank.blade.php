<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label</title>
    <link rel="stylesheet" href="{{ asset('assets/css/tabler.min.css') }}">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: white; }
        }
        body {
            background: #f4f6fa;
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    {{ $slot }}
</body>
</html>
