@extends('layouts.admin')

@section('title', 'Products · AksSap Admin')
@section('page_title', 'Store Products')

@section('inline_errors', '1')
@section('content')
<div class="card-soft mb-4">
    <!-- Header, Search & Add Button -->
    <div class="p-3 p-lg-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <h2 class="h6 fw-bold mb-0">All Products</h2>
        
        <!-- فورم البحث المباشر بالاسم أو الكود -->
        <form action="{{ route('admin.products.index') }}" method="GET" class="d-flex gap-2 w-100 w-md-auto" style="max-width: 350px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or code..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap"><i class="bi bi-search"></i></button>
            @if(request('search'))
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-danger btn-sm" title="Clear Search"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>

        <button class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-lg me-1"></i> Add new product
        </button>
    </div>

    <!-- تنبيه عام لو فيه أي أخطاء في الـ Validation -->
    @if ($errors->any())
        <div class="alert alert-danger m-3 mb-0 rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div>
                <strong>تنبيه!</strong> {{ $errors->first() }}
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success m-3 mb-0 rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Table -->
    <div class="table-responsive p-3">
        <table class="table table-modern align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Code</th>
                    <th>Video Preview</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Wholesale Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $products->firstItem() + $loop->index }}</td>
                        
                        <!-- معاينة الصورة الرئيسية -->
                        <td>
                            @if($product->main_image)
                                <a href="{{ asset('storage/' . $product->main_image) }}" target="_blank" title="View Full Image">
                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="Product" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                </a>
                            @else
                                <span class="badge bg-light text-muted border">No Image</span>
                            @endif
                        </td>

                        <!-- كود المنتج -->
                        <td>
                            <span class="badge bg-dark font-monospace">{{ $product->sku ?? 'N/A' }}</span>
                        </td>

                        <!-- معاينة الفيديو -->
                        <td>
                            @if($product->video_embed_url)
                                <button type="button" class="btn btn-sm btn-outline-primary" data-video-preview="{{ $product->video_embed_url }}" title="Watch Video">
                                    <i class="bi bi-play-circle-fill me-1"></i> Watch
                                </button>
                            @elseif($product->has_legacy_video)
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-video-preview="{{ asset('storage/' . $product->video_url) }}" data-video-file="1" title="Watch Video">
                                    <i class="bi bi-play-circle-fill me-1"></i> Watch
                                </button>
                            @else
                                <span class="badge bg-light text-muted border">No Video</span>
                            @endif
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $product->name }}</div>
                            
                            <!-- زر عرض صور المعرض السريع -->
                            @if($product->images->count() > 0)
                                <button type="button" class="btn btn-sm btn-light border mt-1 text-primary p-0 px-2 py-0 shadow-sm" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#galleryModal{{ $product->id }}">
                                    <i class="bi bi-images me-1"></i> معرض الصور ({{ $product->images->count() }})
                                </button>
                            @endif

                            @if($product->variants->count() > 0)
                                <span class="badge bg-secondary ms-1" style="font-size: 10px;">{{ $product->variants->count() }} variants</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $product->category->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-primary">{{ $product->price }} {{ config('shop.currency') }}</span>
                        </td>
                        <td>
                            @if($product->stock > 0)
                                <span class="badge badge-soft-success">{{ $product->stock }} in stock</span>
                            @else
                                <span class="badge badge-soft-danger">Out of stock</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $product->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <!-- زرار التعديل -->
                            <button class="btn btn-icon btn-light" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editProductModal{{ $product->id }}" 
                                    title="Edit Product">
                                <i class="bi bi-pencil text-primary"></i>
                            </button>

                            <!-- زرار الحذف -->
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline delete-form" data-name="{{ $product->name }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-light text-danger" title="Delete Product">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($products) && $products->hasPages())
        <div class="p-3 d-flex justify-content-between align-items-center small text-muted border-top">
            <span>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}</span>
            <div>{{ $products->appends(request()->query())->links() }}</div>
        </div>
    @endif
</div>

