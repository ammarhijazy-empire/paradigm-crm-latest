<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->char('country', 2)->index();
            $table->enum('kind', ['sea', 'air', 'city']);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('currency', 3)->unique();
            $table->decimal('rate_per_usd', 14, 6);
            $table->boolean('locked')->default(false);
            $table->string('source')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ref_counters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();
        });

        Schema::create('lead_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_ids', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_table');
            $table->string('legacy_id');
            $table->string('model');
            $table->unsignedBigInteger('new_id');
            $table->timestamps();
            $table->unique(['legacy_table', 'legacy_id']);
        });
    }

    public function down(): void
    {
        foreach (['legacy_ids', 'lead_keys', 'ref_counters', 'exchange_rates', 'settings', 'ports'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
