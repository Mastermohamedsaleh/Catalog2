<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    /** Statuses after which an order can never change again. */
    public const TERMINAL_STATUSES = ['delivered', 'cancelled', 'returned'];

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'product_price',
        'selling_price',
        'cancellation_reason',
        'profit',
        'stock', // ordered quantity
        'status',
        'is_paid_to_wallet',
    ];

    protected function casts(): array
    {
        return [
            'is_paid_to_wallet' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Alias kept because views use both names.
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    // NOTE: the previous `booted()` hook was removed on purpose. It overwrote
    // products.stock with the sum of variant stock on EVERY order save, which
    // zeroed stock for products without variants. Stock is now maintained only
    // by App\Services\StockService.
}
