{{--
  مودال "تفاصيل الأوردر" (بيستخدمه الأدمن والتاجر).
  عشان الجدول يفضل صغير وواضح، كل التفاصيل الكتير (العنوان، الملاحظات، تفصيل الأسعار...) بتتعرض هنا.
  المتغيرات: $order (لازم يكون محمّل product و variant)، و $admin (true للأدمن: بيظهر اسم المسوق).
--}}
@php
    $cur = config('shop.currency');
    $statusColors = ['pending' => 'warning', 'processing' => 'info', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'secondary'];
    $statusColor = $statusColors[$order->status] ?? 'secondary';
    $admin = $admin ?? false;
@endphp
<div class="modal fade" id="orderDetails{{ $order->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order #{{ $order->id }}
          <span class="badge bg-{{ $statusColor }} ms-2 text-capitalize fs-6">{{ $order->status }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body order-details-body" style="overflow-wrap:anywhere;word-break:break-word">
        <div class="row g-4">
          <!-- العميل والتوصيل -->
          <div class="col-md-6">
            <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="bi bi-person-lines-fill me-1"></i>Customer &amp; delivery</h6>
            <dl class="row small mb-0">
              <dt class="col-5 text-muted">Name</dt><dd class="col-7 fw-semibold text-break">{{ $order->customer_name }}</dd>
              <dt class="col-5 text-muted">Phone</dt><dd class="col-7 text-break"><a href="tel:{{ $order->customer_phone }}" class="text-decoration-none">{{ $order->customer_phone }}</a></dd>
              <dt class="col-5 text-muted">Emirate</dt><dd class="col-7 text-break">@if($order->emirate)<span class="badge badge-soft-primary">{{ $order->emirate }}</span>@else — @endif</dd>
              <dt class="col-5 text-muted">Customer type</dt>
              <dd class="col-7 text-break">
                @if($order->customer_type === 'new')<span class="badge badge-soft-success">New customer</span>
                @elseif($order->customer_type === 'returning')<span class="badge badge-soft-warning">Returning customer</span>
                @else — @endif
              </dd>
              <dt class="col-5 text-muted">Address</dt><dd class="col-7 text-break" style="white-space: pre-line">{{ $order->customer_address }}</dd>
              <dt class="col-5 text-muted">Notes</dt><dd class="col-7 text-break" style="white-space: pre-line">{{ $order->notes ?: '—' }}</dd>
            </dl>
          </div>

          <!-- المنتج والأسعار -->
          <div class="col-md-6">
            <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="bi bi-receipt me-1"></i>Product &amp; prices</h6>
            <dl class="row small mb-0">
              <dt class="col-5 text-muted">Product</dt>
              <dd class="col-7 fw-semibold text-break">
                @if($order->product){{ $order->product->sku ? '['.$order->product->sku.'] ' : '' }}{{ $order->product->name }}@else Deleted product @endif
              </dd>
              <dt class="col-5 text-muted">Size / Color</dt>
              <dd class="col-7 text-break">@if($order->variant){{ $order->variant->size ?? 'N/A' }}@if($order->variant->color) / {{ $order->variant->color }}@endif @else No variant @endif</dd>
              <dt class="col-5 text-muted">Quantity</dt><dd class="col-7 text-break">{{ $order->stock }}</dd>
              <dt class="col-5 text-muted">Cost (per item)</dt><dd class="col-7 text-break">{{ $order->product_price }} {{ $cur }}</dd>
              <dt class="col-5 text-muted">Selling (per item)</dt><dd class="col-7 text-break">{{ $order->selling_price }} {{ $cur }}</dd>
              <dt class="col-5 text-muted">Products total</dt><dd class="col-7 text-break">{{ $order->items_total }} {{ $cur }}</dd>
              <dt class="col-5 text-muted">Delivery fee</dt><dd class="col-7 text-break">{{ number_format((float) $order->delivery_fee, 2, '.', '') }} {{ $cur }}@if((float) $order->delivery_fee <= 0) <span class="small text-muted">(charged once on the customer's other order)</span>@endif</dd>
            </dl>
            <div class="rounded-3 p-3 mt-3 bg-brand-soft">
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Customer pays (total)</span>
                <span class="h5 fw-bold mb-0" style="color:var(--navy)">{{ $order->customer_total }} {{ $cur }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2 small">
                <span class="text-muted">{{ $admin ? 'Marketer profit' : 'Your profit' }}</span>
                <span class="fw-bold text-success">+{{ $order->profit }} {{ $cur }}</span>
              </div>
            </div>
          </div>

          @if($admin)
          <div class="col-12">
            <h6 class="fw-bold text-uppercase small text-muted mb-2"><i class="bi bi-person-badge me-1"></i>Marketer</h6>
            <div class="small">{{ $order->user->name ?? 'System' }} @if($order->user?->phone)<span class="text-muted">· {{ $order->user->phone }}</span>@endif @if($order->user?->email)<span class="text-muted">· {{ $order->user->email }}</span>@endif</div>
          </div>
          @endif

          @if(in_array($order->status, ['cancelled', 'returned']) && $order->cancellation_reason)
          <div class="col-12">
            <div class="alert alert-danger small mb-0"><strong>Reason:</strong> <span style="white-space: pre-line">{{ $order->cancellation_reason }}</span></div>
          </div>
          @endif
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <span class="small text-muted">Created {{ $order->created_at->format('Y-m-d H:i') }} ({{ $order->created_at->diffForHumans() }})</span>
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
