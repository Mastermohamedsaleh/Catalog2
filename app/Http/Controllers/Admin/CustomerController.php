<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\Money;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // استخدام with('wallet') لجلب الرصيد مباشرة وبسرعة فائقة 🚀
        $query = User::with('wallet')
                     ->where('role', '!=', 'catalog_master_root')
                     ->where('is_super', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where('name', 'like', $like)->orWhere('email', 'like', $like);
            });
        }

        $customers = $query->latest()->latest('id')->paginate(10);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Admin accounts are never managed from the customers screen. Without this guard any
     * admin could reset the password of, or delete, a super admin by guessing the id.
     */
    private function ensureManageable(User $customer): void
    {
        abort_if($customer->isAdmin(), 403, 'لا يمكن تنفيذ هذا الإجراء على حساب إداري.');
    }

    /**
     * تصفير محفظة التاجر / العميل (سحب الأرباح)
     */
    public function zeroWallet(User $customer)
    {
        $this->ensureManageable($customer);

        try {
            $amount = DB::transaction(function () use ($customer) {
                $wallet = Wallet::where('user_id', $customer->id)->lockForUpdate()->first();

                if (! $wallet || Money::toCents($wallet->balance) <= 0) {
                    return null;
                }

                $amount = $wallet->balance;

                WalletTransaction::create([
                    'wallet_id'   => $wallet->id,
                    'type'        => 'debit',
                    'amount'      => $amount,
                    'description' => 'تصفير وسحب الأرباح بواسطة الإدارة من لوحة التحكم',
                ]);

                $wallet->decrement('balance', $amount);

                return $amount;
            }, 3);
        } catch (\Throwable $e) {
            Log::error('Zero wallet failed', ['customer' => $customer->id, 'exception' => $e]);
            return back()->with('error', 'حدث خطأ أثناء تصفير المحفظة، يرجى المحاولة لاحقاً.');
        }

        if ($amount === null) {
            return back()->with('error', 'عذراً، هذا التاجر ليس لديه رصيد متاح لتصفيره!');
        }

        return back()->with('success', "تم تصفير محفظة التاجر ({$customer->name}) بنجاح بمبلغ {$amount} " . config('shop.currency') . '!');
    }

    /**
     * إعادة تعيين كلمة السر للتاجر بواسطة الأدمن.
     * The flash message is rendered ESCAPED ({{ }}), so a merchant name can no longer inject HTML.
     */
    public function resetPassword(User $customer)
    {
        $this->ensureManageable($customer);

        $newPassword = Str::password(14, symbols: false);

        $customer->forceFill(['password' => Hash::make($newPassword)])->save();

        return back()->with('success', "تم إعادة تعيين كلمة السر بنجاح! كلمة السر الجديدة للتاجر ({$customer->name}) هي: {$newPassword}");
    }

    public function destroy(User $customer)
    {
        $this->ensureManageable($customer);

        if ($customer->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        // Orders and the wallet ledger are financial records: never delete them with the user.
        $hasHistory = Order::query()->where('user_id', $customer->id)->exists()
            || Wallet::where('user_id', $customer->id)->where('balance', '>', 0)->exists()
            || WalletTransaction::whereIn('wallet_id', Wallet::where('user_id', $customer->id)->select('id'))->exists();

        if ($hasHistory) {
            return back()->with('error', 'لا يمكن حذف هذا التاجر لوجود أوردرات أو حركات مالية مرتبطة به.');
        }

        Wallet::where('user_id', $customer->id)->delete();
        $customer->delete();

        return redirect()->route('admin.customers.index')
                         ->with('success', 'Customer deleted successfully!');
    }
}
