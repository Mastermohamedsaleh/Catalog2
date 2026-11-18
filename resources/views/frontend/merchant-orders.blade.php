<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New order · CatalogPro Merchant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<div id="toastTop" aria-live="polite"></div>

<!-- ============ Sidebar ============ -->
<aside class="sidebar" id="sidebar">
  <a href="{{ route('merchant.orders.index') }}" class="brand"><i class="bi bi-grid-fill me-2"></i>CatalogPro</a>
  <nav class="nav flex-column flex-grow-1 overflow-auto">
    <div class="nav-label">Merchant</div>
    <a class="nav-link active" href="{{ route('merchant.orders.index') }}"><i class="bi bi-cart-plus"></i>New order</a>
    <a class="nav-link" href="{{ route('merchant.wallet') }}"><i class="bi bi-wallet2"></i>My wallet</a>
    <a class="nav-link" href="{{ route('merchant.profile') }}"><i class="bi bi-person-circle"></i>My profile</a>
    <div class="nav-label">Site</div>
    <a class="nav-link" href="{{ url('/') }}" target="_blank"><i class="bi bi-globe2"></i>View catalog</a>
  </nav>
  <div class="p-3 border-top border-secondary-subtle">
      <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="nav-link m-0 bg-transparent border-0 text-danger w-100 text-start">
              <i class="bi bi-box-arrow-left me-2"></i>Log out
          </button>
      </form>
  </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-toggle></div>

