<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('initials', 4)->nullable()->unique()->after('email');
            $table->string('phone', 32)->nullable()->after('initials');
            $table->string('locale', 2)->default('ar')->after('phone');
            $table->decimal('sales_target', 14, 2)->default(0)->after('locale');
            $table->decimal('commission_pct', 5, 2)->default(0)->after('sales_target');
            $table->boolean('active')->default(true)->after('commission_pct');
            $table->boolean('gets_leads')->default(false)->after('active');
            $table->timestamp('last_lead_at')->nullable()->after('gets_leads');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['initials', 'phone', 'locale', 'sales_target', 'commission_pct', 'active', 'gets_leads', 'last_lead_at']);
        });
    }
};
