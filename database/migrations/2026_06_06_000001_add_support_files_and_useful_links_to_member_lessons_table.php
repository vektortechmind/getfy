<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('member_lessons', 'support_files')) {
                $table->json('support_files')->nullable()->after('content_files');
            }
            if (! Schema::hasColumn('member_lessons', 'useful_links')) {
                $table->json('useful_links')->nullable()->after('support_files');
            }
        });
    }

    public function down(): void
    {
        Schema::table('member_lessons', function (Blueprint $table) {
            if (Schema::hasColumn('member_lessons', 'useful_links')) {
                $table->dropColumn('useful_links');
            }
            if (Schema::hasColumn('member_lessons', 'support_files')) {
                $table->dropColumn('support_files');
            }
        });
    }
};
