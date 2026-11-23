<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تفاصيل التوصيل في الأوردر:
 *  - emirate: الإمارة اللي الزبون قاعد فيها (اسمها بالإنجليزي من config/shop.php). بدون جدول منفصل: سبع إمارات ثابتة.
 *  - customer_type: new / returning (زبون جديد ولا قديم).
 *  - notes: ملاحظات التاجر على الأوردر.
 *  - delivery_fee: رسوم التوصيل وقت الأوردر (30 درهم دلوقتي). بتتخزن مع كل أوردر،
 *    فلو غيّرت الرسوم بعدين الأوردرات القديمة ما يتغيرش إجماليها.
 *
 * كل الأعمدة nullable / default عشان الأوردرات القديمة تفضل شغالة (رسومها 0، وإمارتها فاضية).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('emirate', 40)->nullable()->after('customer_address');
            $table->string('customer_type', 12)->nullable()->after('emirate');
            $table->text('notes')->nullable()->after('customer_type');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['emirate', 'customer_type', 'notes', 'delivery_fee']);
        });
    }
};
