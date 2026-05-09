<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    public function store(UploadedFile $file, string $directory, ?string $disk = 'public'): array
    {
        try {
            if (!$file->isValid()) {
                return $this->error('Invalid file upload.');
            }

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, $disk);

            if (!$path) {
                return $this->error('Failed to store file.');
            }

            return $this->success('File stored successfully.', Storage::disk($disk)->url($path));
        } catch (\Exception $e) {
            return $this->error('Error storing file: ' . $e->getMessage());
        }
    }

    public function storeBase64Image(string $base64Data, string $directory, ?string $disk = 'public'): array
    {
        try {
            $result = $this->parseBase64Image($base64Data);
            if ($result['error']) {
                return $result;
            }

            $filename = Str::uuid() . '.' . $result['extension'];
            $path = $directory . '/' . $filename;

            if (!Storage::disk($disk)->put($path, $result['data'])) {
                return $this->error('Failed to store file.');
            }

            return $this->success('Image stored successfully.', Storage::disk($disk)->url($path));
        } catch (\Exception $e) {
            return $this->error('Error storing image: ' . $e->getMessage());
        }
    }

    public function delete(string $path, ?string $disk = 'public'): array
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                return $this->error('File not found.');
            }

            Storage::disk($disk)->delete($path);
            return $this->success('File deleted successfully.');
        } catch (\Exception $e) {
            return $this->error('Error deleting file: ' . $e->getMessage());
        }
    }

    public function getUrl(string $path, ?string $disk = 'public'): array
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                return $this->error('File not found.');
            }

            return $this->success('URL retrieved successfully.', Storage::disk($disk)->url($path));
        } catch (\Exception $e) {
            return $this->error('Error getting URL: ' . $e->getMessage());
        }
    }

    private function parseBase64Image(string $base64Data): array
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            return $this->error('Invalid base64 image data.');
        }

        $extension = $matches[1];
        $imageData = base64_decode(substr($base64Data, strpos($base64Data, ',') + 1), true);

        if ($imageData === false) {
            return $this->error('Failed to decode base64 data.');
        }

        return [
            'error' => false,
            'extension' => $extension,
            'data' => $imageData,
        ];
    }

    private function success(string $message, ?string $url = null): array
    {
        $response = [
            'error' => false,
            'message' => $message,
        ];

        if ($url !== null) {
            $response['url'] = $url;
        }

        return $response;
    }

    private function error(string $message): array
    {
        return [
            'error' => true,
            'message' => $message,
        ];
    }
}
