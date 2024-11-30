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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('email')->nullable()->default(null)->change();
            $table->string('mobile', 13)->after('remember_token');
            $table->integer('otp')->after('mobile');
            $table->timestamp('expires_at')->after('otp');
            $table->enum('role', ['admin', 'manager', 'user'])->after('expires_at');
            $table->integer('manager_id')->after('role');
            $table->string('address_line_1')->nullable()->after('manager_id'); 
            $table->string('address_line_2')->nullable()->after('address_line_1'); 
            $table->string('city')->nullable()->after('address_line_2'); 
            $table->integer('pincode')->nullable()->after('city'); 
            $table->string('gstin')->nullable()->after('pincode'); 
            $table->string('state')->nullable()->after('gstin'); 
            $table->string('country')->nullable()->after('state'); 
            $table->enum('price_type', ['a', 'b', 'c', 'd'])->after('country');
            $table->string('transport')->nullable()->after('price_type'); 
            $table->string('app_status')->nullable()->after('transport'); 
            $table->string('last_viewed')->nullable()->after('app_status'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
