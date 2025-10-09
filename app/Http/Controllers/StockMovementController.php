<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class StockMovementController extends Controller
{
    public function stockInIndex()
    {
        // Group stock movements by transaction batch
        $stockInGroups = StockMovement::stockIn()
            ->with(['product', 'supplier'])
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy(function ($item) {
                // Group by combination of order_number, invoice_number, supplier_id, and date
                return $item->order_number . '|' . $item->invoice_number . '|' . $item->supplier_id . '|' . $item->transaction_date->format('Y-m-d');
            })
            ->map(function ($group) {
                $first = $group->first();
                
                // Calculate subtotal (before tax) for each item
                $subtotal = $group->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                });
                
                // Calculate total amount (subtotal + tax if applicable)
                $totalAmount = $subtotal;
                if($first->include_tax) {
                    $totalAmount = $subtotal + ($subtotal * 0.11); // Add 11% PPN
                }
                
                return (object) [
                    'id' => $first->id,
                    'order_number' => $first->order_number,
                    'invoice_number' => $first->invoice_number,
                    'supplier' => $first->supplier,
                    'transaction_date' => $first->transaction_date,
                    'notes' => $first->notes,
                    'include_tax' => $first->include_tax,
                    'items_count' => $group->count(),
                    'total_quantity' => $group->sum('quantity'),
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $totalAmount,
                    'items' => $group
                ];
            })
            ->values();

        // Paginate the grouped results
        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $stockIns = new \Illuminate\Pagination\LengthAwarePaginator(
            $stockInGroups->forPage($currentPage, $perPage),
            $stockInGroups->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('stock.in.index', compact('stockIns'));
    }

    public function stockInCreate()
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $categories = \App\Models\ProductCategory::orderBy('name')->get();
        
        return view('stock.in.create', compact('products', 'suppliers', 'categories'));
    }

    public function stockInStore(Request $request)
    {
        try {
            $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.unit_price' => 'required|numeric|min:0',
                'supplier_id' => 'required|exists:suppliers,id',
                'order_number' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:255',
                'notes' => 'nullable',
                'transaction_date' => 'required|date',
                'include_tax' => 'nullable|in:0,1,true,false',
                'tax_amount' => 'nullable|numeric|min:0',
                'final_amount' => 'nullable|numeric|min:0'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()));
        }

        try {
            DB::transaction(function () use ($request) {
                $supplierId = $request->supplier_id;
                $transactionDate = $request->transaction_date;
                $notes = $request->notes;
                
                // Tax information
                $includeTax = in_array($request->input('include_tax'), ['1', 'true', true, 1]);
                $taxAmount = $request->input('tax_amount', 0);
                $finalAmount = $request->input('final_amount', 0);
                
                // Calculate subtotal
                $subtotalAmount = collect($request->products)->sum(function($product) {
                    return $product['quantity'] * $product['unit_price'];
                });
                
                // Get base reference number for this batch
                $baseRefNumber = 'IN-' . date('Ymd', strtotime($transactionDate)) . '-' . str_pad(StockMovement::stockIn()->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
                
                foreach ($request->products as $index => $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    $stockBefore = $product->current_stock;
                    $stockAfter = $stockBefore + $productData['quantity'];

                    // Create unique reference number for each product in batch
                    $referenceNumber = $baseRefNumber . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);

                    // Create stock movement
                    StockMovement::create([
                        'reference_number' => $referenceNumber,
                        'order_number' => $request->order_number,
                        'invoice_number' => $request->invoice_number,
                        'product_id' => $productData['product_id'],
                        'type' => 'in',
                        'quantity' => $productData['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_price' => $productData['unit_price'],
                        'include_tax' => $includeTax,
                        'tax_amount' => $taxAmount,
                        'subtotal_amount' => $subtotalAmount,
                        'final_amount' => $finalAmount,
                        'supplier_id' => $supplierId,
                        'notes' => $notes,
                        'transaction_date' => $transactionDate
                    ]);

                    // Update product stock
                    $product->update(['current_stock' => $stockAfter]);
                }
            });

            $productCount = count($request->products);
            return redirect()->route('stock.in.index')
                ->with('success', "Stok masuk berhasil dicatat untuk {$productCount} produk");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function stockOutIndex()
    {
        // Group stock movements by transaction batch
        $stockOutGroups = StockMovement::stockOut()
            ->with(['product', 'customer'])
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy(function ($item) {
                // Group by combination of order_number, invoice_number, customer_id, and date
                return $item->order_number . '|' . $item->invoice_number . '|' . $item->customer_id . '|' . $item->transaction_date->format('Y-m-d');
            })
            ->map(function ($group) {
                $first = $group->first();
                
                // Calculate subtotal (before tax) for each item
                $subtotal = $group->sum(function($item) {
                    $itemSubtotal = $item->quantity * $item->unit_price;
                    if($item->discount_percent > 0) {
                        $itemSubtotal = $itemSubtotal - ($itemSubtotal * ($item->discount_percent / 100));
                    }
                    return $itemSubtotal;
                });
                
                // Calculate total amount (subtotal + tax if applicable)
                $totalAmount = $subtotal;
                if($first->include_tax) {
                    $totalAmount = $subtotal + ($subtotal * 0.11); // Add 11% PPN
                }
                
                return (object) [
                    'id' => $first->id,
                    'order_number' => $first->order_number,
                    'invoice_number' => $first->invoice_number,
                    'customer' => $first->customer,
                    'transaction_date' => $first->transaction_date,
                    'notes' => $first->notes,
                    'include_tax' => $first->include_tax,
                    'items_count' => $group->count(),
                    'total_quantity' => $group->sum('quantity'),
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $totalAmount,
                    'items' => $group
                ];
            })
            ->values();

        // Paginate the grouped results
        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $stockOuts = new \Illuminate\Pagination\LengthAwarePaginator(
            $stockOutGroups->forPage($currentPage, $perPage),
            $stockOutGroups->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('stock.out.index', compact('stockOuts'));
    }

    public function stockOutCreate()
    {
        $products = Product::where('current_stock', '>', 0)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        
        return view('stock.out.create', compact('products', 'customers', 'categories'));
    }

    public function stockOutStore(Request $request)
    {
        try {
            $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.unit_price' => 'required|numeric|min:0',
                'products.*.discount' => 'nullable|numeric|min:0|max:100',
                'customer_id' => 'required|exists:customers,id',
                'order_number' => 'nullable|string|max:255',
                'invoice_number' => 'nullable|string|max:255',
                'notes' => 'nullable',
                'payment_terms' => 'nullable|integer|min:1|max:365',
                'transaction_date' => 'required|date',
                'include_tax' => 'nullable|in:0,1,true,false',
                'tax_amount' => 'nullable|numeric|min:0',
                'final_amount' => 'nullable|numeric|min:0',
                'draft_id' => 'nullable|exists:stock_out_drafts,id'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()));
        }

        try {
            DB::transaction(function () use ($request) {
                $customerId = $request->customer_id;
                $transactionDate = $request->transaction_date;
                $notes = $request->notes;
                $draftId = $request->input('draft_id'); // Get draft_id if exists
                
                // Tax information
                $includeTax = in_array($request->input('include_tax'), ['1', 'true', true, 1]);
                $taxAmount = $request->input('tax_amount', 0);
                $finalAmount = $request->input('final_amount', 0);
                
                // Calculate subtotal
                $subtotalAmount = collect($request->products)->sum(function($product) {
                    return $product['quantity'] * $product['unit_price'];
                });
                
                // Get base reference number for this batch
                $baseRefNumber = 'OUT-' . date('Ymd', strtotime($transactionDate)) . '-' . str_pad(StockMovement::stockOut()->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            
            foreach ($request->products as $index => $productData) {
                $product = Product::findOrFail($productData['product_id']);
                
                if ($product->current_stock < $productData['quantity']) {
                    throw new \Exception("Stok tidak mencukupi untuk produk {$product->name}. Stok tersedia: {$product->current_stock}, diminta: {$productData['quantity']}");
                }
                
                $stockBefore = $product->current_stock;
                $stockAfter = $stockBefore - $productData['quantity'];

                // Create unique reference number for each product in batch
                $referenceNumber = $baseRefNumber . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);

                // Calculate discount
                $discountPercent = $productData['discount'] ?? 0;
                $totalPrice = $productData['quantity'] * $productData['unit_price'];
                $discountAmount = $totalPrice * ($discountPercent / 100);

                // Create stock movement
                StockMovement::create([
                    'reference_number' => $referenceNumber,
                    'order_number' => $request->order_number,
                    'invoice_number' => $request->invoice_number,
                    'product_id' => $productData['product_id'],
                    'customer_id' => $customerId,
                    'type' => 'out',
                    'quantity' => $productData['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_price' => $productData['unit_price'],
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'include_tax' => $includeTax,
                    'tax_amount' => $taxAmount,
                    'subtotal_amount' => $subtotalAmount,
                    'final_amount' => $finalAmount,
                    'notes' => $notes,
                    'payment_terms' => (int) $request->input('payment_terms', 30),
                    'transaction_date' => $transactionDate
                ]);

                // Update product stock
                $product->update(['current_stock' => $stockAfter]);
            }
            
            // Delete draft if this transaction came from a draft
            if ($draftId) {
                $draft = \App\Models\StockOutDraft::find($draftId);
                if ($draft) {
                    $draft->delete();
                }
            }
            });

            $productCount = count($request->products);
            $draftId = $request->input('draft_id');
            
            if ($draftId) {
                return redirect()->route('stock.out.index')
                    ->with('success', "Draft berhasil diproses menjadi transaksi stok keluar untuk {$productCount} produk");
            } else {
                return redirect()->route('stock.out.index')
                    ->with('success', "Stok keluar berhasil dicatat untuk {$productCount} produk");
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function adminMovements()
    {
        $movements = StockMovement::with(['product', 'supplier', 'customer'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.stock.movements', compact('movements'));
    }

    public function exportStockOutExcel(Request $request)
    {
        try {
            // Check if this is from transaction list (has order_number, invoice_number, etc.) or from create form (has cart_data)
            $cartDataJson = $request->input('cart_data');
            
            if ($cartDataJson) {
                // From create form - use existing logic
                $cartData = json_decode($cartDataJson, true);
                $customerName = $request->input('customer_name', 'Customer');
                $customerId = $request->input('customer_id', '');
                $orderNumber = $request->input('order_number', '');
                $invoiceNumber = $request->input('invoice_number', '');
                $includeTax = $request->input('include_tax', '0') === '1';
                $paymentTerms = (int) $request->input('payment_terms', 30);
                
                if (empty($cartData) || !is_array($cartData)) {
                    return response()->json(['error' => 'Tidak ada data produk untuk diekspor'], 400);
                }
            } else {
                // From transaction list - get data from existing transactions
                $orderNumber = $request->input('order_number');
                $invoiceNumber = $request->input('invoice_number');
                $customerId = $request->input('customer_id');
                $transactionDate = $request->input('transaction_date');
                
                // Get stock movements for this transaction
                $stockMovements = StockMovement::stockOut()
                    ->with(['product', 'customer'])
                    ->where('order_number', $orderNumber)
                    ->where('invoice_number', $invoiceNumber)
                    ->where('customer_id', $customerId)
                    ->whereDate('transaction_date', $transactionDate)
                    ->get();
                
                if ($stockMovements->isEmpty()) {
                    return response()->json(['error' => 'Tidak ada data transaksi ditemukan'], 400);
                }
                
                // Convert stock movements to cart data format
                $cartData = $stockMovements->map(function ($movement) {
                    return [
                        'product_id' => $movement->product_id,
                        'name' => $movement->product->name,
                        'quantity' => $movement->quantity,
                        'unit_price' => $movement->unit_price,
                        'discount' => $movement->discount_percent ?? 0
                    ];
                })->toArray();
                
                $customerName = $stockMovements->first()->customer->name ?? 'Customer';
                $includeTax = $stockMovements->first()->include_tax;
                $paymentTerms = (int) ($stockMovements->first()->payment_terms ?? 30);
                $signerName = $stockMovements->first()->signer_name ?? 'KADARUSMAN';
            }
            
            // Get customer data if customer_id is provided
            $customer = null;
            if ($customerId) {
                $customer = \App\Models\Customer::find($customerId);
            }

            // Calculate totals (subtotal should be after discount)
            $subtotal = 0;
            foreach ($cartData as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $discountPercent = $item['discount'] ?? 0;
                $discountAmount = $totalPrice * ($discountPercent / 100);
                $nettoAmount = $totalPrice - $discountAmount;
                $subtotal += $nettoAmount;
            }
            $taxAmount = $includeTax ? $subtotal * 0.11 : 0;
            $finalAmount = $subtotal + $taxAmount;

            // Generate HTML content that matches the invoice format
            $terbilang = $this->terbilang($finalAmount);
            
            // Ensure signerName is set for both cases
            if (!isset($signerName)) {
                $signerName = $request->input('signer_name', 'KADARUSMAN');
            }
            
            // Ensure paymentTerms is defined for both cases and convert to integer
            if (!isset($paymentTerms)) {
                $paymentTerms = 30; // Default fallback
            }
            $paymentTerms = (int) $paymentTerms; // Convert to integer
            
            $html = view('exports.stock-out-invoice', [
                'cartData' => $cartData,
                'customerName' => $customerName,
                'customer' => $customer,
                'orderNumber' => $orderNumber,
                'invoiceNumber' => $invoiceNumber ?: ('118640625'),
                'includeTax' => $includeTax,
                'subtotal' => $subtotal,
                'taxAmount' => $taxAmount,
                'finalAmount' => $finalAmount,
                'currentDate' => now()->format('d F Y'),
                'terbilang' => $terbilang,
                'signerName' => $signerName,
                'paymentTerms' => $paymentTerms
            ])->render();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    private function terbilang($angka) {
        $angka = abs($angka);
        $baca = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $terbilang = "";
        
        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = $this->terbilang($angka - 10) . " belas";
        } else if ($angka < 100) {
            $terbilang = $this->terbilang($angka / 10) . " puluh" . $this->terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = " seratus" . $this->terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = $this->terbilang($angka / 100) . " ratus" . $this->terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = " seribu" . $this->terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = $this->terbilang($angka / 1000) . " ribu" . $this->terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = $this->terbilang($angka / 1000000) . " juta" . $this->terbilang($angka % 1000000);
        } else if ($angka < 1000000000000) {
            $terbilang = $this->terbilang($angka / 1000000000) . " milyar" . $this->terbilang(fmod($angka, 1000000000));
        } else if ($angka < 1000000000000000) {
            $terbilang = $this->terbilang($angka / 1000000000000) . " trilyun" . $this->terbilang(fmod($angka, 1000000000000));
        }
        
        return $terbilang;
    }

    public function exportStockOutToExcel(Request $request)
    {
        try {
            // Get the cart data from the request
            $cartDataJson = $request->input('cart_data', '[]');
            $cartData = json_decode($cartDataJson, true);
            $customerName = $request->input('customer_name', 'Customer');
            $customerId = $request->input('customer_id', '');
            $orderNumber = $request->input('order_number', '');
            $invoiceNumber = $request->input('invoice_number', '');
            $includeTax = $request->input('include_tax', '0') === '1';
            $signerName = $request->input('signer_name', 'KADARUSMAN');
            $paymentTerms = (int) $request->input('payment_terms', 30);
            
            // Get customer data if customer_id is provided
            $customer = null;
            if ($customerId) {
                $customer = \App\Models\Customer::find($customerId);
            }
            
            if (empty($cartData) || !is_array($cartData)) {
                return response()->json(['error' => 'Tidak ada data produk untuk diekspor'], 400);
            }

            // Calculate totals (subtotal should be after discount)
            $subtotal = 0;
            foreach ($cartData as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $discountPercent = $item['discount'] ?? 0;
                $discountAmount = $totalPrice * ($discountPercent / 100);
                $nettoAmount = $totalPrice - $discountAmount;
                $subtotal += $nettoAmount;
            }
            $taxAmount = $includeTax ? $subtotal * 0.11 : 0;
            $finalAmount = $subtotal + $taxAmount;

            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set page setup
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);

            // Add logo
            $logoPath = public_path('images/logo.png');
            if (file_exists($logoPath)) {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('PT. Mitrajaya Selaras Abadi Logo');
                $drawing->setPath($logoPath);
                $drawing->setHeight(60);
                $drawing->setCoordinates('A1');
                $drawing->setWorksheet($sheet);
            }

            // Company Header (offset to make room for logo)
            $sheet->setCellValue('B1', 'PT. MITRAJAYA SELARAS ABADI');
            $sheet->setCellValue('A2', 'LABORATORY & MEDICAL EQUIPMENT');
            $sheet->setCellValue('A3', 'Ruko Maison Avenue MA 10, Kota Wisata, Cibubur');
            $sheet->setCellValue('A4', 'Telp. / Fax : 82482412 , WA. 08119466470');

            // Customer info (right side) with date
            $sheet->setCellValue('F1', 'Bogor, ' . now()->format('d/m/Y'));
            $sheet->setCellValue('F2', 'Kepada Yth,');
            $sheet->setCellValue('F3', $customerName);
            
            // Customer address from database
            if ($customer && $customer->address) {
                $addressLines = explode("\n", $customer->address);
                $row = 4;
                foreach ($addressLines as $line) {
                    if (trim($line) && $row <= 7) { // Limit to 4 lines (F4-F7)
                        $sheet->setCellValue('F' . $row, trim($line));
                        $row++;
                    }
                }
            } else {
                $sheet->setCellValue('F4', 'Alamat tidak tersedia');
            }

            // FAKTUR title
            $sheet->setCellValue('A7', 'FAKTUR');
            $sheet->mergeCells('A7:G7');

            // Invoice details
            $sheet->setCellValue('A9', 'No. Faktur : ' . ($invoiceNumber ?: '118610625'));
            
            $sheet->setCellValue('E9', 'Cara Pembayaran : Tempo ' . $paymentTerms . ' hari');
            $sheet->setCellValue('E10', 'Tgl Jatuh Tempo : ' . now()->addDays($paymentTerms)->format('d/m/Y'));

            // Table headers
            $headers = ['No', 'Nama Barang', 'P.Number', 'Harga Satuan', 'Banyaknya', 'Disc', 'Harga Netto', 'Jumlah'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '12', $header);
                $col++;
            }

            // Product data
            $row = 13;
            foreach ($cartData as $index => $item) {
                $product = Product::find($item['product_id']);
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $discountPercent = $item['discount'] ?? 0;
                $discountAmount = $totalPrice * ($discountPercent / 100);
                $nettoAmount = $totalPrice - $discountAmount;
                $unit = $product ? $product->unit : 'pcs';

                $nettoUnitPrice = $item['unit_price'] * (1 - $discountPercent/100);
                
                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $product ? $product->name : 'Unknown Product');
                $sheet->setCellValue('C' . $row, $product ? $product->code : '');
                $sheet->setCellValue('D' . $row, 'Rp ' . number_format($item['unit_price'], 2, ',', '.'));
                $sheet->setCellValue('E' . $row, $item['quantity'] . ' ' . $unit);
                $sheet->setCellValue('F' . $row, $discountPercent . '%');
                $sheet->setCellValue('G' . $row, 'Rp ' . number_format($nettoUnitPrice, 2, ',', '.'));
                $sheet->setCellValue('H' . $row, 'Rp ' . number_format($nettoAmount, 2, ',', '.'));
                $row++;
            }

            // Fill empty rows to make it look like the original
            for ($i = $row; $i <= 22; $i++) {
                for ($col = 'A'; $col <= 'H'; $col++) {
                    $sheet->setCellValue($col . $i, '');
                }
            }

            // PO Number section
            $sheet->setCellValue('A23', 'PO No: ' . ($orderNumber ?: '00535/PO/PMC/06/2025'));

            // Totals section
            $totalsRow = 25;
            $sheet->setCellValue('G' . $totalsRow, 'Sub Total');
            $sheet->setCellValue('H' . $totalsRow, 'Rp ' . number_format($subtotal, 2, ',', '.'));
            
            if ($includeTax) {
                $totalsRow++;
                $sheet->setCellValue('G' . $totalsRow, 'PPN 11%');
                $sheet->setCellValue('H' . $totalsRow, 'Rp ' . number_format($taxAmount, 2, ',', '.'));
            }
            
            $totalsRow++;
            $sheet->setCellValue('G' . $totalsRow, 'Total Faktur');
            $sheet->setCellValue('H' . $totalsRow, 'Rp ' . number_format($finalAmount, 2, ',', '.'));

            // Terbilang
            $terbilangExcel = $this->terbilang($finalAmount);
            $sheet->setCellValue('A' . ($totalsRow + 2), 'Terbilang : ' . ucwords(trim($terbilangExcel)) . ' rupiah');

            // Footer
            $footerRow = $totalsRow + 4;
            $sheet->setCellValue('A' . $footerRow, 'Penerima');
            $sheet->setCellValue('C' . $footerRow, 'For Payment, Please Transfer to :');
            $sheet->setCellValue('F' . $footerRow, 'Hormat Kami,');
            
            $sheet->setCellValue('C' . ($footerRow + 1), 'PT. MITRAJAYA SELARAS ABADI');
            $sheet->setCellValue('C' . ($footerRow + 2), 'BANK MANDIRI KCP CIBUBUR KOTA WISATA');
            $sheet->setCellValue('C' . ($footerRow + 3), 'NO. REK. 133 00 1559409 6');
            
            // Signature lines
            $sheet->setCellValue('A' . ($footerRow + 6), '(..............................)');
            $sheet->setCellValue('H' . ($footerRow + 6), '(' . $signerName . ')');

            // Apply styling
            $this->applyInvoiceStyles($sheet, $totalsRow, $footerRow);

            // Generate filename
            $filename = 'Faktur_' . ($invoiceNumber ?: 'INV') . '_' . date('Y-m-d') . '.xlsx';

            // Create writer and save
            $writer = new Xlsx($spreadsheet);
            
            // Output to browser
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    private function applyInvoiceStyles($sheet, $totalsRow, $footerRow)
    {
        // Company name styling (adjusted for logo)
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        
        // FAKTUR title styling
        $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Customer info alignment
        $sheet->getStyle('F1:F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Table headers styling
        $headerRange = 'A12:H12';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        // Table borders
        $tableRange = 'A12:H22';
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Align price columns to right
        $sheet->getStyle('D13:D22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G13:G22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H13:H22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // PO Number styling
        $sheet->getStyle('A23')->getFont()->setBold(true);
        
        // Totals section styling
        $totalsRange = 'G25:H' . $totalsRow;
        $sheet->getStyle($totalsRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('G25:G' . $totalsRow)->getFont()->setBold(true);
        $sheet->getStyle('G25:G' . $totalsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H25:H' . $totalsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G25:G' . $totalsRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        
        // Text alignment for specific columns
        $sheet->getStyle('A13:A22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C13:C22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E13:E22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F13:F22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Draft Management Methods
    public function draftIndex()
    {
        $drafts = \App\Models\StockOutDraft::with('customer')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
        
        return view('stock.out.drafts.index', compact('drafts'));
    }

    public function saveDraft(Request $request)
    {
        try {
            $cartDataJson = $request->input('cart_data', '[]');
            $cartData = json_decode($cartDataJson, true);
            
            if (empty($cartData) || !is_array($cartData)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data produk untuk disimpan'], 400);
            }

            $draft = new \App\Models\StockOutDraft();
            $draft->draft_number = $draft->generateDraftNumber();
            $draft->customer_id = $request->input('customer_id') ?: null;
            $draft->customer_name = $request->input('customer_name', 'Customer');
            $draft->order_number = $request->input('order_number');
            $draft->invoice_number = $request->input('invoice_number');
            $draft->transaction_date = $request->input('transaction_date') ?: now()->toDateString();
            $draft->notes = $request->input('notes');
            $draft->signer_name = $request->input('signer_name', 'KADARUSMAN');
            $draft->payment_terms = (int) $request->input('payment_terms', 30);
            $draft->delivery_number = $request->input('delivery_number');
            $draft->include_tax = $request->input('include_tax', '0') === '1';
            $draft->cart_data = $cartData;
            $draft->total_amount = $draft->calculateTotalAmount();
            
            $draft->save();

            return response()->json([
                'success' => true, 
                'message' => 'Draft berhasil disimpan',
                'draft_id' => $draft->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editDraft($id)
    {
        $draft = \App\Models\StockOutDraft::with('customer')->findOrFail($id);
        $products = Product::with('category')->get();
        $customers = Customer::all();
        
        return view('stock.out.create', compact('draft', 'products', 'customers'));
    }

    public function updateDraft(Request $request, $id)
    {
        try {
            $draft = \App\Models\StockOutDraft::findOrFail($id);
            
            $cartDataJson = $request->input('cart_data', '[]');
            $cartData = json_decode($cartDataJson, true);
            
            if (empty($cartData) || !is_array($cartData)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data produk untuk disimpan'], 400);
            }

            $draft->customer_id = $request->input('customer_id') ?: null;
            $draft->customer_name = $request->input('customer_name', 'Customer');
            $draft->order_number = $request->input('order_number');
            $draft->invoice_number = $request->input('invoice_number');
            $draft->transaction_date = $request->input('transaction_date') ?: now()->toDateString();
            $draft->notes = $request->input('notes');
            $draft->signer_name = $request->input('signer_name', 'KADARUSMAN');
            $draft->payment_terms = (int) $request->input('payment_terms', 30);
            $draft->delivery_number = $request->input('delivery_number');
            $draft->include_tax = $request->input('include_tax', '0') === '1';
            $draft->cart_data = $cartData;
            $draft->total_amount = $draft->calculateTotalAmount();
            
            $draft->save();

            return response()->json([
                'success' => true, 
                'message' => 'Draft berhasil diupdate',
                'draft_id' => $draft->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal mengupdate draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteDraft($id)
    {
        try {
            $draft = \App\Models\StockOutDraft::findOrFail($id);
            $draft->delete();
            
            return redirect()->route('stock.out.draft.index')
                           ->with('success', 'Draft berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('stock.out.draft.index')
                           ->with('error', 'Gagal menghapus draft: ' . $e->getMessage());
        }
    }

    public function processDraft($id)
    {
        try {
            $draft = \App\Models\StockOutDraft::findOrFail($id);
            
            DB::transaction(function () use ($draft) {
                $customerId = $draft->customer_id;
                $transactionDate = $draft->transaction_date;
                $notes = $draft->notes;
                
                // Generate base reference number for this batch
                $baseRefNumber = 'OUT-' . date('Ymd', strtotime($transactionDate)) . '-' . str_pad(StockMovement::stockOut()->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
                
                foreach ($draft->cart_data as $index => $productData) {
                    $product = Product::find($productData['product_id']);
                    
                    if (!$product) {
                        throw new \Exception('Produk tidak ditemukan: ' . $productData['product_id']);
                    }
                    
                    $stockBefore = $product->current_stock;
                    
                    if ($stockBefore < $productData['quantity']) {
                        throw new \Exception('Stok tidak mencukupi untuk produk: ' . $product->name);
                    }
                    
                    $stockAfter = $stockBefore - $productData['quantity'];
                    $product->update(['current_stock' => $stockAfter]);
                    
                    // Create unique reference number for each product in batch
                    $referenceNumber = $baseRefNumber . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                    
                    // Calculate discount
                    $discountPercent = $productData['discount'] ?? 0;
                    $totalPrice = $productData['quantity'] * $productData['unit_price'];
                    $discountAmount = $totalPrice * ($discountPercent / 100);
                    $nettoAmount = $totalPrice - $discountAmount;
                    
                    // Calculate tax for this item
                    $taxAmount = $draft->include_tax ? $nettoAmount * 0.11 : 0;
                    $finalAmount = $nettoAmount + $taxAmount;
                    
                    StockMovement::create([
                        'reference_number' => $referenceNumber,
                        'order_number' => $draft->order_number,
                        'invoice_number' => $draft->invoice_number,
                        'product_id' => $productData['product_id'],
                        'customer_id' => $customerId,
                        'type' => 'out',
                        'quantity' => $productData['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_price' => $productData['unit_price'],
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'include_tax' => $draft->include_tax,
                        'tax_amount' => $taxAmount,
                        'subtotal_amount' => $nettoAmount,
                        'final_amount' => $finalAmount,
                        'transaction_date' => $transactionDate,
                        'notes' => $notes
                    ]);
                }
                
                // Delete draft after processing
                $draft->delete();
            });

            return redirect()->route('stock.out.index')
                           ->with('success', 'Draft berhasil diproses menjadi transaksi stok keluar');
                           
        } catch (\Exception $e) {
            return redirect()->route('stock.out.draft.index')
                           ->with('error', 'Gagal memproses draft: ' . $e->getMessage());
        }
    }

    public function exportDeliveryNote(Request $request)
    {
        try {
            // Get the cart data from the request
            $cartDataJson = $request->input('cart_data');
            
            if ($cartDataJson) {
                // From create form - use existing logic
                $cartData = json_decode($cartDataJson, true);
                $customerName = $request->input('customer_name', 'Customer');
                $customerId = $request->input('customer_id', '');
                $deliveryNumber = $request->input('delivery_number', '');
                $signerName = $request->input('signer_name', 'Yayuk P. Wardani');
                
                if (empty($cartData) || !is_array($cartData)) {
                    return response()->json(['error' => 'Tidak ada data produk untuk diekspor'], 400);
                }
            } else {
                // From transaction list - get data from existing transactions
                $deliveryNumber = $request->input('delivery_number');
                $customerId = $request->input('customer_id');
                $transactionDate = $request->input('transaction_date');
                
                if (!$deliveryNumber || !$customerId || !$transactionDate) {
                    return response()->json(['error' => 'Parameter tidak lengkap'], 400);
                }
                
                // Get stock movements for this transaction
                $stockMovements = \App\Models\StockMovement::where('type', 'out')
                    ->where('customer_id', $customerId)
                    ->whereDate('transaction_date', $transactionDate)
                    ->with(['product', 'customer'])
                    ->get();
                
                if ($stockMovements->isEmpty()) {
                    return response()->json(['error' => 'Data transaksi tidak ditemukan'], 404);
                }
                
                // Convert stock movements to cart data format
                $cartData = $stockMovements->map(function($movement) {
                    return [
                        'product_id' => $movement->product_id,
                        'name' => $movement->product->name,
                        'quantity' => $movement->quantity,
                        'unit_price' => $movement->unit_price
                    ];
                })->toArray();
                
                $customerName = $stockMovements->first()->customer->name ?? 'Customer';
                $signerName = $stockMovements->first()->signer_name ?? 'Yayuk P. Wardani';
            }
            
            // Get customer data if customer_id is provided
            $customer = null;
            if ($customerId) {
                $customer = \App\Models\Customer::find($customerId);
            }
            
            // Generate delivery number if not provided
            if (!$deliveryNumber) {
                $deliveryNumber = 'SJ/' . now()->format('md') . '/IX/MSA/' . now()->format('y');
            }
            
            $html = view('exports.delivery-note', [
                'cartData' => $cartData,
                'customerName' => $customerName,
                'customer' => $customer,
                'deliveryNumber' => $deliveryNumber,
                'signerName' => $signerName,
                'currentDate' => now()->format('d F Y')
            ])->render();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
