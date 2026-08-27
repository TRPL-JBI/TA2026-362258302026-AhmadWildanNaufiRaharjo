<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Lokasi ({{ count($items) }})</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 24px;
            color: #111827;
        }

        .toolbar {
            text-align: center;
            margin-bottom: 16px;
        }

        .toolbar button {
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .toolbar .btn-print {
            background: #2563eb;
            color: white;
            border: none;
        }

        .toolbar .btn-close {
            background: #fff;
            border: 1px solid #d1d5db;
            margin-left: 8px;
        }

        .sheet {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-width: 800px;
            margin: 0 auto;
        }

        .label {
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 16px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .label h1 {
            font-size: 0.95rem;
            margin: 0 0 4px;
            font-weight: 700;
        }

        .label .jenis {
            font-size: 0.75rem;
            color: #9ca3af;
            margin: 0 0 12px;
        }

        .label img {
            width: 180px;
            height: 180px;
            display: block;
            margin: 0 auto 12px;
        }

        .label .brand {
            font-size: 0.6rem;
            color: #9ca3af;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 0;
        }

        /* Setiap 4 item = 1 halaman (2x2) */
        .label:nth-child(4n) {
            page-break-after: always;
            break-after: page;
        }

        .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        @media print {
            body {
                padding: 8mm;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                max-width: none;
                gap: 8mm;
            }

            .label {
                border: 1px solid #d1d5db;
                border-radius: 8px;
            }

            .label img {
                width: 160px;
                height: 160px;
            }
        }

        @media screen and (max-width: 640px) {
            .sheet {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <p class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">Cetak</button>
        <button type="button" class="btn-close" onclick="window.close()">Tutup</button>
        <span style="display:block; margin-top:8px; font-size:13px; color:#6b7280;">
            {{ count($items) }} QR Code — layout 4 per halaman (2×2)
        </span>
    </p>

    <div class="sheet">
        @foreach ($items as $item)
            <div class="label">
                <h1>{{ $item['lokasi']->nama_lokasi }}</h1>
                <p class="jenis">{{ $item['lokasi']->jenis_lokasi }}</p>
                <img src="{{ $item['qrImageUrl'] }}" alt="QR Code {{ $item['lokasi']->nama_lokasi }}" width="180"
                    height="180" />
                <p class="brand">Safety Patrol K3LH - Politeknik Negeri Banyuwangi</p>
            </div>
        @endforeach
    </div>

    <script>
        window.addEventListener('load', () => {
            if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
                window.print();
            }
        });
    </script>
</body>

</html>
