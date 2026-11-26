<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every status change goes through here.
 *
 * Rules enforced:
 *  - the order row is locked FIRST and every check runs on the locked copy;
 *  - delivered / cancelled / returned are terminal;
 *  - stock is released exactly once (on the move to cancelled / returned);
 *  - profit is credited exactly once, on the first move to delivered, with the
 *    wallet row locked and a unique (order_id, type) key as the last defence.
 */
class OrderStatusService
{
    /** Allowed moves. Anything not listed is rejected. */
    public const TRANSITIONS = [
        'pending'    => ['processing', 'delivered', 'cancelled'],
        'processing' => ['delivered', 'returned', 'cancelled'],
    ];

    public function __construct(private StockService $stock)
    {
    }

    /**
     * @throws DomainException when the move is not allowed
     */
    public function change(int $orderId, string $to, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $to, $reason) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();

            if ($order->isTerminal()) {
                throw new DomainException('هذا الطلب وصل لحالة نهائية ولا يمكن تغيير حالته لحماية السجل المالي!');
            }

            if ($to === $order->status) {
                return $order; // nothing to do
            }

            if (! in_array($to, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw new DomainException('لا يمكن نقل الطلب من هذه الحالة إلى الحالة المختارة.');
            }

            if (in_array($to, ['cancelled', 'returned'], true)) {
                $this->stock->release($order);
                $order->cancellation_reason = $reason;
                \App\Support\DeliveryFee::releaseFeeFrom($order); // رسوم التوصيل تنتقل لأوردر تاني لنفس العميل لو فيه
            } else {
                $order->cancellation_reason = null;
            }

            if ($to === 'delivered' && ! $order->is_paid_to_wallet) {
                $this->creditProfit($order);
                $order->is_paid_to_wallet = true;
            }

            $order->status = $to;
            $order->save();

            return $order;
        }, 3);
    }

    /** Merchant cancel: only while the order is still pending. */
    public function cancelByMerchant(int $orderId, int $userId, ?string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $userId, $reason) {
            $order = Order::whereKey($orderId)->where('user_id', $userId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'pending') {
                throw new DomainException('Sorry, this order has already been updated by our team and can no longer be cancelled.');
            }

            $this->stock->release($order);

            $order->status = 'cancelled';
            $order->cancellation_reason = $reason ?: 'Cancelled by the merchant.';
            \App\Support\DeliveryFee::releaseFeeFrom($order);
            $order->save();

            return $order;
        }, 3);
    }

    private function creditProfit(Order $order): void
    {
        // Create (if missing) then lock the wallet so credits and withdrawals serialize.
        $wallet = Wallet::firstOrCreate(['user_id' => $order->user_id], ['balance' => 0]);
        $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

        // Unique (order_id, type) makes a second credit for the same order fail loudly.
        WalletTransaction::create([
            'wallet_id'   => $wallet->id,
            'order_id'    => $order->id,
            'type'        => 'credit',
            'amount'      => $order->profit,
            'description' => "Profit from delivered order #{$order->id}",
        ]);

        $wallet->increment('balance', $order->profit);
    }
}
