<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
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
            'price'       => 'required|numeric|min:0|max:99999999',
            'stock'       => 'required|integer|min:0|max:10000000',
            'original_stock' => 'nullable|integer|min:0',
            'main_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'images'      => 'nullable|array|max:10',
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'video_url'   => 'nullable|mimes:mp4,mov,avi,wmv|max:10240',
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
            'category_id.required'       => 'يرجى اختيار القسم التابع له المنتج.',
            'category_id.exists'         => 'القسم المختار غير موجود.',
            'name.required'              => 'اسم المنتج مطلوب.',
            'name.string'                => 'اسم المنتج يجب أن يكون نصاً صحيحاً.',
            'name.max'                   => 'اسم المنتج يجب ألا يتجاوز 255 حرفاً.',
            'name.unique'                => 'عذراً، هذا الاسم مسجل مسبقاً لمنتج آخر. يرجى اختيار اسم فريد.',
            'sku.max'                    => 'كود المنتج (SKU) يجب ألا يتجاوز 100 حرف.',
            'sku.unique'                 => 'كود المنتج (SKU) مستخدم من قبل، يرجى اختيار كود فريد.',
            'price.required'             => 'سعر الجملة مطلوب.',
            'price.numeric'              => 'سعر الجملة يجب أن يكون رقماً.',
            'price.min'                  => 'سعر الجملة لا يمكن أن يكون أقل من 0.',
            'price.max'                  => 'سعر الجملة تخطى الحد الأقصى المسموح به.',
            'stock.required'             => 'كمية المخزون الأساسي مطلوبة.',
            'stock.integer'              => 'كمية المخزون الأساسي يجب أن تكون رقماً صحيحاً.',
            'stock.min'                  => 'كمية المخزون الأساسي لا يمكن أن تكون أقل من 0.',
            'stock.max'                  => 'كمية المخزون تخطت الحد الأقصى المسموح به.',
            'main_image.image'           => 'الملف المرفوع كصورة رئيسية يجب أن يكون صورة صحيحة.',
            'main_image.mimes'           => 'صيغة الصورة الرئيسية يجب أن تكون: jpeg, png, jpg, أو webp.',
            'main_image.max'             => 'حجم الصورة الرئيسية يجب ألا يتجاوز 2 ميجابايت.',
            'main_image.dimensions'      => 'أبعاد الصورة الرئيسية كبيرة جداً (الحد الأقصى 6000×6000 بكسل).',
            'images.array'               => 'صيغة معرض الصور غير صحيحة.',
            'images.max'                 => 'الحد الأقصى لصور المعرض هو 10 صور فقط.',
            'images.*.image'             => 'جميع الملفات المرفوعة في المعرض يجب أن تكون صوراً صحيحة.',
            'images.*.mimes'             => 'صيغة صور المعرض يجب أن تكون: jpeg, png, jpg, أو webp.',
            'images.*.max'               => 'حجم كل صورة في المعرض يجب ألا يتجاوز 2 ميجابايت.',
            'images.*.dimensions'        => 'أبعاد إحدى صور المعرض كبيرة جداً (الحد الأقصى 6000×6000 بكسل).',
            'video_url.mimes'            => 'صيغة الفيديو يجب أن تكون: mp4, mov, avi, أو wmv.',
            'video_url.max'              => 'حجم الفيديو يجب ألا يتجاوز 10 ميجابايت.',
            'description.max'            => 'وصف المنتج يجب ألا يتجاوز 5000 حرف.',
            'variants.array'             => 'بيانات المقاسات والألوان غير صحيحة.',
            'variants.max'               => 'الحد الأقصى للمقاسات والألوان هو 100 عنصر.',
            'variants.*.size.max'        => 'اسم المقاس يجب ألا يتجاوز 50 حرفاً.',
            'variants.*.color.max'       => 'اسم اللون يجب ألا يتجاوز 50 حرفاً.',
            'variants.*.stock.integer'   => 'مخزون المقاس/اللون يجب أن يكون رقماً صحيحاً.',
            'variants.*.stock.min'       => 'مخزون المقاس/اللون لا يمكن أن يكون أقل من 0.',
            'variants.*.price_modifier.numeric' => 'السعر الإضافي للمقاس/اللون يجب أن يكون رقماً.',
            'variants.*.price_modifier.min'     => 'السعر الإضافي لا يمكن أن يكون أقل من 0.',
        ];
    }

    /** Variant rows with a size or a color, one per (size, color) pair, validated against parent stock. */
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
                throw ValidationException::withMessages([
                    'variants' => "المقاس واللون مكررين: {$size} {$color}"
                ]);
            }
            $rows[$key] = [
                'size'           => $size,
                'color'          => $color,
                'stock'          => (int) ($v['stock'] ?? 0),
                'original_stock' => isset($v['original_stock']) && $v['original_stock'] !== '' ? (int) $v['original_stock'] : null,
                'price_modifier' => $v['price_modifier'] ?? 0,
            ];
        }

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'variants' => 'لقد قمت بتفعيل خيار المقاسات والألوان، يرجى إدخال مقاس أو لون واحد على الأقل.',
            ]);
        }

        // التحقق من تطابق مجموع مخزون المتغيرات مع مخزون المنتج الأساسي
        $parentStock = (int) $request->input('stock', 0);
        $variantsSum = array_sum(array_column($rows, 'stock'));

        if ($variantsSum !== $parentStock) {
            throw ValidationException::withMessages([
                'stock' => "خطأ في توزيع المخزون: مخزون المنتج الأساسي ({$parentStock}) لا يطابق مجموع مخزون المقاسات والألوان ({$variantsSum}). يجب أن يتساوى المجموع مع المخزون الأساسي.",
            ]);
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

        $uploaded = []; // files written so far: removed again if anything below fails
        $imagePath = $videoPath = null;

        try {
            if ($request->hasFile('main_image')) {
                $uploaded[] = $imagePath = $this->mediaService->uploadAndOptimizeImage($request->file('main_image'), 'products/images');
            }
            if ($request->hasFile('video_url')) {
                $uploaded[] = $videoPath = $this->mediaService->uploadFile($request->file('video_url'), 'products/videos');
            }
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
                    'stock'       => (int) $request->stock,
                    'main_image'  => $imagePath,
                    'video_url'   => $videoPath,
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

        try {
            $newMain = $newVideo = null;
            if ($request->hasFile('main_image')) {
                $uploaded[] = $newMain = $this->mediaService->uploadAndOptimizeImage($request->file('main_image'), 'products/images');
            }
            if ($request->hasFile('video_url')) {
                $uploaded[] = $newVideo = $this->mediaService->uploadFile($request->file('video_url'), 'products/videos');
            }
            $galleryPaths = [];
            foreach ((array) $request->file('images', []) as $file) {
                $uploaded[] = $galleryPaths[] = $this->mediaService->uploadAndOptimizeImage($file, 'products/gallery');
            }

            DB::transaction(function () use ($request, $product, $variants, $newMain, $newVideo, $galleryPaths, &$toDelete) {
                $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $existing = ProductVariant::where('product_id', $locked->id)->lockForUpdate()->get();

                // Un-ticking "variants" (or removing a size) is refused while orders point at it.
                $keep = collect($variants)->keys()->all();
                foreach ($existing as $row) {
                    $key = mb_strtolower($row->size . '|' . $row->color);
                    if (! in_array($key, $keep, true) && Order::where('product_variant_id', $row->id)->exists()) {
                        throw ValidationException::withMessages([
                            'variants' => "لا يمكن حذف المقاس ({$row->size} {$row->color}) لوجود أوردرات عليه. اجعل مخزونه 0 بدلاً من حذفه.",
                        ]);
                    }
                }

                if ($newMain) { $toDelete[] = $locked->main_image; }
                if ($newVideo) { $toDelete[] = $locked->video_url; }

                $locked->update([
                    'category_id' => $request->category_id,
                    'name'        => $request->name,
                    'sku'         => $request->filled('sku') ? $request->sku : $locked->sku,
                    'slug'        => $this->uniqueSlug($request->name, $locked->id),
                    'price'       => $request->price,
                    'main_image'  => $newMain ?? $locked->main_image,
                    'video_url'   => $newVideo ?? $locked->video_url,
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
                            $stock = ($v['original_stock'] !== null && $v['original_stock'] === $v['stock']) ? $row->stock : $v['stock'];
                            $row->update(['stock' => $stock, 'price_modifier' => $v['price_modifier']]);
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

        return redirect()->route('admin.products.index')->with('success', 'تم تحديث المنتج بنجاح!');
    }

    public function destroy(Product $product)
    {
        if (Order::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'لا يمكن حذف منتج عليه أوردرات. قم بإلغاء تفعيله بدلاً من حذفه.');
        }

        $files = array_filter([$product->main_image, $product->video_url]);
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

        return redirect()->route('admin.products.index')->with('success', 'تم حذف المنتج وملفاته ومتغيراته بنجاح.');
    }

    public function destroyImage(ProductImage $image)
    {
        $path = $image->image_path;
        $image->delete();
        $this->mediaService->deleteFile($path);

        return back()->with('success', 'تم حذف الصورة من المعرض بنجاح.');
    }

    private function cleanup(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $this->mediaService->deleteFile($path);
        }
    }
}