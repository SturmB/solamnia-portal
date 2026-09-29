<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invites', function (Blueprint $table): void {
            $table->text('plain_token')
                ->after('token')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invites', function (Blueprint $table): void {
            $table->dropColumn('plain_token');
        });
    }
};
