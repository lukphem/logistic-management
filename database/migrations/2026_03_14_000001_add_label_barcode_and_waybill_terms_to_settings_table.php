<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * label_barcode_type is separate from waybill_show_qr — the label
     * (the small sticker on the package) can show either a QR code or
     * a traditional 1D barcode depending on what a company's existing
     * handheld scanners actually read; QR is the more common default
     * for anything scanned by a phone camera, 1D barcode for
     * dedicated warehouse scanner hardware that only reads Code128.
     *
     * waybill_terms is the actual legal/contractual text a company
     * wants printed on the Waybill document — their own liability
     * clause, claims process, prohibited-items notice, whatever their
     * business has settled on. Left nullable and unstyled (plain
     * text, rendered as paragraphs) rather than forcing any particular
     * legal wording — that's a business/legal decision this system
     * has no business making for anyone.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('label_barcode_type', ['qr', 'barcode'])->default('qr')->after('label_design');
            $table->text('waybill_terms')->nullable()->after('invoice_footer');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['label_barcode_type', 'waybill_terms']);
        });
    }
};
