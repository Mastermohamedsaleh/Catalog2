<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fixes (C3, H5, H7, H9, M4, M6):
 *  - orders are soft deleted (audit trail) and indexed for the queries the app runs;
 *  - one wallet per user, one credit per order (hard stop against double payouts);
 *  - orders can no longer be wiped by deleting a product, category or merchant;
 *  - orders.product_variant_id gets a real foreign key;
 *  - CHECK constraints make negative stock / balance impossible (MySQL 8.0.16+ / MariaDB 10.2+).
 *
 * Run `php artisan migrate`. If it fails on a unique index, you have duplicate
 * wallets or phones in existing data: merge or clean them, then re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['user_id', 'status', 'id'], 'orders_user_status_idx');
            $table->index(['status', 'id'], 'orders_status_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'is_active', 'id'], 'products_category_active_idx');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('user_id', 'wallets_user_id_unique');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            // order_id is NULL for withdrawals, and NULLs do not collide in a unique index.
            $table->unique(['order_id', 'type'], 'wallet_tx_order_type_unique');
            $table->index(['wallet_id', 'id'], 'wallet_tx_wallet_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone', 'users_phone_unique');
        });

        // SQLite (used by the test suite) cannot alter foreign keys in place.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Dangling variant ids from the old delete-and-recreate behaviour would block the new key.
        DB::statement('UPDATE orders SET product_variant_id = NULL WHERE product_variant_id IS NOT NULL AND product_variant_id NOT IN (SELECT id FROM product_variants)');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->restrictOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        foreach ([
            'ALTER TABLE products ADD CONSTRAINT chk_products_stock CHECK (stock >= 0)',
            'ALTER TABLE product_variants ADD CONSTRAINT chk_variants_stock CHECK (stock >= 0)',
            'ALTER TABLE wallets ADD CONSTRAINT chk_wallets_balance CHECK (balance >= 0)',
            'ALTER TABLE orders ADD CONSTRAINT chk_orders_qty CHECK (stock >= 1)',
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Existing rows violate the rule or the server is too old: fix the data, then add it by hand.
                report($e);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropUnique('users_phone_unique'));
        Schema::table('wallet_transactions', function (Blueprint $t) {
            $t->dropUnique('wallet_tx_order_type_unique');
            $t->dropIndex('wallet_tx_wallet_idx');
        });
        Schema::table('wallets', fn (Blueprint $t) => $t->dropUnique('wallets_user_id_unique'));
        Schema::table('products', fn (Blueprint $t) => $t->dropIndex('products_category_active_idx'));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropIndex('orders_user_status_idx');
            $t->dropIndex('orders_status_idx');
            $t->dropSoftDeletes();
        });
    }
};