<div class="main">
  <!-- ============ Topbar ============ -->
  <header class="topbar d-flex align-items-center gap-3 px-3 px-lg-4 py-2">
    <button class="btn btn-light d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
    <h1 class="h5 fw-bold mb-0 me-auto">New order</h1>
    <a href="{{ route('merchant.wallet') }}" class="btn btn-light btn-sm d-flex align-items-center gap-2" title="Wallet balance">
      <i class="bi bi-wallet2 text-primary"></i><span class="fw-semibold">{{ \App\Models\Order::where('user_id', Auth::id())->where('status', 'delivered')->sum('profit') }} {{ config('shop.currency') }}</span>
    </a>
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center gap-2 text-dark" data-bs-toggle="dropdown">
        <span class="thumb g3" style="width:36px;height:36px;border-radius:50%">{{ substr(Auth::user()->name, 0, 1) }}</span>
        <span class="d-none d-sm-block small fw-semibold">{{ Auth::user()->name }}</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
        <li>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left me-2"></i>Log out</button>
            </form>
        </li>
      </ul>
    </div>
  </header>

  <main class="p-3 p-lg-4 flex-grow-1">
    <!-- الأخطاء والرسائل -->
    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success mb-4 small">{{ session('success') }}</div>
    @endif

    <!-- ============ ORDER FORM ============ -->
    <form action="{{ route('merchant.orders.store') }}" method="POST" id="orderForm" class="card-soft p-3 p-lg-4 mb-4">
      @csrf
      <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-person-lines-fill text-primary fs-5"></i><h2 class="h5 fw-bold mb-0">Customer &amp; order details</h2></div>
      <p class="text-muted small mb-4">Fill in your customer's data. The order is sent to the admin for confirmation.</p>

      <div class="row g-3">
        <!-- البحث عن المنتج -->
        <div class="col-md-6 position-relative">
          <label for="productSearch" class="form-label small fw-semibold">Search Product (Name or Code) <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="productSearch" placeholder="Type product name or Code..." autocomplete="off" required>
          <input type="hidden" id="productId" name="product_id">
          <div id="searchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 220px; overflow-y: auto; display: none;"></div>
          <div class="invalid-feedback">Please choose a valid product from the list.</div>
        </div>

        <!-- اختيار المقاس -->
        <div class="col-md-6" id="variantContainer" style="display: none;">
          <label for="variantSelect" class="form-label small fw-semibold">Select Size / Variant <span class="text-danger">*</span></label>
          <select class="form-select" id="variantSelect" name="product_variant_id">
              <option value="">Choose size first...</option>
          </select>
        </div>

        <!-- الكمية -->
        <div class="col-6 col-md-3">
          <label for="qty" class="form-label small fw-semibold">Quantity <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="qty" name="stock" min="1" step="1" value="1" required>
          <div class="form-text" id="stockHint">Select a product first</div>
          <div class="invalid-feedback">Enter a valid quantity within available stock.</div>
        </div>

        <!-- سعر المنتج الأساسي -->
        <div class="col-6 col-md-3">
          <label for="productPrice" class="form-label small fw-semibold">Product price <span class="text-muted fw-normal">(cost)</span></label>
          <div class="input-group"><input type="number" class="form-control bg-light" id="productPrice" name="product_price" readonly tabindex="-1" placeholder="0"><span class="input-group-text">{{ config('shop.currency') }}</span></div>
          <!-- تفصيل التكلفة: السعر الأساسي + فرق المقاس = التكلفة النهائية اللي لازم البيع ما ينزلش عنها -->
          <div class="form-text" id="costBreakdown"></div>
        </div>

        <!-- سعر البيع للعميل -->
        <div class="col-md-4">
          <label for="sellingPrice" class="form-label small fw-semibold">Selling price <span class="text-muted fw-normal">(per item)</span> <span class="text-danger">*</span></label>
          <div class="input-group"><input type="number" class="form-control" id="sellingPrice" name="selling_price" min="0" step="0.01" required placeholder="0.00"><span class="input-group-text">{{ config('shop.currency') }}</span></div>
          <div class="form-text fw-semibold" id="minSellHint"></div>
          <div class="invalid-feedback">Selling price must be at least the product price.</div>
        </div>

        <!-- الربح المتوقع -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Your profit <span class="text-muted fw-normal">(auto)</span></label>
          <div class="border rounded-3 px-3 py-2 bg-light d-flex align-items-center justify-content-between" style="min-height:44px">
            <span id="profitText" class="h4 fw-bold mb-0">—</span><i class="bi bi-graph-up-arrow text-success"></i>
          </div>
          <input type="hidden" id="profit" name="profit">
        </div>

        <!-- الحالة -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Status</label>
          <div class="border rounded-3 px-3 py-2 bg-light d-flex align-items-center gap-2" style="min-height:44px"><span class="badge bg-warning text-dark">Pending</span><span class="small text-muted">Set by admin</span></div>
          <input type="hidden" name="status" value="pending">
          <input type="hidden" name="is_paid_to_wallet" value="0">
        </div>

        <!-- بيانات العميل -->
        <div class="col-md-6">
          <label for="customerName" class="form-label small fw-semibold">Customer name <span class="text-danger">*</span></label>
          <div class="input-icon"><i class="bi bi-person"></i><input type="text" class="form-control" id="customerName" name="customer_name" required minlength="3" placeholder="Full name"><div class="invalid-feedback">Enter customer name.</div></div>
        </div>
        <div class="col-md-6">
          <label for="customerPhone" class="form-label small fw-semibold">Customer phone <span class="text-danger">*</span></label>
          <div class="input-icon"><i class="bi bi-telephone"></i><input type="tel" class="form-control" id="customerPhone" name="customer_phone" required placeholder="05XXXXXXXX"><div class="invalid-feedback">Enter valid phone number.</div></div>
        </div>
        <div class="col-12">
          <label for="customerAddress" class="form-label small fw-semibold">Customer address <span class="text-danger">*</span></label>
          <textarea class="form-control" id="customerAddress" name="customer_address" rows="2" required placeholder="Governorate, city, street, building…"></textarea>
          <div class="invalid-feedback">Enter delivery address.</div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
          <button type="reset" class="btn btn-light">Clear</button>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send me-2"></i>Send order to admin</button>
        </div>
      </div>
    </form>

    <!-- ============ MY ORDERS TABLE ============ -->
    <div class="card-soft">
      <div class="p-3 p-lg-4 pb-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0">My orders <span class="badge bg-primary ms-1" id="ordersCount">{{ isset($orders) && method_exists($orders, 'total') ? $orders->total() : $orders->count() }}</span></h2>
        <div class="input-icon" style="min-width:240px"><i class="bi bi-search"></i><input type="search" class="form-control form-control-sm py-2" placeholder="Search orders…" data-filter="#ordersTable"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-modern" id="ordersTable">
          <thead>
              <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Customer</th>
                  <th class="d-none d-xxl-table-cell">Address</th>
                  <th>Cost</th>
                  <th>Selling</th>
                  <th>Qty</th>
                  <th>Size / Color</th>
                  <th>Profit</th>
                  <th>Status</th>
                  <th>Wallet</th>
              </tr>
          </thead>
          <tbody>
              @forelse($orders as $order)
                  <tr>
                      <td>{{ isset($orders->firstItem) ? $orders->firstItem() + $loop->index : $loop->iteration }}</td>
                      <td class="fw-semibold">
                          @if($order->product)
                              @if($order->product->sku)
                                  <span class="text-muted small">[{{ $order->product->sku }}]</span>
                              @endif
                              {{ $order->product->name }}
                          @else
                              Deleted Product
                          @endif
                      </td>
                      <td>{{ $order->customer_name }}<br><small class="text-muted">{{ $order->customer_phone }}</small></td>
                      <td class="d-none d-xxl-table-cell small text-muted">{{ $order->customer_address }}</td>
                      <td>{{ $order->product_price }} {{ config('shop.currency') }}</td>
                      <td>{{ $order->selling_price }} {{ config('shop.currency') }}</td>
                      <td>{{ $order->stock }}</td>
                      <td>
                        @if($order->variant)
                            <div class="mt-1">
                                <span class="badge bg-secondary">
                                    Size: {{ $order->variant->size ?? 'N/A' }} 
                                    @if($order->variant->color)
                                        | Color: {{ $order->variant->color }}
                                    @endif
                                </span>
                            </div>
                        @else
                            <span class="text-muted small">No variant</span>
                        @endif
                      </td>
                      <td class="fw-bold text-success">+{{ $order->profit }} {{ config('shop.currency') }}</td>
                      <td>
                          @if($order->status == 'pending') 
                              <div class="d-flex align-items-center gap-2">
                                  <span class="badge bg-warning text-dark">Pending</span>
                                  <form action="{{ route('merchant.orders.cancel', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الأوردر وإعادة المخزون؟');">
                                      @csrf
                                      <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-0" style="font-size: 11px; border-radius: 6px; line-height: 1.5;">
                                          <i class="bi bi-x-circle me-1"></i>Cancel
                                      </button>
                                  </form>
                              </div>
                          @elseif($order->status == 'delivered') 
                              <span class="badge bg-success">Delivered</span>
                          @elseif($order->status == 'cancelled') 
                              <div>
                                  <span class="badge bg-danger">Cancelled</span>
                              </div>
                              @if($order->cancellation_reason)
                                  <div class="mt-1">
                                      <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" 
                                              data-bs-toggle="modal" data-bs-target="#merchantReasonModal{{ $order->id }}" style="font-size: 11px;">
                                          <i class="bi bi-info-circle me-1"></i> View Reason
                                      </button>
                                  </div>
                              @endif
                          @elseif($order->status == 'returned') 
                              <div>
                                  <span class="badge bg-secondary">Returned</span>
                              </div>
                              @if($order->cancellation_reason)
                                  <div class="mt-1">
                                      <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" 
                                              data-bs-toggle="modal" data-bs-target="#merchantReasonModal{{ $order->id }}" style="font-size: 11px;">
                                          <i class="bi bi-info-circle me-1"></i> View Reason
                                      </button>
                                  </div>
                              @endif
                          @else 
                              <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                          @endif
                      </td>
                      <td>
                          @if($order->is_paid_to_wallet)
                              <i class="bi bi-check-circle-fill text-success"> Paid</i>
                          @else
                              <span class="text-muted small">Pending</span>
                          @endif
                      </td>
                  </tr>
              @empty
                  <tr>
                      <td colspan="11" class="text-center text-muted py-4">No orders placed yet.</td>
                  </tr>
              @endforelse
          </tbody>
      </table>
      </div>

      <!-- Pagination Links (آمنة مع الـ Collection والـ Paginator) -->
      @if(isset($orders) && method_exists($orders, 'hasPages') && $orders->hasPages())
          <div class="p-3 d-flex justify-content-between align-items-center small text-muted border-top">
              <span>Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }}</span>
              <div>
                  {{ $orders->appends(request()->query())->links() }}
              </div>
          </div>
      @endif
    </div>
  </main>
