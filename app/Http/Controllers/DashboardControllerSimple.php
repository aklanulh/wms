<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardControllerSimple extends Controller
{
    public function index(Request $request)
    {
        try {
            // Basic KPI Data only
            $totalProducts = Product::count();
            $lowStockProducts = Product::whereColumn('current_stock', '<=', 'minimum_stock')->count();
            $totalSuppliers = Supplier::count();
            $totalCustomers = Customer::count();
            
            // Simple stock movements
            $monthlyStockIn = StockMovement::where('type', 'in')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('quantity');
                
            $monthlyStockOut = StockMovement::where('type', 'out')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('quantity');
            
            // Simple chart data (last 7 days)
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                
                $stockIn = StockMovement::where('type', 'in')
                    ->whereDate('transaction_date', $date)
                    ->sum('quantity');
                    
                $stockOut = StockMovement::where('type', 'out')
                    ->whereDate('transaction_date', $date)
                    ->sum('quantity');
                
                $chartData[] = [
                    'period' => $date->format('d/m'),
                    'stock_in' => $stockIn,
                    'stock_out' => $stockOut,
                    'revenue' => 0
                ];
            }
            
            // Recent movements
            $recentMovements = StockMovement::with(['product'])
                ->orderBy('transaction_date', 'desc')
                ->take(10)
                ->get();
            
            return view('dashboard-simple', compact(
                'totalProducts',
                'lowStockProducts', 
                'totalSuppliers',
                'totalCustomers',
                'monthlyStockIn',
                'monthlyStockOut',
                'chartData',
                'recentMovements'
            ));
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return response()->view('errors.500', ['error' => $e->getMessage()], 500);
        }
    }
}
