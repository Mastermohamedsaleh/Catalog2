@extends('layouts.admin')

@section('title', 'Orders · AksSap Admin')
@section('page_title', 'Store Orders')

@section('content')
<div class="card-soft mb-4">
    <!-- Header & Search -->
    <div class="p-3 p-lg-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <h2 class="h6 fw-bold mb-0">All orders</h2>
        
        <!-- فورم البحث المباشر للطلبات -->
        <form action="{{ route('admin.orders.index') }}" method="GET" class="d-flex gap-2 w-100 w-md-auto" style="max-width: 350px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search ID, customer name, email, phone..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap"><i class="bi bi-search"></i></button>
            @if(request('search'))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-danger btn-sm" title="Clear Search"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <!-- تنبيهات الأخطاء والنجاح -->
    @if ($errors->any())
        <div class="alert alert-danger m-3 mb-0 rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div><strong>تنبيه!</strong> برجاء مراجعة البيانات المدخلة وتصحيح الأخطاء أدناه.</div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger m-3 mb-0 rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-x-circle-fill fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success m-3 mb-0 rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Table: مختصر وواضح. باقي التفاصيل (العنوان، الملاحظات، تفصيل الأسعار...) في زرار Details -->
    <div class="table-responsive p-3">
        <table class="table table-modern align-middle mb-0" id="ordersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Customer pays</th>
                    <th>Profit</th>
                    <th>Status</th>
                    <th>Marketer</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold">{{ $order->customer_name }}</div>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $order->customer_phone }}</div>
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @if($order->emirate)<span class="badge badge-soft-primary fw-normal"><i class="bi bi-geo-alt me-1"></i>{{ $order->emirate }}</span>@endif
                                @if($order->customer_type === 'new')<span class="badge badge-soft-success fw-normal">New</span>
                                @elseif($order->customer_type === 'returning')<span class="badge badge-soft-warning fw-normal">Returning</span>@endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('shop.product.show', $order->product_id) }}" target="_blank" class="fw-semibold text-primary text-decoration-none" title="View Product Details">
                                {{ $order->product->name ?? '—' }} <i class="bi bi-box-arrow-up-right small ms-1 opacity-75"></i>
                            </a>
                            @if($order->variant)
                                <div><span class="badge bg-secondary fw-normal">{{ $order->variant->size ?? 'N/A' }}@if($order->variant->color) | {{ $order->variant->color }}@endif</span></div>
                            @endif
                        </td>
                        <td><span class="fw-bold">{{ $order->stock }}</span></td>
                        <td>
                            <div class="fw-bold">{{ $order->customer_total }} {{ config('shop.currency') }}</div>
                            <div class="small text-muted">incl. {{ number_format((float) $order->delivery_fee, 0) }} delivery</div>
                        </td>
                        <td>
                            <span class="badge badge-soft-success fw-bold">+{{ $order->profit }} {{ config('shop.currency') }}</span>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    'returned' => 'secondary',
                                ];
                                $color = $statusColors[$order->status] ?? 'secondary';
                            @endphp
                            <div>
                                <span class="badge bg-{{ $color }} text-capitalize">{{ $order->status }}</span>
                            </div>
                            
                            <!-- زرار صغير منظم لعرض/تعديل سبب الإلغاء أو الإرجاع بدون تخريب الجدول -->
                            @if(in_array($order->status, ['cancelled', 'returned']) && $order->cancellation_reason)
                                <div class="mt-1">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" 
                                            data-bs-toggle="modal" data-bs-target="#reasonModal{{ $order->id }}" style="font-size: 11px;">
                                        <i class="bi bi-info-circle me-1"></i> عرض / تعديل السبب
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $order->user->name ?? 'System' }}
                        </td>
                        <td class="small text-muted">
                            {{ $order->created_at->diffForHumans() }}
                        </td>
                        <td class="text-end text-nowrap">
                            <!-- كل التفاصيل في مودال -->
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#orderDetails{{ $order->id }}" title="Full order details">
                                <i class="bi bi-eye me-1"></i>Details
                            </button>

                            <!-- زرار تحديث الحالة -->
                            <button class="btn btn-icon btn-light" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editOrderStatusModal{{ $order->id }}" 
                                    aria-label="Update Status" title="Change Status">
                                <i class="bi bi-gear text-primary"></i>
                            </button>

                            <!-- زرار الحذف (للملغي/المرتجع بس، وهو حذف نهائي) -->
                            <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-light text-danger" aria-label="Delete" title="Delete Order">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($orders) && method_exists($orders, 'hasPages') && $orders->hasPages())
        <div class="p-3 d-flex justify-content-between align-items-center small text-muted border-top">
            <span>Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }}</span>
            <div>
                {{ $orders->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>

<!-- مودالات تفاصيل كل أوردر -->
@foreach($orders as $order)
    @include('partials.order-details-modal', ['order' => $order, 'admin' => true])
@endforeach

<!-- مودالات تعديل حالة الطلب لكل أوردر -->
@if(isset($orders))
    @foreach($orders as $order)
    <div class="modal fade" id="editOrderStatusModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
           <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            
            <div class="modal-header">
                <h5 class="modal-title">تحديث حالة الطلب #{{ $order->id }}</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-start">
                <p class="small text-muted mb-3 text-end">العميل: <strong>{{ $order->customer_name }}</strong></p>

                <!-- حماية واجهة المودال إذا وصل الطلب لحالة نهائية -->
                @if(in_array($order->status, ['delivered', 'cancelled', 'returned']))
                    <div class="alert alert-warning small text-end">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> تنبيه: هذا الطلب في حالة نهائية (<strong>{{ $order->status }}</strong>) ولا يمكن تعديل حالته لحماية السجل المالي والمحفظة.
                    </div>
                @else
                    <!-- اختيار الحالة (Select) -->
                    <div class="mb-3 text-end">
                        <label class="form-label small fw-semibold">حالة الطلب <span class="text-danger">*</span></label>
                        <select name="status" class="form-select js-status-select" required>
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>قيد الانتظار (Pending)</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>جاري التجهيز (Processing)</option>
                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>تم التوصيل (Delivered)</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>ملغي (Cancelled)</option>
                            <option value="returned" {{ $order->status == 'returned' ? 'selected' : '' }}>مرتجع (Returned)</option>
                        </select>
                    </div>

                    <!-- السبب: بيظهر بس لما تختار "ملغي" أو "مرتجع" (الـ JS تحت بيظهره ويخليه إجباري) -->
                    <div class="mb-3 text-end js-reason-box" style="display:none">
                        <label class="form-label small fw-semibold">السبب <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="اكتب السبب هنا..." maxlength="500">{{ $order->cancellation_reason }}</textarea>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                    <button type="submit" class="btn btn-primary btn-sm px-4">حفظ التغييرات</button>
                @endif
            </div>
        </form>
        </div>
    </div>
    @endforeach
@endif

<!-- مودالات عرض وتعديل سبب الإلغاء/الإرجاع المنفصلة -->
@if(isset($orders))
    @foreach($orders as $order)
        @if(in_array($order->status, ['cancelled', 'returned']))
        <div class="modal fade" id="reasonModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('admin.orders.updateReason', $order->id) }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header">
                        <h5 class="modal-title">سبب الـ {{ $order->status == 'cancelled' ? 'إلغاء' : 'إرجاع' }} للطلب #{{ $order->id }}</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body text-start">
                        <div class="mb-3 text-end">
                            <label class="form-label small fw-semibold">تعديل السبب <span class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" class="form-control" rows="4" required>{{ $order->cancellation_reason }}</textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">حفظ التعديل</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endforeach
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // السبب بيظهر ويبقى إجباري بس مع "ملغي" أو "مرتجع". غير كده بيتخفي ومبيتبعتش.
    document.querySelectorAll('.js-status-select').forEach(select => {
        const form = select.closest('form');
        const box = form.querySelector('.js-reason-box');
        const textarea = box.querySelector('textarea');
        const sync = () => {
            const needs = ['cancelled', 'returned'].includes(select.value);
            box.style.display = needs ? '' : 'none';
            textarea.required = needs;
            textarea.disabled = !needs;
        };
        select.addEventListener('change', sync);
        sync();
    });

    // تأكيد الحذف
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this order?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endpush