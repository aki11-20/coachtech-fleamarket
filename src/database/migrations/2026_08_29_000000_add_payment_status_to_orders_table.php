<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPaymentStatusToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('payment_type');
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('status');
            $table->timestamp('reserved_until')->nullable()->after('stripe_checkout_session_id');
            $table->timestamp('paid_at')->nullable()->after('reserved_until');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');

            // Keep an index available for the foreign key before removing the unique index.
            $table->index('item_id');
        });

        DB::table('orders')->update([
            'status' => 'paid',
            'paid_at' => DB::raw('created_at'),
            'reserved_until' => null,
            'cancelled_at' => null,
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('item_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['item_id']);
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn([
                'status',
                'stripe_checkout_session_id',
                'reserved_until',
                'paid_at',
                'cancelled_at',
            ]);
        });
    }
}
