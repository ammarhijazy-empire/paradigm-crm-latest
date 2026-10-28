<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->constrained('files')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('type', 32);   // commercial_invoice, packing_list, certificate_of_origin, bl, awb, msds, whatsapp_chat, voice_note, rfq_attachment, other
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 24)->index();       // whatsapp_rfq, vendor_email, doc_check
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->string('model')->nullable();
            $table->string('prompt_version', 16)->nullable();
            $table->json('input_ref')->nullable();
            $table->json('output')->nullable();
            $table->enum('status', ['queued', 'running', 'done', 'failed', 'accepted', 'rejected'])->default('queued')->index();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->decimal('cost_usd', 10, 4)->default(0);
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doc_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('ai_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->enum('severity', ['blocker', 'warning', 'info']);
            $table->string('field')->nullable();
            $table->text('message');
            $table->enum('status', ['open', 'fixed', 'dismissed'])->default('open')->index();
            $table->text('dismiss_reason')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->char('currency', 3);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('e_invoice_no', 64)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid', 14, 2)->default(0);
            $table->enum('status', ['draft', 'issued', 'partly_paid', 'paid', 'cancelled'])->default('issued')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['file_id', 'currency']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description_en');
            $table->string('description_ar');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->boolean('vat_applies')->default(true);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('paid_at');
            $table->string('method', 24)->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_paid_range CHECK (paid >= 0 AND paid <= total + 0.01)');
        }

        Schema::create('email_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->json('cc')->nullable();
            $table->string('subject');
            $table->longText('body_html');
            $table->json('attachments')->nullable();
            $table->string('kind', 32)->index();        // rfq_vendor, costing_sales, prealert, reminder, summary, lead_sla
            $table->nullableMorphs('related');
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->text('resolution')->nullable();
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('type', 16)->default('annual');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('perf_self', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('period', 7);  // 2026-10
            $table->json('answers');
            $table->timestamps();
            $table->unique(['user_id', 'period']);
        });

        Schema::create('perf_mgr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->char('period', 7);
            $table->unsignedTinyInteger('score');
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        foreach (['perf_mgr', 'perf_self', 'leaves', 'complaints', 'notifications', 'email_outbox', 'payments', 'invoice_lines', 'invoices', 'doc_checks', 'ai_runs', 'documents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
