<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\DeliveryFee;
use Illuminate\Console\Command;

/** أداة فحص: بتعرض آخر الأوردرات ومفتاح "التوصيلة" لكل واحد، عشان تعرف ليه الرسوم اتحسبت أو لأ. */
class DeliveryDebug extends Command
{
    protected $signature = 'delivery:debug {count=6}';
    protected $description = 'Show recent orders with their delivery-fee group key';

    public function handle(): int
    {
        $this->info('مدة التوصيلة الحالية (دقيقة): ' . DeliveryFee::windowMinutes() . '   | الوقت الآن: ' . now()->toDateTimeString());
        $rows = Order::latest('id')->take((int) $this->argument('count'))->get()->map(fn ($o) => [
            $o->id, $o->user_id, $o->status, $o->delivery_fee, $o->created_at?->toDateTimeString(),
            DeliveryFee::groupKey($o->customer_phone, $o->emirate, $o->customer_address),
        ])->all();
        $this->table(['id', 'merchant', 'status', 'fee', 'created_at', 'group key (phone|emirate|address)'], $rows);
        $this->line('لو مفتاحين مختلفين في حرف واحد = توصيلتين. لو نفس المفتاح والرسوم 30 تاني: يبقى الوقت بين الأوردرين أكبر من المدة، أو الأوردر الأول اتحوّل لحالة غير pending/processing.');

        return self::SUCCESS;
    }
}
