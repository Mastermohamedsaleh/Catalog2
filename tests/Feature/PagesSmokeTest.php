<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_render(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@t.test', 'phone' => '01000000001', 'password' => 'password123', 'role' => 'catalog_master_root']);
        $merchant = User::create(['name' => 'M', 'email' => 'm@t.test', 'phone' => '01000000002', 'password' => 'password123', 'role' => 'app_client_usr']);
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'is_active' => true]);
        $leaf = Category::create(['name' => 'Leaf', 'slug' => 'leaf', 'parent_id' => $root->id, 'is_active' => true]);
        $p = Product::create(['category_id' => $leaf->id, 'name' => 'P', 'sku' => 'SK1', 'slug' => 'p', 'price' => 50, 'stock' => 5, 'is_active' => true]);
        ProductVariant::create(['product_id' => $p->id, 'size' => 'M', 'stock' => 5]);

        $this->get('/')->assertOk();
        foreach (['/category/root', '/category/leaf', '/product/' . $p->id, '/categories'] as $url) {
            $r = $this->actingAs($merchant)->get($url);
            $this->assertContains($r->status(), [200], "$url => " . $r->status());
        }
        foreach (['admin.dashboard', 'admin.categories.index', 'admin.products.index', 'admin.orders.index', 'admin.customers.index'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
        $super = User::create(['name' => 'S', 'email' => 's@t.test', 'phone' => '01000000003', 'password' => 'password123', 'role' => 'catalog_master_root', 'is_super' => true]);
        $this->actingAs($super)->get(route('admin.admins.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.admins.index'))->assertRedirect();
        foreach (['merchant.orders.index', 'merchant.wallet'] as $name) {
            $this->actingAs($merchant)->get(route($name))->assertOk();
        }
        $this->actingAs($merchant)->getJson(route('merchant.products.search', ['q' => 'P']))->assertOk();
    }
}
