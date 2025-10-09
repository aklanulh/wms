<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'order_number',
        'invoice_number',
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'include_tax',
        'tax_amount',
        'subtotal_amount',
        'final_amount',
        'supplier_id',
        'customer_id',
        'notes',
        'payment_terms',
        'transaction_date'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'include_tax' => 'boolean',
        'transaction_date' => 'datetime'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeOpname($query)
    {
        return $query->where('type', 'opname');
    }
}
