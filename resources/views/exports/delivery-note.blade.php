<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $deliveryNumber ?? 'SJ/1036/IX/MSA/25' }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #000;
        }
        
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .logo {
            width: 80px;
            height: 60px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .logo-fallback {
            display: none;
            font-size: 10px;
            font-weight: bold;
            color: #000;
            text-align: center;
            line-height: 1.2;
            padding: 5px;
            border: 2px solid #000;
            background-color: #f0f0f0;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .company-subtitle {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-address {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .header-right {
            text-align: right;
            font-size: 10px;
            width: 200px;
        }
        
        .delivery-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            letter-spacing: 2px;
        }
        
        .delivery-info {
            margin-bottom: 15px;
        }
        
        .delivery-number {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .delivery-text {
            margin-bottom: 15px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .products-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }
        
        .products-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
            vertical-align: middle;
        }
        
        .products-table .text-left {
            text-align: left;
            padding-left: 8px;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .footer-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .footer-left, .footer-center, .footer-right {
            width: 30%;
            text-align: center;
        }
        
        .signature-area {
            margin-top: 60px;
            border-bottom: 1px solid #000;
            min-height: 20px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2px;
        }
        
        .signature-name {
            font-weight: bold;
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="{{ url('images/logo.png') }}" alt="PT MSA Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div class="logo-fallback">PT MSA<br>LOGO</div>
        </div>
        <div class="company-info">
            <div class="company-name">PT. MITRAJAYA SELARAS ABADI</div>
            <div class="company-subtitle">LABORATORY & MEDICAL EQUIPMENT</div>
            <div class="company-address">
                Ruko Maison Avenue MA 19, Kota Wisata, Cibubur<br>
                Telp. / Fax : 82482412 , WA. 08119466470
            </div>
        </div>
        <div class="header-right">
            Bogor, {{ now()->format('d F Y') }}<br>
            Kepada Yth,<br>
            <strong>{{ $customerName ?? 'Klinik Pratama Zam Zam Medika' }}</strong><br>
            @if($customer && $customer->address)
                {!! nl2br(e($customer->address)) !!}
            @else
                Jl. Raya Dramaga RT.02/RW.02<br>
                Leuwikopo, Kec. Dramaga, Kota Bogor<br>
                Jawa Barat
            @endif
        </div>
    </div>
    
    <div class="delivery-title">SURAT JALAN</div>
    
    <div class="delivery-info">
        <div class="delivery-number">No. : {{ $deliveryNumber ?? 'SJ/1036/IX/MSA/25' }}</div>
        <div class="delivery-text">Telah kami terima dengan baik barang sebagai berikut :</div>
    </div>
    
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 200px;">Description</th>
                <th style="width: 80px;">ED</th>
                <th style="width: 80px;">Cat No.</th>
                <th style="width: 80px;">Lot No.</th>
                <th style="width: 50px;">QTY</th>
                <th style="width: 100px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cartData as $index => $item)
                @php
                    $product = \App\Models\Product::find($item['product_id']);
                    $unit = $product ? $product->unit : 'pcs';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $product ? $product->name : 'Unknown Product' }}</td>
                    <td class="text-center">{{ $product && $product->expired_date ? \Carbon\Carbon::parse($product->expired_date)->format('d/m/Y') : '' }}</td>
                    <td class="text-center">{{ $product ? $product->code : '' }}</td>
                    <td class="text-center">{{ $product ? $product->lot_number : '' }}</td>
                    <td class="text-center">{{ $item['quantity'] }} {{ $unit }}</td>
                    <td class="text-center"></td>
                </tr>
            @endforeach
            
            <!-- Empty rows to fill space -->
            @for($i = count($cartData); $i < 10; $i++)
                <tr>
                    <td class="text-center">&nbsp;</td>
                    <td class="text-left">&nbsp;</td>
                    <td class="text-center">&nbsp;</td>
                    <td class="text-center">&nbsp;</td>
                    <td class="text-center">&nbsp;</td>
                    <td class="text-center">&nbsp;</td>
                    <td class="text-center">&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>
    
    <div class="footer-section">
        <div class="footer-left">
            <strong>Penerima</strong>
            <div class="signature-area">
                <span class="signature-name">(.....................)</span>
            </div>
        </div>
        <div class="footer-center">
            <strong>Hormat Kami,</strong><br>
            <strong>Penanggung Jawab</strong><br>
            <strong>Teknis</strong>
            <div class="signature-area">
                <span class="signature-name">(Ria Kamelia)</span>
            </div>
        </div>
        <div class="footer-right">
            <strong>Hormat Kami,</strong><br>
            <strong>Pengirim</strong>
            <div class="signature-area">
                <span class="signature-name">({{ $signerName ?? 'Yayuk P. Wardani' }})</span>
            </div>
        </div>
    </div>
</body>
</html>
