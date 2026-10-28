<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_person');
            $table->string('phone', 40);
            $table->string('phone_normalized', 20)->index();
            $table->string('email')->index();
            $table->string('sector', 40);
            $table->string('sector_other')->nullable();
            $table->string('products')->nullable();
            $table->string('current_forwarder')->nullable();
            $table->string('source', 40)->nullable();
            $table->enum('status', ['lead', 'contacted', 'active', 'repeat', 'lost'])->default('lead')->index();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->date('next_follow_up')->nullable()->index();
            $table->date('next_order_date')->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->string('tax_id', 40)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['agent', 'line', 'air', 'cfs', 'broker', 'truck', 'courier', 'insurance', 'other']);
            $table->char('country', 2)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_person')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 40)->default('website');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('sla_due_at');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('sla_alerted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('clients');
    }
};