<!-- ================= مودال إضافة منتج جديد ================= -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow-lg needs-validation" novalidate>
        @csrf
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Add New Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-start">
        
        <!-- اسم المنتج والكود -->
        <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Enter product name" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label small fw-semibold">Product code</label>
              <input type="text" class="form-control @error('sku') is-invalid @enderror" name="sku" value="{{ old('sku') }}" placeholder="اختياري (يولد تلقائياً)">
            </div>
        </div>

        <!-- القسم -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
              <option value="">-- Choose Category --</option>
              @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                      {{ $cat->name }}
                  </option>
              @endforeach
          </select>
        </div>

        <!-- السعر والمخزون -->
        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-semibold">Wholesale Price ({{ config('shop.currency') }}) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" name="price" value="{{ old('price') }}" placeholder="e.g. 150" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label small fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
              <input type="number" class="form-control @error('stock') is-invalid @enderror" name="stock" value="{{ old('stock', 10) }}" required>
            </div>
        </div>

        <!-- الصورة الرئيسية -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Main Image</label>
          <input type="file" class="form-control" name="main_image" accept="image/*">
        </div>

        <!-- صور المعرض المتعددة -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Gallery Images (Multiple)</label>
          <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
          <div class="form-text small text-muted">يمكنك اختيار أكثر من صورة لعرضها في تفاصيل المنتج.</div>
        </div>

        <!-- الفيديو -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Video (YouTube link)</label>
          @include('admin.products._video-uploader')
                  </div>

        <!-- قسم المتغيرات (المقاسات والألوان) -->
        <div class="card bg-light border-0 p-3 mb-3">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="has_variants" id="hasVariantsCheck" value="1" {{ old('has_variants') ? 'checked' : '' }}>
              <label class="form-check-label fw-bold small text-dark" for="hasVariantsCheck">هل المنتج يحتوي على مقاسات والألوان متعددة؟ (مثل الملابس والأحذية)</label>
            </div>
            
            <div id="variantsSection" style="display: {{ old('has_variants') ? 'block' : 'none' }};">
                <p class="small text-muted mb-2">أضف المقاسات والألوان المتاحة مع مخزونها الخاص (مجموع المخزون يجب أن يساوي المخزون الإجمالي). خانة "فرق السعر" = المبلغ الذي يُضاف لسعر المنتج الأساسي لهذا المقاس (اتركها 0 إن كان السعر نفس الأساسي):</p>
                <div id="variantsContainer">
                    <div class="row g-2 mb-2 variant-row">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="variants[0][size]" placeholder="Size (e.g. S, M, L)">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="variants[0][color]" placeholder="Color (e.g. Red, Black)">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control form-control-sm" name="variants[0][stock]" placeholder="Stock" value="0">
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control form-control-sm" name="variants[0][price_modifier]" placeholder="+ فرق السعر" value="0" min="0" step="0.01" title="فرق السعر عن السعر الأساسي (يُضاف للسعر)">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-variant" style="display:none;"><i class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="addVariantBtn"><i class="bi bi-plus"></i> إضافة مقاس/لون آخر</button>
            </div>
        </div>

        <!-- الوصف -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Product details...">{{ old('description') }}</textarea>
        </div>

        <!-- الحالة -->
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
          <label class="form-check-label small fw-semibold" for="isActiveCheck">Active Product</label>
        </div>

      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save product</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= مودالات التعديل لكل منتج ================= -->
