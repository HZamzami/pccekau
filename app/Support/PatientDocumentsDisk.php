<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

// Which filesystem disk holds patient documents.
//
// Laravel Cloud attaches a bucket as a disk under whatever name was chosen
// when it was created, and doesn't set the AWS_* variables the stock "s3"
// disk reads. Pointing PATIENT_DOCUMENTS_DISK at "s3" there made every
// upload fail with "Missing required client configuration options" (a 500).
// So a configured disk is only used if it can actually work; otherwise the
// attached bucket is found by itself.
class PatientDocumentsDisk
{
    public static function name(): string
    {
        $configured = config('filesystems.patient_documents');

        if ($configured && static::isUsable($configured)) {
            return $configured;
        }

        $bucket = collect(config('filesystems.disks'))
            ->filter(fn (array $disk, string $name) => ($disk['driver'] ?? null) === 's3' && static::isUsable($name))
            ->keys()
            ->first();

        $fallback = $bucket ?? (static::isUsable((string) config('filesystems.default')) ? config('filesystems.default') : 'local');

        if ($configured) {
            Log::warning("PATIENT_DOCUMENTS_DISK is set to \"{$configured}\", which isn't a working disk; using \"{$fallback}\" instead.");
        }

        return $fallback;
    }

    protected static function isUsable(string $name): bool
    {
        $disk = config("filesystems.disks.{$name}");

        if (! is_array($disk)) {
            return false;
        }

        // An S3-style disk needs a bucket and somewhere to reach it: a region,
        // or (R2, Laravel Cloud) its own endpoint.
        return ($disk['driver'] ?? null) !== 's3'
            || (filled($disk['bucket'] ?? null) && (filled($disk['region'] ?? null) || filled($disk['endpoint'] ?? null)));
    }
}
