<?php

namespace App\Services;

use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    public function store(User $uploader, UploadedFile $file): StoredFile
    {
        $disk = config('filesystems.default', 'local');
        $directory = 'uploads/'.now()->format('Y/m');
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $storedName, ['disk' => $disk]);

        return StoredFile::create([
            'user_id' => $uploader->id,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'size' => $file->getSize() ?: 0,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'visibility' => 'private',
        ]);
    }

    public function deleteFromDisk(StoredFile $storedFile): void
    {
        if (Storage::disk($storedFile->disk)->exists($storedFile->path)) {
            Storage::disk($storedFile->disk)->delete($storedFile->path);
        }
    }
}
