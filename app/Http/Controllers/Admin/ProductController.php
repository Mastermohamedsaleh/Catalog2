<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\VideoLinks;
use App\Services\MediaService;
use App\Services\StockService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
        protected StockService $stockService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Product::with(['category:id,name', 'images', 'variants'])->latest()->latest('id');

        if ($request->filled('search')) {
            $like = '%' . addcslashes(trim($request->search), '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('sku', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        $products = $query->paginate(10);
        // Products belong to leaf categories only (a parent shows its children's products).
        $categories = Category::whereDoesntHave('children')->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    private function rules(?Product $product = null): array
    {
        $idRule = $product ? ',' . $product->id : '';

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id', function ($attr, $value, $fail) {
                if (Category::whereKey($value)->whereHas('children')->exists()) {
                    $fail('اختر قسماً فرعياً، لا يمكن إضافة منتج مباشرة إلى قسم رئيسي له أقسام فرعية.');
                }
            }],
            'name'        => 'required|string|max:255|unique:products,name' . $idRule,
            'sku'         => 'nullable|string|max:100|unique:products,sku' . $idRule,
            'price'       => 'required|numeric|min:0.01|max:99999999',
            'stock'       => 'required|integer|min:0|max:10000000',
            'original_stock' => 'nullable|integer|min:0',
            'main_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'images'      => 'nullable|array|max:10',
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            // لينك يوتيوب (Unlisted) للمشاهدة. أي لينك مش يوتيوب بيترفض عشان ميتحطش جوه iframe.
            'video_url'   => ['nullable', 'string', 'max:255', function ($attr, $value, $fail) {
                if (filled($value) && ! VideoLinks::youtubeId($value)) {
                    $fail('لينك الفيديو لازم يكون لينك يوتيوب صحيح (youtube.com أو youtu.be).');
                }
            }],
            // لينك التنزيل المنفصل (Google Drive / Dropbox ...). http/https بس.
            'video_download_url' => ['nullable', 'url:http,https', 'max:500'],
            'remove_video' => 'nullable|boolean',
            'description' => 'nullable|string|max:5000',
            'variants'                  => 'nullable|array|max:100',
            'variants.*.size'           => 'nullable|string|max:50',
            'variants.*.color'          => 'nullable|string|max:50',
            'variants.*.stock'          => 'nullable|integer|min:0|max:10000000',
            'variants.*.original_stock' => 'nullable|integer|min:0',
            'variants.*.price_modifier' => 'nullable|numeric|min:0|max:99999999',
        ];
    }

    private function messages(): array
    {
        return [
            'name.unique'          => 'عذراً، هذا الاسم مسجل مسبقاً لمنتج آخر. يرجى اختيار اسم فريد.',
            'sku.unique'           => 'كود المنتج مستخدم من قبل، يرجى اختيار كود فريد.',
            'category_id.required' => 'يرجى اختيار القسم التابع له المنتج.',
            'price.required'       => 'سعر الجملة مطلوب.',
            'price.min'            => 'سعر الجملة لازم يكون أكبر من صفر.',
            'images.max'           => 'الحد الأقصى 10 صور في المرة الواحدة.',
            'images.*.image'       => 'كل ملف في معرض الصور لازم يكون صورة.',
            'images.*.max'         => 'حجم الصورة الواحدة لا يزيد عن 2 ميجا.',
            'main_image.max'       => 'حجم الصورة الرئيسية لا يزيد عن 2 ميجا.',
            'stock.required'       => 'كمية المخزون مطلوبة.',
        ];
    }

    /** رسالة الأدمن (عربي): مجموع المقاسات لازم يساوي الإجمالي. */
    private function stockMismatchMessage(int $total, int $sum): string
    {
        return "مجموع مخزون المقاسات ({$sum}) لا يساوي المخزون الإجمالي ({$total}). وزّع الإجمالي على المقاسات بحيث يكون مجموعها {$total} بالظبط، أو غيّر الإجمالي ليطابق المجموع.";
    }

    /** Variant rows with a size or a color, one per (size, color) pair. */
    private function submittedVariants(Request $request): array
    {
        if (! $request->has('has_variants')) {
            return [];
        }

        $rows = [];
        foreach ((array) $request->input('variants', []) as $v) {
            $size  = isset($v['size']) && trim($v['size']) !== '' ? trim($v['size']) : null;
            $color = isset($v['color']) && trim($v['color']) !== '' ? trim($v['color']) : null;
            if ($size === null && $color === null) {
                continue;
            }
            $key = mb_strtolower($size . '|' . $color);
            if (isset($rows[$key])) {
                throw ValidationException::withMessages(['variants' => "المقاس واللون مكررين: {$size} {$color}"]);
            }
            $rows[$key] = [
                'size'           => $size,
                'color'          => $color,
                'stock'          => (int) ($v['stock'] ?? 0),
                'original_stock' => isset($v['original_stock']) && $v['original_stock'] !== '' ? (int) $v['original_stock'] : null,
                'price_modifier' => $v['price_modifier'] ?? 0,
            ];
        }

        return $rows;
    }

    private function uniqueSku(?string $wanted): string
    {
        $sku = $wanted ?: 'PRD-' . strtoupper(Str::random(6));
        while (Product::where('sku', $sku)->exists()) {
            $sku = 'PRD-' . strtoupper(Str::random(6));
        }
        return $sku;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());
        $variants = $this->submittedVariants($request);

        // المخزون الإجمالي هو الأساس: لو فيه مقاسات لازم مجموعها يساوي الإجمالي بالظبط (مفيش تعديل صامت في الإجمالي).
        if ($variants) {
            $sum = array_sum(array_column($variants, 'stock'));
            if ($sum !== (int) $request->stock) {
                throw ValidationException::withMessages(['stock' => $this->stockMismatchMessage((int) $request->stock, $sum)]);
            }
        }

        // لينك التنزيل من غير لينك يوتيوب ملوش معنى. بنرفضه قبل أي رفع صور.
        if (! VideoLinks::youtubeId($request->input('video_url')) && $request->filled('video_download_url')) {
            throw ValidationException::withMessages(['video_url' => 'أضف لينك يوتيوب أولاً، أو امسح لينك التنزيل.']);
        }

        $uploaded = []; // files written so far: removed again if anything below fails
        $imagePath = $videoPath = null;

        try {
            if ($request->hasFile('main_image')) {
                $uploaded[] = $imagePath = $this->mediaService->uploadAndOptimizeImage($request->file('main_image'), 'products/images');
            }
            $videoPath = VideoLinks::normalizeYoutube($request->input('video_url')); // لينك يوتيوب موحّد، مفيش رفع ملفات
            $galleryPaths = [];
            foreach ((array) $request->file('images', []) as $file) {
                $uploaded[] = $galleryPaths[] = $this->mediaService->uploadAndOptimizeImage($file, 'products/gallery');
            }

            DB::transaction(function () use ($request, $variants, $imagePath, $videoPath, $galleryPaths) {
                $product = Product::create([
                    'category_id' => $request->category_id,
                    'name'        => $request->name,
                    'sku'         => $this->uniqueSku($request->filled('sku') ? $request->sku : null),
                    'slug'        => $this->uniqueSlug($request->name),
                    'price'       => $request->price,
                    'stock'       => $variants ? array_sum(array_column($variants, 'stock')) : (int) $request->stock,
                    'main_image'  => $imagePath,
                    'video_url'   => $videoPath,
                    'video_download_url' => $request->filled('video_download_url') ? trim($request->input('video_download_url')) : null,
                    'description' => $request->description,
                    'is_active'   => $request->has('is_active'),
                ]);

                foreach ($galleryPaths as $path) {
                    ProductImage::create(['product_id' => $product->id, 'image_path' => $path]);
                }

                foreach ($variants as $v) {
                    ProductVariant::create([
                        'product_id'     => $product->id,
                        'size'           => $v['size'],
                        'color'          => $v['color'],
                        'stock'          => $v['stock'],
                        'price_modifier' => $v['price_modifier'],
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->cleanup($uploaded);
            return back()->withInput()->withErrors(['name' => 'تعذر الحفظ بسبب تكرار الاسم أو الكود، حاول مرة أخرى.']);
        } catch (\Throwable $e) {
            $this->cleanup($uploaded);
            Log::error('Product create failed', ['exception' => $e]);
            return back()->withInput()->withErrors(['name' => 'حدث خطأ أثناء حفظ المنتج، لم يتم حفظ أي بيانات.']);
        }

        return redirect()->route('admin.products.index')->with('success', 'تم إضافة المنتج مع صوره ومتغيراته بنجاح!');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate($this->rules($product), $this->messages());
        $variants = $this->submittedVariants($request);

        $uploaded = [];  // new files: removed if the update fails
        $toDelete = [];  // old files: removed only AFTER the update committed
        $oldVideo = null;

        try {
            $newMain = $newVideo = null;
            if ($request->hasFile('main_image')) {
                $uploaded[] = $newMain = $this->mediaService->uploadAndOptimizeImage($request->file('main_image'), 'products/images');
            }
            $newVideo = VideoLinks::normalizeYoutube($request->input('video_url'));

            // الفورم بيعرض لينك يوتيوب الحالي جوه الحقل. لو الأدمن مسحه وسابه فاضي يبقى قصده يشيل الفيديو.
            // (القديم كان بيعتبر الحقل الفاضي "سيبه زي ما هو" فاللينك كان بيرجع تاني بعد الحفظ.)
            // الاستثناء الوحيد: فيديو قديم متخزن كملف على السيرفر، الحقل بتاعه بيظهر فاضي دايماً، فمايتمسحش إلا بالـ checkbox.
            $hasLegacyFile = $product->has_legacy_video;
            $fieldCleared  = $request->has('video_url') && ! $request->filled('video_url') && ! $hasLegacyFile;
            $removeVideo   = ! $newVideo && ($request->boolean('remove_video') || $fieldCleared);

            // لينك التنزيل من غير فيديو ملوش معنى: لازم يكون فيه لينك يوتيوب (جديد أو موجود ومش بيتشال).
            $finalHasVideo = $newVideo || (filled($product->video_url) && ! $removeVideo);
            if (! $finalHasVideo && $request->filled('video_download_url')) {
                throw ValidationException::withMessages(['video_url' => 'أضف لينك يوتيوب أولاً، أو امسح لينك التنزيل.']);
            }

            // إجمالي صور المعرض بعد الإضافة لا يزيد عن 10.
            $newImages = count((array) $request->file('images', []));
            if ($newImages && $product->images()->count() + $newImages > 10) {
                throw ValidationException::withMessages(['images' => 'معرض الصور لا يزيد عن 10 صور. احذف بعض الصور القديمة أولاً.']);
            }
            $galleryPaths = [];
            foreach ((array) $request->file('images', []) as $file) {
                $uploaded[] = $galleryPaths[] = $this->mediaService->uploadAndOptimizeImage($file, 'products/gallery');
            }

            DB::transaction(function () use ($request, $product, $variants, $newMain, $newVideo, $removeVideo, $galleryPaths, &$toDelete, &$oldVideo) {
                $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $existing = ProductVariant::where('product_id', $locked->id)->lockForUpdate()->get();

                // Un-ticking "variants" (or removing a size) is refused while orders point at it.
                $keep = collect($variants)->keys()->all();
                foreach ($existing as $row) {
                    $key = mb_strtolower($row->size . '|' . $row->color);
                    if (! in_array($key, $keep, true) && Order::query()->where('product_variant_id', $row->id)->exists()) {
                        throw ValidationException::withMessages([
                            'variants' => "لا يمكن حذف المقاس ({$row->size} {$row->color}) لوجود أوردرات عليه. اجعل مخزونه 0 بدلاً من حذفه.",
                        ]);
                    }
                }

                // مجموع المقاسات (بعد حساب القيم الحية للمقاسات اللي ماتلمسهاش) لازم يساوي المخزون الإجمالي.
                $effective = [];
                if ($variants) {
                    $byKeyLive = $existing->keyBy(fn ($r) => mb_strtolower($r->size . '|' . $r->color));
                    foreach ($variants as $key => $v) {
                        $row = $byKeyLive->get($key);
                        $effective[$key] = ($row && $v['original_stock'] !== null && $v['original_stock'] === $v['stock']) ? (int) $row->stock : (int) $v['stock'];
                    }
                    $originalTotal = $request->filled('original_stock') ? (int) $request->original_stock : null;
                    // الإجمالي ماتغيرش في الفورم => المخزون الحي (ممكن الأوردرات غيّرته). اتغير => اللي الأدمن كتبه.
                    $target = ($originalTotal !== null && $originalTotal === (int) $request->stock) ? (int) $locked->stock : (int) $request->stock;
                    $sum = array_sum($effective);
                    if ($sum !== $target) {
                        throw ValidationException::withMessages(['stock' => $this->stockMismatchMessage($target, $sum)]);
                    }
                }

                if ($newMain) { $toDelete[] = $locked->main_image; }
                if (($newVideo && $newVideo !== $locked->video_url) || $removeVideo) { $oldVideo = $locked->video_url; }

                $locked->update([
                    'category_id' => $request->category_id,
                    'name'        => $request->name,
                    'sku'         => $request->filled('sku') ? $request->sku : $locked->sku,
                    'slug'        => $this->uniqueSlug($request->name, $locked->id),
                    'price'       => $request->price,
                    'main_image'  => $newMain ?? $locked->main_image,
                    'video_url'   => $newVideo ?? ($removeVideo ? null : $locked->video_url),
                    // لينك التنزيل: لو الحقل اتبعت (حتى فاضي) بنحفظه، ولو الفيديو اتشال بيتشال معاه.
                    'video_download_url' => $removeVideo ? null
                        : ($request->has('video_download_url')
                            ? ($request->filled('video_download_url') ? trim($request->input('video_download_url')) : null)
                            : $locked->video_download_url),
                    'description' => $request->description,
                    'is_active'   => $request->has('is_active'),
                ]);

                foreach ($galleryPaths as $path) {
                    ProductImage::create(['product_id' => $locked->id, 'image_path' => $path]);
                }

                if ($variants) {
                    // Update by (size, color) so variant ids (and the orders that reference them) survive.
                    $byKey = $existing->keyBy(fn ($r) => mb_strtolower($r->size . '|' . $r->color));
                    foreach ($variants as $key => $v) {
                        $row = $byKey->get($key);
                        if ($row) {
                            $row->update(['stock' => $effective[$key], 'price_modifier' => $v['price_modifier']]);
                        } else {
                            ProductVariant::create([
                                'product_id' => $locked->id, 'size' => $v['size'], 'color' => $v['color'],
                                'stock' => $v['stock'], 'price_modifier' => $v['price_modifier'],
                            ]);
                        }
                    }
                    foreach ($existing as $row) {
                        if (! in_array(mb_strtolower($row->size . '|' . $row->color), $keep, true)) {
                            $row->delete();
                        }
                    }
                    $this->stockService->syncParentStock($locked->id);
                } else {
                    ProductVariant::where('product_id', $locked->id)->delete();
                    $original = $request->filled('original_stock') ? (int) $request->original_stock : null;
                    // Unchanged stock field => keep live stock; changed => the admin set it explicitly.
                    if ($original === null || $original !== (int) $request->stock) {
                        $locked->update(['stock' => (int) $request->stock]);
                    }
                }
            }, 3);
        } catch (ValidationException $e) {
            $this->cleanup($uploaded);
            throw $e;
        } catch (UniqueConstraintViolationException $e) {
            $this->cleanup($uploaded);
            return back()->withInput()->withErrors(['name' => 'تعذر الحفظ بسبب تكرار الاسم أو الكود، حاول مرة أخرى.']);
        } catch (\Throwable $e) {
            $this->cleanup($uploaded);
            Log::error('Product update failed', ['product' => $product->id, 'exception' => $e]);
            return back()->withInput()->withErrors(['name' => 'حدث خطأ أثناء تحديث المنتج، لم يتم حفظ أي تغيير.']);
        }

        $this->cleanup($toDelete); // committed: now it is safe to drop the replaced files
        $this->discardVideo($oldVideo);

        return redirect()->route('admin.products.index')->with('success', 'تم تحديث المنتج بنجاح!');
    }

    public function destroy(Product $product)
    {
        // Orders are financial records. Deleting a product with orders is refused (the foreign key also blocks it).
        if (Order::query()->where('product_id', $product->id)->exists()) {
            return back()->with('error', 'لا يمكن حذف منتج عليه أوردرات. قم بإلغاء تفعيله بدلاً من حذفه.');
        }

        $video = $product->video_url;
        $files = array_filter([$product->main_image]);
        $files = array_merge($files, $product->images()->pluck('image_path')->all());

        try {
            DB::transaction(function () use ($product) {
                $product->images()->delete();
                $product->variants()->delete();
                $product->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Product delete failed', ['product' => $product->id, 'exception' => $e]);
            return back()->with('error', 'تعذر حذف المنتج.');
        }

        $this->cleanup($files);
        $this->discardVideo($video);

        return redirect()->route('admin.products.index')->with('success', 'تم حذف المنتج وملفاته ومتغيراته بنجاح.');
    }

    public function destroyImage(Request $request, ProductImage $image)
    {
        $path = $image->image_path;
        $image->delete();
        $this->mediaService->deleteFile($path);

        // الزرار في مودال التعديل بيبعت fetch (من غير ما يقفل المودال أو يعيد تحميل الصفحة).
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'تم حذف الصورة من المعرض بنجاح.');
    }

    /** لينكات يوتيوب مفيهاش حاجة تتمسح. بنمسح بس الملفات القديمة اللي كانت على السيرفر. */
    private function discardVideo(?string $video): void
    {
        if (! $video || VideoLinks::youtubeId($video) || ! str_contains($video, '/') || preg_match('~^https?://~i', $video)) {
            return;
        }

        $this->mediaService->deleteFile($video);
    }

    private function cleanup(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $this->mediaService->deleteFile($path);
        }
    }
}
