<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\OrderStatusService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the audit findings: stock, profit, wallet and access control.
 */
class OrderStockWalletTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'app_client_usr', bool $super = false): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'name' => "User {$n}", 'email' => "u{$n}@example.test", 'phone' => '0100000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'password' => 'password123', 'role' => $role, 'is_super' => $super,
        ]);
    }

    private function product(int $stock = 10, float $price = 100, bool $active = true): Product
    {
        $cat = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
        return Product::create([
            'category_id' => $cat->id, 'name' => 'P ' . uniqid(), 'sku' => 'S' . uniqid(), 'slug' => 'p-' . uniqid(),
            'price' => $price, 'stock' => $stock, 'is_active' => $active,
        ]);
    }

    private function orderPayload(Product $p, array $over = []): array
    {
        return array_merge([
            'product_id' => $p->id, 'stock' => 2, 'selling_price' => 150,
            'customer_name' => 'Customer', 'customer_phone' => '01012345678', 'customer_address' => '12 Test Street, Cairo',
        ], $over);
    }

    private function place(User $merchant, Product $p, array $over = [])
    {
        return $this->actingAs($merchant)->post(route('merchant.orders.store'), $this->orderPayload($p, $over));
    }

    // ---------- C1: stock for standalone products ----------

    public function test_standalone_order_deducts_exactly_the_quantity_and_cancel_restores_it(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);

        $this->place($merchant, $p)->assertSessionHasNoErrors();
        $this->assertSame(8, $p->fresh()->stock, 'stock must be 10 - 2, not reset to 0 by a model hook');

        $order = Order::firstOrFail();
        $this->assertEquals('100.00', $order->product_price);
        $this->assertEquals('100.00', $order->profit, '(150 - 100) * 2');

        $this->actingAs($merchant)->post(route('merchant.orders.cancel', $order))->assertSessionHasNoErrors();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_status_changes_never_touch_stock_of_a_standalone_product(): void
    {
        $p = $this->product(10);
        $this->place($this->user(), $p);
        $order = Order::firstOrFail();

        app(OrderStatusService::class)->change($order->id, 'processing');
        app(OrderStatusService::class)->change($order->id, 'shipped');
        $this->assertSame(8, $p->fresh()->stock);
    }

    // ---------- variants ----------

    public function test_variant_order_deducts_variant_and_parent_and_cancel_restores_both(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        $v1 = ProductVariant::create(['product_id' => $p->id, 'size' => 'M', 'stock' => 5]);
        $v2 = ProductVariant::create(['product_id' => $p->id, 'size' => 'L', 'stock' => 5]);

        $this->place($merchant, $p, ['product_variant_id' => $v1->id])->assertSessionHasNoErrors();
        $this->assertSame(3, $v1->fresh()->stock);
        $this->assertSame(5, $v2->fresh()->stock);
        $this->assertSame(8, $p->fresh()->stock);

        $order = Order::firstOrFail();
        app(OrderStatusService::class)->change($order->id, 'cancelled', 'test');
        $this->assertSame(5, $v1->fresh()->stock);
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_variant_is_required_when_product_has_variants_and_must_belong_to_product(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        ProductVariant::create(['product_id' => $p->id, 'size' => 'M', 'stock' => 5]);
        $other = ProductVariant::create(['product_id' => $this->product()->id, 'size' => 'XL', 'stock' => 5]);

        $this->place($merchant, $p)->assertSessionHasErrors('product_variant_id');
        $this->place($merchant, $p, ['product_variant_id' => $other->id])->assertSessionHasErrors('product_variant_id');
        $this->assertSame(0, Order::count());
        $this->assertSame(5, $other->fresh()->stock);
    }

    // ---------- no negative stock / validation ----------

    public function test_cannot_order_more_than_stock_and_stock_never_goes_negative(): void
    {
        $p = $this->product(1);
        $this->place($this->user(), $p, ['stock' => 2])->assertSessionHasErrors('stock');
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    public function test_second_order_for_the_last_unit_fails(): void
    {
        $p = $this->product(1);
        $this->place($this->user(), $p, ['stock' => 1])->assertSessionHasNoErrors();
        $this->place($this->user(), $p, ['stock' => 1])->assertSessionHasErrors('stock');
        $this->assertSame(0, $p->fresh()->stock);
        $this->assertSame(1, Order::count());
    }

    public function test_inactive_product_and_price_below_cost_are_rejected(): void
    {
        $merchant = $this->user();
        $inactive = $this->product(10, 100, false);
        $this->place($merchant, $inactive)->assertSessionHasErrors('product_id');

        $p = $this->product(10, 100);
        $this->place($merchant, $p, ['selling_price' => 99.99])->assertSessionHasErrors('selling_price');
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    public function test_profit_uses_exact_decimal_math(): void
    {
        $p = $this->product(10, 10.10);
        $this->place($this->user(), $p, ['stock' => 3, 'selling_price' => 10.35])->assertSessionHasNoErrors();
        $this->assertEquals('0.75', Order::firstOrFail()->profit); // (10.35 - 10.10) * 3, no float drift
    }

    public function test_double_submit_does_not_create_two_orders(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        $this->place($merchant, $p);
        $this->place($merchant, $p)->assertSessionHasErrors('error');
        $this->assertSame(1, Order::count());
        $this->assertSame(8, $p->fresh()->stock);
    }

    // ---------- C2 / H1: wallet credit and terminal states ----------

    private function deliveredOrder(User $merchant, Product $p): Order
    {
        $this->place($merchant, $p);
        $order = Order::latest('id')->firstOrFail();
        app(OrderStatusService::class)->change($order->id, 'shipped');
        app(OrderStatusService::class)->change($order->id, 'delivered');
        return $order->fresh();
    }

    public function test_delivery_credits_profit_exactly_once(): void
    {
        $merchant = $this->user();
        $order = $this->deliveredOrder($merchant, $this->product());

        $wallet = Wallet::where('user_id', $merchant->id)->firstOrFail();
        $this->assertEquals('100.00', $wallet->balance);
        $this->assertTrue($order->is_paid_to_wallet);
        $this->assertSame(1, WalletTransaction::where('order_id', $order->id)->where('type', 'credit')->count());

        // A second "delivered" (double click / second admin) must change nothing.
        try {
            app(OrderStatusService::class)->change($order->id, 'delivered');
            $this->fail('delivered is terminal');
        } catch (DomainException $e) {
            $this->addToAssertionCount(1);
        }
        $this->assertEquals('100.00', $wallet->fresh()->balance);
        $this->assertSame(1, WalletTransaction::count());
    }

    public function test_database_refuses_a_second_credit_for_the_same_order(): void
    {
        $merchant = $this->user();
        $order = $this->deliveredOrder($merchant, $this->product());
        $wallet = Wallet::where('user_id', $merchant->id)->firstOrFail();

        $this->expectException(QueryException::class);
        WalletTransaction::create(['wallet_id' => $wallet->id, 'order_id' => $order->id, 'type' => 'credit', 'amount' => 100, 'description' => 'dup']);
    }

    public function test_database_allows_only_one_wallet_per_user(): void
    {
        $u = $this->user();
        Wallet::create(['user_id' => $u->id, 'balance' => 0]);
        $this->expectException(QueryException::class);
        Wallet::create(['user_id' => $u->id, 'balance' => 0]);
    }

    public function test_terminal_orders_reject_every_change(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        $svc = app(OrderStatusService::class);

        foreach (['delivered', 'cancelled', 'returned'] as $terminal) {
            $this->place($merchant, $p, ['customer_phone' => '0101' . random_int(1000000, 9999999)]);
            $order = Order::latest('id')->firstOrFail();
            match ($terminal) {
                'delivered' => [$svc->change($order->id, 'shipped'), $svc->change($order->id, 'delivered')],
                'cancelled' => $svc->change($order->id, 'cancelled', 'r'),
                'returned'  => [$svc->change($order->id, 'shipped'), $svc->change($order->id, 'returned', 'r')],
            };

            foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'] as $to) {
                try {
                    $svc->change($order->id, $to, 'x');
                    $this->fail("{$terminal} -> {$to} must be rejected");
                } catch (DomainException $e) {
                    $this->addToAssertionCount(1);
                }
            }
            $this->assertSame($terminal, $order->fresh()->status);
        }
    }

    public function test_cancel_and_return_release_stock_exactly_once(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        $this->place($merchant, $p);
        $order = Order::firstOrFail();

        app(OrderStatusService::class)->change($order->id, 'cancelled', 'r');
        $this->assertSame(10, $p->fresh()->stock);

        try { app(OrderStatusService::class)->change($order->id, 'cancelled', 'again'); } catch (DomainException $e) {}
        $this->assertSame(10, $p->fresh()->stock, 'a second cancel must not restore stock again');
    }

    public function test_returned_order_cannot_be_delivered_or_paid(): void
    {
        $merchant = $this->user();
        $this->place($merchant, $this->product());
        $order = Order::firstOrFail();
        app(OrderStatusService::class)->change($order->id, 'shipped');
        app(OrderStatusService::class)->change($order->id, 'returned', 'r');

        $this->expectException(DomainException::class);
        try {
            app(OrderStatusService::class)->change($order->id, 'delivered');
        } finally {
            $this->assertSame(0, WalletTransaction::count());
        }
    }

    public function test_merchant_cannot_cancel_after_admin_moved_the_order_or_cancel_someone_elses(): void
    {
        $merchant = $this->user();
        $p = $this->product(10);
        $this->place($merchant, $p);
        $order = Order::firstOrFail();

        $this->actingAs($this->user())->post(route('merchant.orders.cancel', $order))->assertForbidden();

        app(OrderStatusService::class)->change($order->id, 'processing');
        $this->actingAs($merchant)->post(route('merchant.orders.cancel', $order))->assertSessionHasErrors('error');
        $this->assertSame(8, $p->fresh()->stock);
    }

    // ---------- withdrawals ----------

    public function test_withdraw_pays_out_once_and_logs_a_debit(): void
    {
        $merchant = $this->user();
        $this->deliveredOrder($merchant, $this->product());

        $this->actingAs($merchant)->post(route('merchant.wallet.withdraw'), ['phone' => '01012345678'])->assertSessionHasNoErrors();
        $wallet = Wallet::where('user_id', $merchant->id)->firstOrFail();
        $this->assertEquals('0.00', $wallet->balance);
        $this->assertSame(1, WalletTransaction::where('type', 'debit')->count());

        $this->actingAs($merchant)->post(route('merchant.wallet.withdraw'), ['phone' => '01012345678'])->assertSessionHasErrors('error');
        $this->assertSame(1, WalletTransaction::where('type', 'debit')->count());
    }

    // ---------- C3: financial history is protected ----------

    public function test_product_category_and_merchant_with_orders_cannot_be_deleted(): void
    {
        $admin = $this->user('catalog_master_root');
        $merchant = $this->user();
        $p = $this->product();
        $this->place($merchant, $p);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $p))->assertSessionHas('error');
        $this->assertNotNull(Product::find($p->id));

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $p->category))->assertSessionHas('error');
        $this->assertNotNull(Category::find($p->category_id));

        $this->actingAs($admin)->delete(route('admin.customers.destroy', $merchant))->assertSessionHas('error');
        $this->assertNotNull(User::find($merchant->id));
        $this->assertSame(1, Order::count());
    }

    public function test_only_cancelled_or_returned_orders_can_be_deleted_and_they_are_soft_deleted(): void
    {
        $admin = $this->user('catalog_master_root');
        $this->place($this->user(), $this->product());
        $order = Order::firstOrFail();

        $this->actingAs($admin)->delete(route('admin.orders.destroy', $order))->assertSessionHas('error');
        $this->assertNotNull(Order::find($order->id));

        app(OrderStatusService::class)->change($order->id, 'cancelled', 'r');
        $this->actingAs($admin)->delete(route('admin.orders.destroy', $order))->assertSessionHas('success');
        $this->assertNull(Order::find($order->id));
        $this->assertNotNull(Order::withTrashed()->find($order->id));
    }

    // ---------- C4 / C5: access control ----------

    public function test_normal_admin_cannot_reset_zero_or_delete_a_super_admin(): void
    {
        $admin = $this->user('catalog_master_root');
        $super = $this->user('catalog_master_root', true);
        $hash = $super->password;

        $this->actingAs($admin)->post(route('admin.customers.resetPassword', $super))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.customers.zeroWallet', $super))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.customers.destroy', $super))->assertForbidden();
        $this->assertSame($hash, $super->fresh()->password);
    }

    public function test_reset_password_message_is_escaped_in_the_page(): void
    {
        $this->withoutVite();
        $admin = $this->user('catalog_master_root');
        $merchant = $this->user();
        $merchant->forceFill(['name' => '<script>alert(1)</script>'])->save();

        $this->actingAs($admin)->post(route('admin.customers.resetPassword', $merchant))->assertSessionHas('success');
        $html = $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_registration_saves_the_phone_number(): void
    {
        $this->post('/register', [
            'name' => 'New Merchant', 'email' => 'new@example.test', 'phone' => '01099998888',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $this->assertSame('01099998888', User::where('email', 'new@example.test')->value('phone'));
    }
}
