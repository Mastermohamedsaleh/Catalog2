<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(protected MediaService $mediaService)
    {
    }

    public function index()
    {
        $categories = Category::with('parent:id,name', 'children:id,parent_id,name')
            ->withCount('products')
            ->latest()->latest('id')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $products = $category->products()->latest('id')->paginate(10);
        return view('admin.categories.show', compact('category', 'products'));
    }

    /** Parent rules: max 2 levels, never itself, never a descendant, and a category with children stays a root. */
    private function parentRules(?Category $self = null): array
    {
        return ['nullable', 'integer', 'exists:categories,id', function ($attr, $value, $fail) use ($self) {
            if (! $value) {
                return;
            }
            $parent = Category::find($value);
            if ($parent->parent_id !== null) {
                $fail('لا يمكن اختيار قسم فرعي كقسم رئيسي (الحد الأقصى مستويان).');
            }
            if ($self) {
                if ((int) $value === $self->id) {
                    $fail('لا يمكن أن يكون القسم تابعاً لنفسه.');
                }
                if ($self->children()->exists()) {
                    $fail('هذا القسم لديه أقسام فرعية، لا يمكن جعله فرعياً.');
                }
            }
        }];
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'parent_id'   => $this->parentRules(),
            'description' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
        ], [
            'name.unique' => 'هذا الاسم مستخدم من قبل، يرجى اختيار اسم فريد للقسم.',
        ]);

        $imagePath = null;
        try {
            if ($request->hasFile('image')) {
                $imagePath = $this->mediaService->uploadAndOptimizeImage($request->file('image'), 'categories');
            }

            Category::create([
                'name'        => $data['name'],
                'parent_id'   => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'slug'        => $this->uniqueSlug($data['name']),
                'is_active'   => $request->boolean('is_active'),
                'image'       => $imagePath,
            ]);
        } catch (\Throwable $e) {
            $this->mediaService->deleteFile($imagePath);
            Log::error('Category create failed', ['exception' => $e]);
            return back()->withInput()->withErrors(['name' => 'تعذر حفظ القسم، يرجى المحاولة مرة أخرى.']);
        }

        return redirect()->back()->with('success', 'Category created successfully!');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'parent_id'   => $this->parentRules($category),
            'description' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=6000,max_height=6000',
        ], [
            'name.unique' => 'هذا الاسم مستخدم من قبل، يرجى اختيار اسم فريد للقسم.',
        ]);

        $newImage = null;
        $oldImage = null;
        try {
            if ($request->hasFile('image')) {
                $newImage = $this->mediaService->uploadAndOptimizeImage($request->file('image'), 'categories');
                $oldImage = $category->image;
            }

            // The slug is NOT regenerated on rename: links and bookmarks keep working.
            $category->update([
                'name'        => $data['name'],
                'parent_id'   => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active'   => $request->boolean('is_active'),
                'image'       => $newImage ?? $category->image,
            ]);
        } catch (\Throwable $e) {
            $this->mediaService->deleteFile($newImage);
            Log::error('Category update failed', ['category' => $category->id, 'exception' => $e]);
            return back()->withInput()->withErrors(['name' => 'تعذر تحديث القسم، يرجى المحاولة مرة أخرى.']);
        }

        $this->mediaService->deleteFile($oldImage); // only after the row points at the new file

        return redirect()->back()->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', 'لا يمكن حذف قسم يحتوي على أقسام فرعية أو منتجات. انقلها أو احذفها أولاً.');
        }

        $image = $category->image;
        $category->delete();
        $this->mediaService->deleteFile($image);

        return redirect()->back()->with('success', 'Category deleted successfully!');
    }
}
