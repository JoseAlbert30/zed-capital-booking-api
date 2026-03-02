<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_thirdparties', function (Blueprint $table) {
            $table->id();
            $table->string('thirdparty_number')->unique();
            $table->string('project_name');
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_number');
            $table->string('thirdparty_name');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Initial form document
            $table->string('form_document_path')->nullable();
            $table->string('form_document_name')->nullable();
            $table->timestamp('form_uploaded_at')->nullable();
            
            // Buyer notification
            $table->boolean('sent_to_buyer')->default(false);
            $table->timestamp('sent_to_buyer_at')->nullable();
            $table->string('sent_to_buyer_email')->nullable();
            
            // Signed document from buyer
            $table->string('signed_document_path')->nullable();
            $table->string('signed_document_name')->nullable();
            $table->timestamp('signed_document_uploaded_at')->nullable();
            
            // Developer notification
            $table->boolean('sent_to_developer')->default(false);
            $table->timestamp('sent_to_developer_at')->nullable();
            $table->boolean('viewed_by_developer')->default(false);
            $table->timestamp('viewed_at')->nullable();
            
            // Receipt from developer
            $table->string('receipt_document_path')->nullable();
            $table->string('receipt_document_name')->nullable();
            $table->timestamp('receipt_uploaded_at')->nullable();
            $table->string('receipt_uploaded_by')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_thirdparties');
    }
};
