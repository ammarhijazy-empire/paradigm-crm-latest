<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('number', 16)->unique();          // PC-26-047
            $table->string('ref', 24)->unique();             // AH-2610-6
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('sales_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ops_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('stage')->default(1);
            $table->enum('status', ['open', 'closed', 'lost'])->default('open')->index();
            $table->enum('direction', ['import', 'export'])->default('import');
            $table->string('service', 12);
            $table->json('addons')->nullable();
            $table->string('origin', 12)->nullable();
            $table->string('destination', 12)->nullable();
            $table->string('incoterm', 3)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('delivery_address')->nullable();
            $table->json('inq')->nullable();
            $table->json('checks')->nullable();
            $table->json('ship')->nullable();
            $table->string('bl_no', 40)->nullable()->index();
            $table->string('carrier')->nullable();
            $table->date('etd')->nullable();
            $table->date('atd')->nullable();
            $table->date('eta')->nullable();
            $table->string('acid_no', 32)->nullable();
            $table->string('pricing_status', 16)->default('draft')->index();
            $table->date('quote_etd')->nullable();
            $table->unsignedSmallInteger('quote_transit_days')->nullable();
            $table->string('quote_carrier')->nullable();
            $table->unsignedBigInteger('chosen_vendor_quote_id')->nullable();
            $table->foreignId('margin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('margin_approved_at')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->unsignedTinyInteger('lost_stage')->nullable();
            $table->string('lost_category', 24)->nullable();
            $table->text('lost_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['stage', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE files ADD CONSTRAINT files_stage_range CHECK (stage BETWEEN 1 AND 7)');
            DB::statement("ALTER TABLE files ADD CONSTRAINT files_lost_reason CHECK (status <> 'lost' OR lost_reason IS NOT NULL)");
        }

        Schema::create('file_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->unsignedTinyInteger('from_stage')->nullable();
            $table->unsignedTinyInteger('to_stage')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('file_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('location')->nullable();
            $table->timestamp('happened_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('file_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->json('mentions')->nullable();
            $table->timestamps();
        });

        Schema::create('file_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 24);       // prealert, eta_change, arrival, delivered, custom
            $table->string('channel', 16);    // email, whatsapp
            $table->text('body');
            $table->foreignId('email_outbox_id')->nullable();
            $table->timestamps();
        });

        Schema::create('file_booking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->unique()->constrained('files')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable();
            $table->string('booking_no', 40)->nullable();
            $table->string('vessel_voyage')->nullable();
            $table->timestamp('cutoff_at')->nullable();
            $table->string('cfs_warehouse')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['call', 'whatsapp', 'meeting', 'email', 'note']);
            $table->text('body');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activities', 'file_booking', 'file_updates', 'file_notes', 'file_events', 'file_history', 'files'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
