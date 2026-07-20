<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO fields for articles: a focus keyword (for on-page checks and reporting)
 * and an FAQ list (rendered as FAQPage structured data for rich results).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('focus_keyword')->nullable()->after('meta_description');
            $table->json('faq')->nullable()->after('focus_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['focus_keyword', 'faq']);
        });
    }
};
