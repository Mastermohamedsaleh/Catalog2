<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderStatusService;
use App\Services\StockService;
use App\Support\Money;
use App\Support\Phone;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['product:id,name,sku', 'variant:id,size,color'])
            ->where('user_id', Auth::id())
            ->latest()->latest('id')
            ->paginate(15);

        return view('frontend.merchant-orders', compact('orders'));
    }

    // البحث هنا من الداتابيز مباشرة من غير كاش، وده مقصود: التاجر لازم يشوف المخزون الحقيقي لحظة الطلب.
    public function searchProducts(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $like = '%' . addcslashes($search, '%_\\') . '%';

        $products = Product::with('variants:id,product_id,size,color,stock,price_modifier')
            ->where('is_active', true)
            ->where(function ($query) use ($like) {
                $query->where('name', 'LIKE', $like)->orWhere('sku', 'LIKE', $like);
            })
            ->orderBy('name')
            ->take(30)
            ->get(['id', 'name', 'sku', 'price', 'stock']);

        return response()->json($products);
    }

    public function store(Request $request, StockService $stock)
    {
        // رقم العميل بيتحوّل للصيغة الموحدة 05XXXXXXXX قبل التحقق والحفظ،
        // عشان فحص "الأوردر المكرر" والبحث في لوحة الأدمن يشتغلوا على شكل واحد للرقم.
        $request->merge(['customer_phone' => Phone::normalize($request->input('customer_phone'))]);

        $data = $request->validate([
            'product_id'         => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer',
            'stock'              => 'required|integer|min:1|max:1000',
            'customer_name'      => 'required|string|max:255',
            'customer_phone'     => ['required', 'string', Phone::rule()],
            'customer_address'   => 'required|string|max:500',
            // الإمارة لازم تكون واحدة من السبع في config/shop.php، ونوع الزبون جديد أو قديم، والملاحظات اختيارية.
            'emirate'            => ['required', 'string', Rule::in(config('shop.emirates'))],
            'customer_type'      => ['required', Rule::in(['new', 'returning'])],
            'notes'              => 'nullable|string|max:500',
            'selling_price'      => 'required|numeric|min:0|max:99999999',
        ], [
            'customer_phone.regex' => 'رقم هاتف العميل غير صحيح. ' . Phone::message(),
            'emirate.required'       => 'اختر الإمارة التي يقيم فيها الزبون.',
            'emirate.in'             => 'الإمارة المختارة غير صحيحة.',
            'customer_type.required' => 'حدد هل الزبون جديد أم قديم.',
        ]);

        $qty = (int) $data['stock'];

        try {
            $order = DB::transaction(function () use ($data, $qty, $stock) {
                // نقفل صف المنتج ونقرا السعر وحالة التفعيل منه هو (من الداتابيز مش من الكاش)،
                // فالأوردر عمره ما يتحسب على سعر قديم ولا يتباع منتج اتقفل.
                $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();

                if (! $product->is_active) {
                    throw ValidationException::withMessages(['product_id' => 'هذا المنتج غير متاح للطلب حالياً.']);
                }

                $variant = null;
                $hasVariants = ProductVariant::where('product_id', $product->id)->exists();

                if ($hasVariants) {
                    if (empty($data['product_variant_id'])) {
                        throw ValidationException::withMessages(['product_variant_id' => 'هذا المنتج يحتوي على مقاسات وألوان، يجب اختيار المقاس المطلوب بدقة!']);
                    }
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->whereKey($data['product_variant_id'])->lockForUpdate()->first();
                    if (! $variant) {
                        throw ValidationException::withMessages(['product_variant_id' => 'المقاس المختار غير صالح لهذا المنتج!']);
                    }
                } elseif (! empty($data['product_variant_id'])) {
                    throw ValidationException::withMessages(['product_variant_id' => 'هذا المنتج لا يحتوي على مقاسات.']);
                }

                $cost = Money::fromCents(Money::toCents($product->price) + ($variant ? Money::toCents($variant->price_modifier) : 0));

                if (Money::toCents($data['selling_price']) < Money::toCents($cost)) {
                    throw ValidationException::withMessages(['selling_price' => 'سعر البيع للعميل يجب ألا يكون أقل من سعر أساس المنتج!']);
                }

                // نفس التاجر + نفس المنتج/المقاس/الموبايل/الكمية خلال دقيقة = ضغطة مكررة على الزرار.
                $duplicate = Order::where('user_id', Auth::id())
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variant?->id)
                    ->where('customer_phone', $data['customer_phone'])
                    ->where('stock', $qty)
                    ->where('status', 'pending')
                    ->where('created_at', '>=', now()->subMinute())
                    ->exists();
                if ($duplicate) {
                    throw ValidationException::withMessages(['error' => 'تم تسجيل نفس الأوردر منذ لحظات، لن يتم تكراره.']);
                }

                // خصم المخزون. وجوه StockService بيتمسح كاش المنتج ده تلقائياً بعد الـ commit.
                // اتشال من هنا سطرين الكاش القديمين (Cache::forget و Cache::tags()->flush) للأسباب دي:
                //  1) Cache::tags بيرمي خطأ لو الكاش مش Redis، فالأوردر كان هيفشل والتاجر يشوف "حدث خطأ ما".
                //  2) مسح كاش الأقسام كله مع كل أوردر كان هيلغي فايدة الكاش مع 500 تاجر، وأعداد المنتجات في الأقسام
                //     مبتتغيرش بالأوردرات أصلاً (المتغير هو المخزون بس، ومكانه صفحة المنتج).
                //  3) المسح جوه الترانزكشن قبل الـ commit ممكن يخلّي طلب تاني يكتب النسخة القديمة تاني في الكاش.
                //  4) لو Redis وقع، الكاش ما لازمش يوقّف الأوردر.
                $stock->reserve($product->id, $variant?->id, $qty);

                return Order::create([
                    'user_id'            => Auth::id(),
                    'product_id'         => $product->id,
                    'product_variant_id' => $variant?->id,
                    'customer_name'      => $data['customer_name'],
                    'customer_phone'     => $data['customer_phone'],
                    'customer_address'   => $data['customer_address'],
                    'emirate'            => $data['emirate'],
                    'customer_type'      => $data['customer_type'],
                    'notes'              => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                    'product_price'      => $cost,
                    'selling_price'      => $data['selling_price'],
                    // رسوم التوصيل (30 درهم) بتتسجل مع الأوردر نفسه ومش بتدخل في ربح التاجر.
                    'delivery_fee'       => Money::fromCents(Money::toCents(config('shop.delivery_fee'))),
                    'profit'             => Money::profit($data['selling_price'], $cost, $qty),
                    'stock'              => $qty,
                    'status'             => 'pending',
                    'is_paid_to_wallet'  => false,
                ]);
            }, 3);
        } catch (ValidationException $e) {
            throw $e; // لارافل بترجّع التاجر للفورم برسائل الخطأ والبيانات اللي كتبها
        } catch (\Throwable $e) {
            Log::error('Merchant order failed', ['user' => Auth::id(), 'exception' => $e]);
            return back()->withErrors(['error' => 'حدث خطأ ما، يرجى المحاولة لاحقاً.'])->withInput();
        }

        return redirect()->route('merchant.orders.index')->with('success', 'تم تسجيل الأوردر بنجاح!');
    }

    public function cancel(Request $request, Order $order, OrderStatusService $service)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $data = $request->validate(['cancellation_reason' => 'nullable|string|max:500']);

        try {
            // إرجاع المخزون بيتم جوه OrderStatusService عن طريق StockService::release،
            // وهو اللي بيمسح كاش المنتج، فمفيش حاجة تتعمل هنا.
            $service->cancelByMerchant($order->id, Auth::id(), $data['cancellation_reason'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Merchant cancel failed', ['order' => $order->id, 'exception' => $e]);
            return back()->withErrors(['error' => 'حدث خطأ أثناء إلغاء الأوردر.']);
        }

        return redirect()->route('merchant.orders.index')->with('success', 'تم إلغاء الأوردر بنجاح !');
    }
}
