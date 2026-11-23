<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function index()
    {
        $merchant = Auth::user();

        $wallet = Wallet::firstOrCreate(['user_id' => $merchant->id], ['balance' => 0]);

        // One query for all the figures instead of three.
        $stats = Order::where('user_id', $merchant->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status IN ('pending','processing') THEN profit ELSE 0 END), 0) AS pending_profit,
                COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_count,
                COALESCE(SUM(CASE WHEN status IN ('cancelled','returned') THEN 1 ELSE 0 END), 0) AS lost_count
            ")
            ->first();

        $pendingProfit  = $stats->pending_profit;
        $deliveredCount = (int) $stats->delivered_count;
        $lostCount      = (int) $stats->lost_count;

        $orders = Order::where('user_id', $merchant->id)
            ->with('product:id,name')
            ->latest()->latest('id')
            ->paginate(20);

        $merchant->calculated_wallet = $wallet->balance;

        return view('frontend.merchant-wallet', compact(
            'merchant', 'pendingProfit', 'deliveredCount', 'lostCount', 'orders'
        ));
    }

    /**
     * Withdraw the whole balance to the merchant's wallet number. The wallet row is locked, so two
     * parallel requests cannot both pay out, and a concurrent profit credit waits.
     */
    public function withdraw(Request $request)
    {
        $merchant = Auth::user();

        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        $request->validate([
            'phone' => ['required', 'string', Phone::rule()],
        ], [
            'phone.regex' => 'رقم المحفظة غير صحيح. ' . Phone::message(),
        ]);

        try {
            $amount = DB::transaction(function () use ($merchant, $request) {
                $wallet = Wallet::where('user_id', $merchant->id)->lockForUpdate()->first();

                if (! $wallet || Money::toCents($wallet->balance) <= 0) {
                    return null;
                }

                $amount = $wallet->balance;

                WalletTransaction::create([
                    'wallet_id'   => $wallet->id,
                    'type'        => 'debit',
                    'amount'      => $amount,
                    'description' => 'سحب أرباح على رقم المحفظة: ' . $request->phone,
                ]);

                $wallet->decrement('balance', $amount);

                return $amount;
            }, 3);
        } catch (\Throwable $e) {
            Log::error('Withdraw failed', ['user' => $merchant->id, 'exception' => $e]);
            return back()->withErrors(['error' => 'حدث خطأ أثناء تنفيذ طلب السحب، يرجى المحاولة لاحقاً.']);
        }

        if ($amount === null) {
            return back()->withErrors(['error' => 'عذراً، لا يوجد رصيد متاح للسحب حالياً!']);
        }

        return back()->with('success', 'تم تقديم طلب سحب الأرباح بنجاح، وتصفير الخزنة في انتظار التحويل على رقم المحفظة الخاص بك!');
    }
}
