<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FaceClusterService
 *
 * Mengelola pengelompokan wajah (DBSCAN) via Python microservice.
 */
class FaceClusterService
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.facenet.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (int) config('services.facenet.timeout', 60);
    }

    /**
     * Jalankan DBSCAN clustering (sinkron).
     *
     * @param  int[]|null  $folderIds  null = semua folder publik
     */
    public function runClustering(?array $folderIds = null): array
    {
        try {
            $payload = [];
            if ($folderIds) {
                $payload['folder_ids'] = implode(',', $folderIds);
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/cluster", $payload);

            if ($response->successful()) {
                return ['success' => true, 'result' => $response->json('result', [])];
            }

            return ['success' => false, 'message' => $response->json('detail', $response->body())];
        } catch (\Exception $e) {
            Log::error('FaceCluster: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Jalankan clustering di background (async).
     *
     * @param  int[]|null  $folderIds
     */
    public function runClusteringAsync(?array $folderIds = null): array
    {
        try {
            $payload = ['async_mode' => '1'];
            if ($folderIds) {
                $payload['folder_ids'] = implode(',', $folderIds);
            }

            $response = Http::timeout(10)
                ->asForm()
                ->post("{$this->baseUrl}/cluster", $payload);

            return $response->successful()
                ? ['success' => true, 'message' => $response->json('message')]
                : ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ambil daftar semua cluster dari Python service.
     */
    public function getClusters(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/clusters");

            return $response->successful()
                ? $response->json('clusters', [])
                : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Ambil semua foto dalam satu cluster dari Python service.
     */
    public function getClusterPhotos(int $clusterId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/clusters/{$clusterId}/photos");

            return $response->successful()
                ? $response->json('photos', [])
                : [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
