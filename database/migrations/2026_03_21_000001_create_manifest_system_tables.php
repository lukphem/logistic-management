<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Three-level structure, matching the design worked through in
     * conversation:
     *
     * manifest_trips = one physical vehicle journey — one vehicle, one
     * driver, one dispatch event. A trip can carry several manifests
     * inside it (a truck making multiple drops along its route).
     *
     * manifests = one destination batch within a trip — what actually
     * gets arrival-scanned at a hub. A hub can be "just a stop" for
     * the trip as a whole while still being the exact destination for
     * one specific manifest riding inside it. A manifest's own
     * destination is NOT required to be a shipment's final
     * destination_hub_id — it can be dropped at any intermediate hub
     * for a further manifest onward, same as a real linehaul network.
     *
     * manifest_shipments = the actual shipment-level record, one row
     * per (manifest, shipment) pair — a shipment can appear on many
     * manifests over its life, one per leg, so this is never a
     * direct FK on shipments itself. Carries the per-shipment
     * condition at receipt (received/damaged/missing/over), since
     * "arrived, but which of those" is exactly the audit detail asked
     * for — a manifest being "received" doesn't mean every shipment
     * on it arrived in good order.
     */
    public function up(): void
    {
        Schema::create('manifest_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique();
            $table->foreignId('origin_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('origin_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->enum('transport_mode', ['road', 'air', 'sea'])->default('road');
            $table->enum('carrier_type', ['company', 'third_party'])->default('company');
            $table->string('carrier_name')->nullable();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();
            $table->string('vehicle_identifier')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->foreignId('dispatched_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('manifest_trip_id')->constrained('manifest_trips')->cascadeOnDelete();
            $table->foreignId('destination_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('destination_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->enum('status', ['draft', 'dispatched', 'received'])->default('draft');
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('discrepancy_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('manifest_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained('manifests')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->enum('condition', ['pending', 'received', 'damaged', 'missing', 'over'])->default('pending');
            $table->text('condition_notes')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            // One shipment can't appear twice on the same manifest —
            // it CAN appear on many different manifests over its life
            // (one per leg), just never duplicated within one.
            $table->unique(['manifest_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifest_shipments');
        Schema::dropIfExists('manifests');
        Schema::dropIfExists('manifest_trips');
    }
};
