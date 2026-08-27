<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR: {{ $apar->kode_apar }}</title>
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

        .label {
            max-width: 320px;
            margin: 0 auto;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }

        .label h1 {
            font-size: 1rem;
            margin: 0 0 4px;
            font-weight: 700;
        }

        .label .sub {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .label .jenis {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 16px;
        }

        .label img {
            width: 220px;
            height: 220px;
            display: block;
            margin: 0 auto 16px;
        }

        .label .brand {
            font-size: 0.65rem;
            color: #9ca3af;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .label {
                border: none;
            }
        }
    </style>
</head>

<body>
    <p class="no-print" style="text-align: center; margin-bottom: 16px;">
        <button type="button" onclick="window.print()"
            style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">
            Cetak
        </button>
        <button type="button" onclick="window.close()"
            style="padding: 8px 16px; margin-left: 8px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
            Tutup
        </button>
    </p>

    <div class="label">
        <h1>{{ $apar->kode_apar }}</h1>
        <p class="sub">{{ $apar->lokasi?->nama_lokasi }}</p>
        <p class="jenis">{{ $apar->jenisKapasitasLabel() }}</p>

        <img src="{{ $qrImageUrl }}" alt="QR Code {{ $apar->kode_apar }}" width="220" height="220" />

        <p class="brand">Safety Patrol K3LH - Politeknik Negeri Banyuwangi</p>
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