@foreach($products as $product)
<div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow-lg">
        @csrf
        @method('PUT')
        
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Edit Product: {{ $product->name }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body text-start">
        
        <!-- اسم المنتج والكود -->
        <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" value="{{ $product->name }}" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label small fw-semibold">Product code</label>
              <input type="text" class="form-control font-monospace" name="sku" value="{{ $product->sku }}">
            </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control" required>
              @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                      {{ $cat->name }}
                  </option>
              @endforeach
          </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-semibold">Wholesale Price ({{ config('shop.currency') }}) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" name="price" value="{{ $product->price }}" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label small fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="stock" value="{{ $product->stock }}" min="0" required>
              {{-- Stock as it was when this form opened: if it is unchanged on save, the live stock is kept so new orders are not overwritten. --}}
              <input type="hidden" name="original_stock" value="{{ $product->stock }}">
            </div>
        </div>

        <!-- الصورة الرئيسية -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Main Image</label>
          @if($product->main_image)
              <div class="mb-2">
                  <img src="{{ asset('storage/' . $product->main_image) }}" alt="Current Image" class="rounded border" style="max-height: 80px;">
              </div>
          @endif
          <input type="file" class="form-control" name="main_image" accept="image/*">
          <div class="form-text small text-muted">اتركها فارغة إذا لم ترد تغيير الصورة.</div>
        </div>

        <!-- صور المعرض -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Gallery Images</label>
          @if($product->images->count() > 0)
              <div class="d-flex flex-wrap gap-2 mb-2 p-2 bg-light rounded border">
                  @foreach($product->images as $img)
                      <div class="position-relative border rounded p-1 bg-white js-gallery-item" style="width: 70px; height: 70px;">
                          <img src="{{ asset('storage/' . $img->image_path) }}" class="rounded w-100 h-100" style="object-fit: cover;" alt="">
                          <!-- حذف الصورة فوراً (fetch) من غير ما نقفل المودال. type=button عشان مايعملش submit للفورم -->
                          <button type="button" class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle p-0 js-delete-gallery-image"
                                  style="width: 20px; height: 20px; line-height: 1; font-size: 12px;"
                                  data-url="{{ route('admin.products.images.destroy', $img->id) }}" title="Delete this image" aria-label="Delete image">&times;</button>
                      </div>
                  @endforeach
              </div>
          @else
              <div class="text-muted small mb-2">لا توجد صور إضافية في المعرض.</div>
          @endif

          <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
        </div>

        <!-- الفيديو -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Product Video</label>
          @include('admin.products._video-uploader', ['product' => $product])
        </div>

        <!-- المتغيرات (تعديل وإضافة مقاسات جديدة) -->
        <div class="card bg-light border-0 p-3 mb-3">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input edit-has-variants-check" type="checkbox" name="has_variants" id="editHasVariantsCheck{{ $product->id }}" value="1" {{ $product->variants->count() > 0 ? 'checked' : '' }} data-product-id="{{ $product->id }}">
              <label class="form-check-label fw-bold small text-dark" for="editHasVariantsCheck{{ $product->id }}">تفعيل المقاسات والألوان لهذا المنتج</label>
            </div>
            
            <div id="editVariantsSection{{ $product->id }}" style="display: {{ $product->variants->count() > 0 ? 'block' : 'none' }};">
                <p class="small text-muted mb-2">يمكنك تعديل أو إضافة مقاسات ولون جديدة:</p>
                <div id="editVariantsContainer{{ $product->id }}">
                    @if($product->variants->count() > 0)
                        @foreach($product->variants as $index => $variant)
                            <div class="row g-2 mb-2 edit-variant-row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" name="variants[{{ $index }}][size]" value="{{ $variant->size }}" placeholder="Size">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" name="variants[{{ $index }}][color]" value="{{ $variant->color }}" placeholder="Color">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control form-control-sm" name="variants[{{ $index }}][stock]" value="{{ $variant->stock }}" min="0" placeholder="Stock">
                                    <input type="hidden" name="variants[{{ $index }}][original_stock]" value="{{ $variant->stock }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control form-control-sm" name="variants[{{ $index }}][price_modifier]" placeholder="+ فرق السعر" value="{{ (float) $variant->price_modifier + 0 }}" min="0" step="0.01" title="فرق السعر عن السعر الأساسي (يُضاف للسعر)">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-edit-variant"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="row g-2 mb-2 edit-variant-row">
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" name="variants[0][size]" placeholder="Size">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" name="variants[0][color]" placeholder="Color">
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control form-control-sm" name="variants[0][stock]" placeholder="Stock" value="0">
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control form-control-sm" name="variants[0][price_modifier]" placeholder="+ فرق السعر" value="0" min="0" step="0.01" title="فرق السعر عن السعر الأساسي (يُضاف للسعر)">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-edit-variant"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1 add-edit-variant-btn" data-product-id="{{ $product->id }}"><i class="bi bi-plus"></i> إضافة مقاس/لون جديد</button>
            </div>
        </div>

        <!-- الوصف -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea class="form-control" name="description" rows="3">{{ $product->description }}</textarea>
        </div>

        <!-- الحالة -->
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="is_active" id="editIsActiveCheck{{ $product->id }}" value="1" {{ $product->is_active ? 'checked' : '' }}>
          <label class="form-check-label small fw-semibold" for="editIsActiveCheck{{ $product->id }}">Active Product</label>
        </div>

      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update product</button>
      </div>
    </form>
  </div>
</div>
@endforeach

<!-- مودالات معرض الصور: carousel فيه الصورة الرئيسية + صور المعرض. (الزرار "معرض الصور" كان بيشاور على مودال مش موجود فمكانش بيفتح) -->
@foreach($products as $product)
  @if($product->main_image || $product->images->count() > 0)
  @php
      $slides = collect([$product->main_image])->filter()->merge($product->images->pluck('image_path'))->values();
  @endphp
  <div class="modal fade" id="galleryModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark">
        <div class="modal-header border-0">
          <h5 class="modal-title text-white">{{ $product->name }} <small class="opacity-75">({{ $slides->count() }} images)</small></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-0">
          <div id="galleryCarousel{{ $product->id }}" class="carousel slide" data-bs-ride="false">
            @if($slides->count() > 1)
            <div class="carousel-indicators">
              @foreach($slides as $i => $path)
                <button type="button" data-bs-target="#galleryCarousel{{ $product->id }}" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Image {{ $i + 1 }}"></button>
              @endforeach
            </div>
            @endif
            <div class="carousel-inner rounded-3">
              @foreach($slides as $i => $path)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                  <img src="{{ asset('storage/' . $path) }}" class="d-block mx-auto" style="max-height: 70vh; max-width: 100%; object-fit: contain;" alt="{{ $product->name }} {{ $i + 1 }}" loading="lazy">
                </div>
              @endforeach
            </div>
            @if($slides->count() > 1)
              <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel{{ $product->id }}" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
              <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel{{ $product->id }}" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
@endforeach

<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0"><h5 class="modal-title text-white">Video preview</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body pt-0"><div class="ratio ratio-16x9" id="videoPreviewBox"></div></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ---- Preview modal ----
    var modalEl = document.getElementById('videoPreviewModal'), box = document.getElementById('videoPreviewBox');
    document.querySelectorAll('[data-video-preview]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            box.innerHTML = '';
            var el;
            if (btn.dataset.videoFile) { el = document.createElement('video'); el.controls = true; el.src = btn.dataset.videoPreview; }
            else { el = document.createElement('iframe'); el.src = btn.dataset.videoPreview; el.allow = 'autoplay; fullscreen; picture-in-picture'; el.allowFullscreen = true; }
            box.appendChild(el);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
    modalEl.addEventListener('hidden.bs.modal', function () { box.innerHTML = ''; });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. التحكم في ظهور سكشن المتغيرات للإضافة
    const hasVariantsCheck = document.getElementById('hasVariantsCheck');
    const variantsSection = document.getElementById('variantsSection');
    
    if (hasVariantsCheck) {
        hasVariantsCheck.addEventListener('change', function() {
            variantsSection.style.display = this.checked ? 'block' : 'none';
        });
    }

    // إضافة صف جديد للمقاسات في مودال الإضافة
    let variantIndex = {{ old('variants') ? count(old('variants')) : 1 }};
    const addVariantBtn = document.getElementById('addVariantBtn');
    const variantsContainer = document.getElementById('variantsContainer');

    if (addVariantBtn) {
        addVariantBtn.addEventListener('click', function() {
            const rowHtml = `
                <div class="row g-2 mb-2 variant-row">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="variants[${variantIndex}][size]" placeholder="Size">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="variants[${variantIndex}][color]" placeholder="Color">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" name="variants[${variantIndex}][stock]" placeholder="Stock" value="0">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" name="variants[${variantIndex}][price_modifier]" placeholder="+ فرق السعر" value="0" min="0" step="0.01" title="فرق السعر عن السعر الأساسي (يُضاف للسعر)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-variant"><i class="bi bi-x"></i></button>
                    </div>
                </div>
            `;
            variantsContainer.insertAdjacentHTML('beforeend', rowHtml);
            variantIndex++;
            toggleRemoveButtons();
        });
    }

    // حذف صف من الإضافة
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-variant')) {
            e.target.closest('.variant-row').remove();
            toggleRemoveButtons();
        }
    });

    function toggleRemoveButtons() {
        const rows = document.querySelectorAll('.variant-row');
        rows.forEach((row, idx) => {
            const btn = row.querySelector('.remove-variant');
            if (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            }
        });
    }

    // 2. التحكم في مودالات التعديل (إظهار/إخفاء وإضافة صفوف مقاسات جديدة لكل منتج)
    document.querySelectorAll('.edit-has-variants-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const section = document.getElementById('editVariantsSection' + productId);
            if (section) {
                section.style.display = this.checked ? 'block' : 'none';
            }
        });
    });

    // إضافة صف جديد داخل مودال التعديل
    document.querySelectorAll('.add-edit-variant-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const container = document.getElementById('editVariantsContainer' + productId);
            const rowCount = container.querySelectorAll('.edit-variant-row').length;

            const rowHtml = `
                <div class="row g-2 mb-2 edit-variant-row">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="variants[${rowCount}][size]" placeholder="Size">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="variants[${rowCount}][color]" placeholder="Color">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" name="variants[${rowCount}][stock]" placeholder="Stock" value="0">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" name="variants[${rowCount}][price_modifier]" placeholder="+ فرق السعر" value="0" min="0" step="0.01" title="فرق السعر عن السعر الأساسي (يُضاف للسعر)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-edit-variant"><i class="bi bi-x"></i></button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        });
    });

    // حذف صف من التعديل
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-edit-variant')) {
            e.target.closest('.edit-variant-row').remove();
        }
    });
});
</script>
<script>
// معاينة الصور قبل الحفظ + فحص الحجم/النوع/العدد في المتصفح، وحذف صورة من المعرض بدون إعادة تحميل.
document.addEventListener('DOMContentLoaded', function () {
    const MAX_MB = 2, MAX_FILES = 10, TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    document.querySelectorAll('input[type=file][name="main_image"], input[type=file][name="images[]"]').forEach(function (input) {
        const holder = document.createElement('div');
        holder.className = 'd-flex flex-wrap gap-2 mt-2';
        const msg = document.createElement('div');
        msg.className = 'text-danger small mt-1';
        input.insertAdjacentElement('afterend', msg);
        input.insertAdjacentElement('afterend', holder);

        input.addEventListener('change', function () {
            holder.innerHTML = ''; msg.textContent = '';
            const files = Array.from(input.files || []);
            if (input.multiple && files.length > MAX_FILES) { msg.textContent = 'الحد الأقصى ' + MAX_FILES + ' صور.'; input.value = ''; return; }
            for (const f of files) {
                if (!TYPES.includes(f.type)) { msg.textContent = '"' + f.name + '" ليست صورة مدعومة (JPG / PNG / WEBP).'; input.value = ''; holder.innerHTML = ''; return; }
                if (f.size > MAX_MB * 1024 * 1024) { msg.textContent = '"' + f.name + '" أكبر من ' + MAX_MB + ' ميجا.'; input.value = ''; holder.innerHTML = ''; return; }
            }
            files.forEach(function (f) {
                const box = document.createElement('div');
                box.className = 'border rounded p-1 bg-white text-center';
                box.style.width = '84px';
                const img = document.createElement('img');
                img.className = 'rounded';
                img.style.cssText = 'width:100%;height:70px;object-fit:cover;cursor:zoom-in';
                img.alt = f.name;
                img.src = URL.createObjectURL(f);
                img.onload = function () { URL.revokeObjectURL(img.src); };
                img.addEventListener('click', function () { window.open(URL.createObjectURL(f), '_blank'); });
                const cap = document.createElement('div');
                cap.className = 'text-muted text-truncate';
                cap.style.fontSize = '10px';
                cap.textContent = f.name;
                box.append(img, cap);
                holder.appendChild(box);
            });
        });
    });

    document.querySelectorAll('.js-delete-gallery-image').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('حذف هذه الصورة نهائياً؟')) return;
            btn.disabled = true;
            fetch(btn.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            }).then(function (r) {
                if (!r.ok) throw new Error('failed');
                btn.closest('.js-gallery-item').remove();
            }).catch(function () { btn.disabled = false; alert('تعذر حذف الصورة، حاول مرة أخرى.'); });
        });
    });
});

    // تأكيد قبل حذف المنتج (كان بيتحذف مباشرة)
    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var name = form.dataset.name || 'هذا المنتج';
            if (!confirm('هل أنت متأكد من حذف المنتج "' + name + '"؟\nلا يمكن التراجع عن هذا الإجراء.')) e.preventDefault();
        });
    });
</script>
@endpush
