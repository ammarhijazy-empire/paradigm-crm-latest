<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('from_email')->index();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->json('attachments')->nullable();   // [{name, path, mime, size}]
            $table->timestamp('received_at');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('match_method', 16)->nullable(); // reference, sender, manual
            $table->enum('status', ['new', 'unmatched', 'reading', 'read', 'failed', 'ignored'])->default('new')->index();
            $table->timestamps();
        });

        Schema::create('vendor_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('ref', 32)->unique();       // AH-2610-1-Q1
            $table->string('carrier')->nullable();
            $table->string('routing')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->decimal('freight', 14, 2)->default(0);
            $table->json('surcharges')->nullable();     // [{name, amount, currency}]
            $table->decimal('origin_charges', 14, 2)->default(0);
            $table->decimal('destination_charges', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedSmallInteger('transit_days')->nullable();
            $table->unsignedSmallInteger('free_days')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('etd')->nullable();
            $table->text('conditions')->nullable();
            $table->json('lane')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'rejected'])->default('confirmed');
            $table->enum('source', ['manual', 'ai'])->default('manual');
            $table->json('ai_confidence')->nullable();
            $table->foreignId('inbound_email_id')->nullable()->constrained('inbound_emails')->nullOnDelete();
            $table->boolean('is_chosen')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cost_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->enum('type', ['freight', 'origin', 'destination', 'customs', 'trucking', 'storage', 'insurance', 'other']);
            $table->string('description')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->char('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 14, 6)->default(1);  // per USD at the time
            $table->decimal('estimated', 14, 2)->default(0);
            $table->decimal('actual', 14, 2)->nullable();
            $table->boolean('extra_fee')->default(false);
            $table->date('vendor_due_date')->nullable();
            $table->decimal('vendor_paid', 14, 2)->default(0);
            $table->timestamp('vendor_paid_at')->nullable();
            $table->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cost_lines ADD CONSTRAINT cost_lines_positive CHECK (estimated >= 0 AND (actual IS NULL OR actual >= 0))');
        }

        Schema::create('cost_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'returned'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('section', 24)->default('freight'); // freight, origin, destination, clearance, trucking, insurance, other
            $table->string('description_en');
            $table->string('description_ar');
            $table->char('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2)->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('quote_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->unsignedTinyInteger('round');
            $table->decimal('our_price_usd', 14, 2);
            $table->decimal('counter_offer_usd', 14, 2)->nullable();
            $table->enum('outcome', ['pending', 'accepted', 'countered', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['file_id', 'round']);
        });

        Schema::create('reprice_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->decimal('target_usd', 14, 2)->nullable();
            $table->enum('status', ['open', 'answered', 'declined'])->default('open');
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();
        });

        Schema::create('file_pay_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->unique()->constrained('files')->cascadeOnDelete();
            $table->enum('method', ['cash_in_advance', 'against_documents', 'on_arrival', 'credit']);
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->enum('status', ['not_needed', 'pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['file_pay_terms', 'reprice_requests', 'quote_rounds', 'quote_lines', 'cost_reviews', 'cost_lines', 'vendor_quotes', 'inbound_emails'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
