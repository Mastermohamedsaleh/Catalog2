<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user:id,name,email', 'product:id,name', 'variant:id,size,color'])
            ->latest()->latest('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($search, $like) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('customer_name', 'like', $like)
                  ->orWhere('customer_phone', 'like', $like)
                  ->orWhereHas('user', function ($u) use ($like) {
                      $u->where('name', 'like', $like)->orWhere('email', 'like', $like);
                  })
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like));
            });
        }

        $orders = $query->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $service)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,returned',
            'cancellation_reason' => 'required_if:status,cancelled,returned|nullable|string|max:500',
        ], [
            'cancellation_reason.required_if' => 'يجب كتابة سبب الإلغاء أو الإرجاع نظراً لتغيير حالة الطلب.',
        ]);

        try {
            $service->change($order->id, $data['status'], $data['cancellation_reason'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Order status change failed', ['order' => $order->id, 'exception' => $e]);
            return back()->with('error', 'حدث خطأ أثناء التحديث، يرجى المحاولة مرة أخرى.');
        }

        return back()->with('success', 'تم تحديث حالة الطلب وإدارة المخزون بنجاح!');
    }

    public function updateReason(Request $request, Order $order)
    {
        if (! in_array($order->status, ['cancelled', 'returned'], true)) {
            return back()->with('error', 'يمكن تعديل السبب فقط للطلبات الملغية أو المرتجعة.');
        }

        $data = $request->validate(['cancellation_reason' => 'required|string|max:500'], [
            'cancellation_reason.required' => 'يجب كتابة السبب.',
        ]);

        $order->update(['cancellation_reason' => $data['cancellation_reason']]);

        return back()->with('success', 'تم تحديث سبب الطلب بنجاح!');
    }

    /**
     * Only cancelled / returned orders may be removed permanently.
     */
    public function destroy(Order $order)
    {
        if (! in_array($order->status, ['cancelled', 'returned'], true)) {
            return back()->with('error', 'لا يمكن حذف طلب نشط أو مُسلَّم. قم بإلغائه أولاً لإعادة المخزون.');
        }

        // الحذف النهائي من قاعدة البيانات مباشرة
        $order->forceDelete();

        return back()->with('success', 'تم حذف الطلب نهائياً بنجاح.');
    }
}