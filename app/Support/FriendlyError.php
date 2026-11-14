<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

class FriendlyError
{
    /**
     * Convert an exception into a clean, human-readable Indonesian error message.
     */
    public static function message(Throwable|string|null $error, string $prefix = 'Terjadi kesalahan'): string
    {
        if ($error === null) {
            return $prefix . '. Silakan periksa kembali data Anda.';
        }

        // If a string was passed directly
        if (is_string($error)) {
            return self::cleanString($error, $prefix);
        }

        // Log the full technical exception for debugging purposes
        Log::error("{$prefix}: " . $error->getMessage(), [
            'exception' => $error,
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ]);

        $msg = $error->getMessage();

        // 1. Database Query & PDO Exceptions
        if ($error instanceof QueryException || $error instanceof PDOException || str_contains($msg, 'SQLSTATE') || str_contains($msg, 'SQL:')) {
            return self::parseDatabaseError($msg, $prefix);
        }

        // 2. HTTP / Model Not Found
        if ($error instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || str_contains($msg, 'No query results for model')) {
            return "{$prefix}: Data yang Anda cari tidak ditemukan atau telah dihapus.";
        }

        // 3. Clean user-level / business exceptions
        if (!str_contains($msg, 'vendor/') && !str_contains($msg, 'Stack trace') && !str_contains($msg, 'Connection:')) {
            $cleanMsg = trim($msg);
            // If the message is already user-friendly and not a class name
            if (strlen($cleanMsg) > 0 && !str_contains($cleanMsg, '\\')) {
                return $prefix ? "{$prefix}: {$cleanMsg}" : $cleanMsg;
            }
        }

        return "{$prefix}. Terjadi kendala saat memproses permintaan Anda. Silakan coba kembali.";
    }

    /**
     * Clean and sanitize raw technical error strings.
     */
    public static function cleanString(string $rawMessage, string $prefix = ''): string
    {
        $rawMessage = trim($rawMessage);

        if (str_contains($rawMessage, 'SQLSTATE') || str_contains($rawMessage, 'SQL:') || str_contains($rawMessage, 'Connection:')) {
            return self::parseDatabaseError($rawMessage, $prefix ?: 'Gagal memproses data');
        }

        return $rawMessage;
    }

    /**
     * Translate database error patterns to user-friendly messages.
     */
    private static function parseDatabaseError(string $rawMsg, ?string $prefix = null): string
    {
        $cleanPrefix = rtrim($prefix ?? 'Gagal memproses data', ': ');

        // Foreign key constraint violation (Error 1451 / 1452)
        if (str_contains($rawMsg, 'foreign key constraint') || str_contains($rawMsg, '1451') || str_contains($rawMsg, '1452')) {
            return "{$cleanPrefix}: Data tidak dapat diubah atau dihapus karena masih terkait dengan data transaksi lainnya.";
        }

        // Duplicate entry (Error 1062)
        if (str_contains($rawMsg, 'Duplicate entry') || str_contains($rawMsg, '1062')) {
            return "{$cleanPrefix}: Data sudah terdaftar di sistem atau terjadi duplikasi data. Silakan periksa kembali input Anda.";
        }

        // Column value / Type mismatch / Null constraint (Error 1366 / 1048 / 1265)
        if (str_contains($rawMsg, 'Incorrect integer value') || str_contains($rawMsg, '1366') || str_contains($rawMsg, 'Data truncated') || str_contains($rawMsg, 'cannot be null') || str_contains($rawMsg, '1048')) {
            return "{$cleanPrefix}: Format data yang dimasukkan tidak sesuai dengan standar sistem. Silakan periksa kembali formulir Anda.";
        }

        // Deadlock / Lock wait timeout
        if (str_contains($rawMsg, 'Deadlock') || str_contains($rawMsg, 'Lock wait timeout')) {
            return "{$cleanPrefix}: Sistem sedang sibuk melayani transaksi lain. Silakan coba beberapa detik lagi.";
        }

        // General integrity constraint violation (SQLSTATE 23000)
        if (str_contains($rawMsg, '23000') || str_contains($rawMsg, 'Integrity constraint violation')) {
            return "{$cleanPrefix}: Terjadi kendala relasi atau integritas data transaksi. Silakan periksa kembali data Anda.";
        }

        // General database error without leaking credentials, IPs, tables, or queries
        return "{$cleanPrefix}: Terjadi kendala pada server database. Silakan coba beberapa saat lagi atau hubungi administrator.";
    }
}
