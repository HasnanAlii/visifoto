<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini menyimpan metadata file yang sudah diindeks oleh Python FaceNet service.
     * Embedding (vektor 512 dimensi) disimpan di JSON file oleh service Python,
     * tabel ini digunakan untuk tracking status indexing & menghubungkan hasil
     * pencarian (relative path) ke record DriveFile.
     */
    public function up(): void
    {
        Schema::create('face_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_file_id')->constrained('drive_files')->onDelete('cascade');
            $table->string('relative_path')->unique()->comment('Path relatif di storage, dipakai sebagai key oleh Python service');
            $table->boolean('has_face')->default(false)->comment('Apakah wajah berhasil terdeteksi');
            $table->string('file_hash', 32)->nullable()->comment('MD5 hash file untuk deteksi perubahan');
            $table->timestamp('indexed_at')->nullable()->comment('Waktu file selesai diindeks oleh Python service');
            $table->timestamps();

            $table->index(['has_face']);
            $table->index(['drive_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_embeddings');
    }
};
