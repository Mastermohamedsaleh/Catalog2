<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Money;

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
        'emirate',
        'customer_type',
        'notes',
        'product_price',
        'selling_price',
        'delivery_fee',
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

    /** سعر المنتجات للزبون = سعر البيع × الكمية (من غير التوصيل). */
    public function getItemsTotalAttribute(): string
    {
        return Money::fromCents(Money::toCents($this->selling_price) * (int) $this->stock);
    }

    /** إجمالي اللي الزبون هيدفعه = المنتجات + رسوم التوصيل. */
    public function getCustomerTotalAttribute(): string
    {
        return Money::fromCents(Money::toCents($this->selling_price) * (int) $this->stock + Money::toCents($this->delivery_fee ?? 0));
    }

    public function getCustomerTypeLabelAttribute(): string
    {
        return match ($this->customer_type) {
            'new'       => 'New customer',
            'returning' => 'Returning customer',
            default     => '—',
        };
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
