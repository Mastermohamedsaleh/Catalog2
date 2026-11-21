<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FrontendController extends Controller
{
    /**
     * الأقسام الرئيسية النشطة مع عدد المنتجات (شامل الأقسام الفرعية).
     * بتتكاش 30 دقيقة، وبتتمسح لوحدها لما الأدمن يعدّل أي قسم أو منتج (CatalogObserver).
     */
    private function rootCategories(?int $limit = null): Collection
    {
        return CatalogCache::remember('root_categories_' . ($limit ?? 'all'), now()->addMinutes(30), function () use ($limit) {
            $query = Category::whereNull('parent_id')
                ->where('is_active', true)
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->with(['children' => fn ($q) => $q->where('is_active', true)
                    ->withCount(['products' => fn ($p) => $p->where('is_active', true)])])
                ->orderBy('name');

            if ($limit) {
                $query->limit($limit);
            }

            return $query->get()->each(function (Category $c) {
                $c->products_count += $c->children->sum('products_count');
            });
        });
    }

    public function welcome()
    {
        $categories = $this->rootCategories(8);
        return view('welcome', compact('categories'));
    }

    public function allCategories()
    {
        $allCategories = $this->rootCategories();
        return view('frontend.all-categories', compact('allCategories'));
    }

    public function showCategory(Request $request, $slug)
    {
        // (int) بدل get(): لو حد كتب ?page[]=1 كان هيحصل خطأ "Array to string conversion" ويطلع 500.
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('q', ''));

        // كل الشغل الحقيقي (الاستعلام) في دالة واحدة، عشان نقدر نستخدمها بكاش أو من غير كاش.
        $build = function () use ($slug, $search) {
            $category = Category::with(['children' => fn ($q) => $q->where('is_active', true)
                    ->withCount(['products' => fn ($p) => $p->where('is_active', true)])
                    ->orderBy('name')])
                ->where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            $categoryIds = $category->children->pluck('id')->push($category->id);

            $productsQuery = Product::whereIn('category_id', $categoryIds)
                ->where('is_active', true);

            if ($search !== '') {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $productsQuery->where(function ($q) use ($like) {
                    $q->where('name', 'LIKE', $like)
                      ->orWhere('sku', 'LIKE', $like);
                });
            }

            $products = $productsQuery->latest()->latest('id')->paginate(12);

            if ($search !== '') {
                $products->appends(['q' => $search]);
            }

            return ['category' => $category, 'products' => $products];
        };

        // نكاشي بس لو مفيش بحث وفي أول 50 صفحة.
        // ليه؟ لو كاشينا كل كلمة بحث وكل رقم صفحة، أي حد يقدر يبعت آلاف الطلبات بكلمات عشوائية
        // فيتملي Redis (وفيه الجلسات كمان، فالناس تتعمل لها logout). البحث الحي من الداتابيز كويس لأنه نادر.
        $data = ($search === '' && $page <= 50)
            ? CatalogCache::remember('category:' . $slug . ':p' . $page, now()->addMinutes(15), $build)
            : $build();

        // الـ paginator المتخزن بيحتفظ برابط الصفحة اللي اتعمل منها أول مرة (ممكن يكون IP أو دومين تاني).
        // السطر ده بيضبط الروابط على العنوان الحالي.
        $data['products']->withPath($request->url());

        return view('frontend.category', [
            'category' => $data['category'],
            'products' => $data['products'],
        ]);
    }

    public function showProduct($id)
    {
        // مفتاح كل منتج لوحده، وبيتمسح لوحده لما مخزونه يتغير (StockService)
        // أو لما الأدمن يعدّل أي حاجة (CatalogObserver).
        $product = CatalogCache::remember(CatalogCache::productKey((int) $id), now()->addMinutes(30), function () use ($id) {
            return Product::with(['category', 'images', 'variants'])
                ->where('is_active', true)
                ->whereHas('category', fn ($q) => $q->where('is_active', true))
                ->findOrFail($id);
        });

        // رابط الفيديو (video_embed_url) بيتحسب من لينك يوتيوب المخزّن وقت العرض، ومفيهوش توكن بينتهي.
        return view('frontend.product-details', compact('product'));
    }
}
