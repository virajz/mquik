<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 0;
        size: {{ $widthMm }}mm {{ $heightMm }}mm;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; }
    .page {
        padding: 1.5mm;
        text-align: center;
        page-break-after: always;
    }
    .page:last-child { page-break-after: auto; }
    .barcode-img {
        max-width: 100%;
        max-height: {{ $heightMm - 8 }}mm;
        display: block;
        margin: 0 auto;
    }
    .barcode-value {
        font-family: 'Courier', monospace;
        font-size: 8pt;
        letter-spacing: 1pt;
        margin-top: 1mm;
        text-align: center;
    }
</style>
</head>
<body>
    @for ($i = 0; $i < $copies; $i++)
        <div class="page">
            <img class="barcode-img" src="{{ $barcodeDataUri }}" alt="{{ $barcodeValue }}">
            <div class="barcode-value">{{ $barcodeValue }}</div>
        </div>
    @endfor
</body>
</html>