</div>

<!-- ============ مودالات عرض سبب الإلغاء/الإرجاع للتاجر (Read-Only) ============ -->
@if(isset($orders))
    @foreach($orders as $order)
        @if(in_array($order->status, ['cancelled', 'returned']) && $order->cancellation_reason)
        <div class="modal fade" id="merchantReasonModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
<h5 class="modal-title">سبب الـ {{ $order->status == 'cancelled' ? 'إلغاء' : 'إرجاع' }} للطلب #{{ $order->id }}</h5>                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Detailed Reason:</label>
                            <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-line; line-height: 1.6;">
                                {{ $order->cancellation_reason }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endif

<!-- ============ Scripts ============ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>

<script>
    const productSearchUrl = @json(route('merchant.products.search'));
    const CURRENCY = @json(config('shop.currency'));
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('productSearch');
    const resultsBox = document.getElementById('searchResults');
    const productIdInput = document.getElementById('productId');
    const productPriceInput = document.getElementById('productPrice');
    const qtyInput = document.getElementById('qty');
    const sellingPriceInput = document.getElementById('sellingPrice');
    const profitText = document.getElementById('profitText');
    const profitInput = document.getElementById('profit');
    const stockHint = document.getElementById('stockHint');
    const variantContainer = document.getElementById('variantContainer');
    const variantSelect = document.getElementById('variantSelect');
    const orderForm = document.getElementById('orderForm');

    const costBreakdown = document.getElementById('costBreakdown');
    const minSellHint = document.getElementById('minSellHint');
    const toCents = n => Math.round((parseFloat(n) || 0) * 100);
    const money = cents => (cents / 100).toFixed(2);

    // التكلفة الحقيقية = سعر المنتج الأساسي + فرق سعر المقاس المختار (نفس معادلة السيرفر بالظبط).
    // لو التاجر شاف السعر الأساسي بس هيفتكر إنه الحد الأدنى للبيع، فبنوريه التفصيل والحد الأدنى صراحةً.
    function setCost(baseCents, modifierCents) {
        const cost = baseCents + modifierCents;
        productPriceInput.value = cost > 0 ? money(cost) : '';
        sellingPriceInput.min = cost > 0 ? money(cost) : '0';

        if (!baseCents) {
            costBreakdown.textContent = '';
            minSellHint.textContent = '';
        } else {
            costBreakdown.textContent = modifierCents !== 0
                ? `Base ${money(baseCents)} ${modifierCents > 0 ? '+' : '−'} size difference ${money(Math.abs(modifierCents))} = ${money(cost)} ${CURRENCY}`
                : `Base price ${money(baseCents)} ${CURRENCY}`;
            minSellHint.textContent = `Minimum selling price: ${money(cost)} ${CURRENCY}`;
        }
        calculateProfit();
    }
    let currentBaseCents = 0;

    // Server-side search (debounced) instead of shipping the whole catalog to every page view.
    function renderResults(list) {
        resultsBox.innerHTML = '';
        if (!list.length) { resultsBox.style.display = 'none'; return; }
        resultsBox.style.display = 'block';
        list.forEach(product => {
            let item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action small';

            // textContent (not innerHTML): product names are data, never markup
            let strong = document.createElement('strong');
            strong.textContent = `${product.sku ? '[' + product.sku + '] ' : ''}${product.name}`;
            let price = document.createElement('span');
            price.className = 'text-primary';
            const sizesDiffer = (product.variants || []).some(v => toCents(v.price_modifier) !== 0);
            price.textContent = `${product.price} ${CURRENCY}` + (sizesDiffer ? ' (+ size difference)' : '');
            item.append(strong, ' — ', price, ` (Stock: ${product.stock})`);

            item.addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = `${product.sku ? product.sku + ' - ' : ''}${product.name}`;
                productIdInput.value = product.id;
                currentBaseCents = toCents(product.price);
                setCost(currentBaseCents, 0);

                variantSelect.innerHTML = '<option value="">Choose size/color...</option>';
                if (product.variants && product.variants.length > 0) {
                    variantContainer.style.display = 'block';
                    variantSelect.setAttribute('required', 'required');
                    product.variants.forEach(variant => {
                        let opt = document.createElement('option');
                        opt.value = variant.id;
                        const finalCents = toCents(product.price) + toCents(variant.price_modifier);
                        opt.textContent = `${variant.size ?? ''} ${variant.color ? '(' + variant.color + ')' : ''} — ${money(finalCents)} ${CURRENCY} — Stock: ${variant.stock}`;
                        opt.setAttribute('data-stock', variant.stock);
                        opt.setAttribute('data-modifier', variant.price_modifier ?? 0);
                        variantSelect.appendChild(opt);
                    });
                    if (stockHint) stockHint.textContent = `Select a variant size`;
                } else {
                    variantContainer.style.display = 'none';
                    variantSelect.removeAttribute('required');
                    variantSelect.value = '';
                    qtyInput.max = product.stock;
                    if (stockHint) stockHint.textContent = `Available stock: ${product.stock}`;
                }

                resultsBox.style.display = 'none';
                calculateProfit();
            });
            resultsBox.appendChild(item);
        });
    }

    if (searchInput) {
        let searchTimer = null;
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(searchTimer);
            if (query.length < 1) { resultsBox.style.display = 'none'; return; }
            searchTimer = setTimeout(() => {
                fetch(`${productSearchUrl}?q=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : [])
                    .then(renderResults)
                    .catch(() => { resultsBox.style.display = 'none'; });
            }, 250);
        });
    }

    document.addEventListener('click', function(e) {
        if (searchInput && resultsBox && !searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });

    if (variantSelect) {
        variantSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const stock = parseInt(selectedOpt.getAttribute('data-stock')) || 0;
            qtyInput.max = stock;
            if(stockHint) stockHint.textContent = `Available stock for size: ${stock}`;
            setCost(currentBaseCents, toCents(selectedOpt.getAttribute('data-modifier')));
        });
    }

    if (qtyInput || sellingPriceInput) {
        [qtyInput, sellingPriceInput].forEach(input => {
            if (input) input.addEventListener('input', calculateProfit);
        });
    }

    function calculateProfit() {
        const price = parseFloat(productPriceInput.value) || 0;
        const qty = parseInt(qtyInput.value) || 1;
        const selling = parseFloat(sellingPriceInput.value) || 0;

        const totalCost = price * qty;
        const totalSelling = selling * qty;
        const totalProfit = totalSelling - totalCost;

        if (selling > 0 && totalProfit >= 0) {
            profitText.textContent = totalProfit.toFixed(2) + ' ' + CURRENCY;
            profitInput.value = totalProfit.toFixed(2);
        } else {
            profitText.textContent = '—';
            profitInput.value = '0';
        }
    }

    if (orderForm) {
        orderForm.addEventListener('submit', function (e) {
            const prodId = productIdInput.value;
            if (!prodId) {
                e.preventDefault();
                alert('من فضلك اختر منتجاً صالحاً من قائمة البحث أولاً!');
                searchInput.focus();
                return false;
            }
            // منع البيع بأقل من التكلفة (السيرفر بيرفضه برضه، بس كده التاجر يعرف السبب قبل الإرسال)
            if (toCents(sellingPriceInput.value) < toCents(productPriceInput.value)) {
                e.preventDefault();
                sellingPriceInput.classList.add('is-invalid');
                sellingPriceInput.focus();
                return false;
            }
        });
        orderForm.addEventListener('reset', function () {
            currentBaseCents = 0;
            setCost(0, 0);
            sellingPriceInput.classList.remove('is-invalid');
        });
        sellingPriceInput.addEventListener('input', () => sellingPriceInput.classList.remove('is-invalid'));
    }
});
</script>
</body>
</html>