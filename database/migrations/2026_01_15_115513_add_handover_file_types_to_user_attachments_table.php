<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE user_attachments MODIFY COLUMN type ENUM('soa', 'contract', 'id', 'passport', 'emirates_id', 'visa', 'receipt', 'other', 'payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc', 'handover_checklist', 'handover_declaration', 'handover_photo', 'client_signature') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE user_attachments MODIFY COLUMN type ENUM('soa', 'contract', 'id', 'passport', 'emirates_id', 'visa', 'receipt', 'other', 'payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc') NOT NULL");
    }
};
