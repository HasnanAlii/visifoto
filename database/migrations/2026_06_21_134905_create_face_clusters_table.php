<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Orang Tidak Dikenal');
            $table->unsignedBigInteger('representative_drive_file_id')->nullable()->comment('Foto representatif kluster');
            $table->unsignedInteger('member_count')->default(0);
            $table->timestamps();

            $table->foreign('representative_drive_file_id')
                ->references('id')->on('drive_files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_clusters');
    }
};
