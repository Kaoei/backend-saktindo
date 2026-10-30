<?php

if (!function_exists('terbilangAngka')) {
    /**
     * Konversi angka nominal bulat ke kata-kata Bahasa Indonesia (rekursif)
     */
    function terbilangAngka($angka): string
    {
        $angka = (int) $angka;
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 0) {
            return 'Minus ' . terbilangAngka(abs($angka));
        } elseif ($angka < 12) {
            return $huruf[$angka];
        } elseif ($angka < 20) {
            return trim(terbilangAngka($angka - 10) . ' Belas');
        } elseif ($angka < 100) {
            return trim(terbilangAngka(intval($angka / 10)) . ' Puluh ' . terbilangAngka($angka % 10));
        } elseif ($angka < 200) {
            return trim('Seratus ' . terbilangAngka($angka - 100));
        } elseif ($angka < 1000) {
            return trim(terbilangAngka(intval($angka / 100)) . ' Ratus ' . terbilangAngka($angka % 100));
        } elseif ($angka < 2000) {
            return trim('Seribu ' . terbilangAngka($angka - 1000));
        } elseif ($angka < 1000000) {
            return trim(terbilangAngka(intval($angka / 1000)) . ' Ribu ' . terbilangAngka($angka % 1000));
        } elseif ($angka < 1000000000) {
            return trim(terbilangAngka(intval($angka / 1000000)) . ' Juta ' . terbilangAngka($angka % 1000000));
        } elseif ($angka < 1000000000000) {
            return trim(terbilangAngka(intval($angka / 1000000000)) . ' Milyar ' . terbilangAngka($angka % 1000000000));
        } elseif ($angka < 1000000000000000) {
            return trim(terbilangAngka(intval($angka / 1000000000000)) . ' Triliun ' . terbilangAngka($angka % 1000000000000));
        }

        return '';
    }
}

if (!function_exists('terbilangRupiah')) {
    /**
     * Menghasilkan teks terbilang nominal lengkap diakhiri "Rupiah".
     * Contoh: 150000 -> "Seratus Lima Puluh Ribu Rupiah"
     */
    function terbilangRupiah($angka): string
    {
        $nominal = (int) round((float) $angka);
        if ($nominal === 0) {
            return 'Nol Rupiah';
        }
        $kata = trim(preg_replace('/\s+/', ' ', terbilangAngka($nominal)));
        return $kata ? $kata . ' Rupiah' : 'Nol Rupiah';
    }
}

if (!function_exists('terbilang')) {
    /**
     * Backward-compatible alias untuk terbilangAngka
     */
    function terbilang($angka)
    {
        return terbilangAngka($angka);
    }
}

if (!function_exists('parseFlexibleMoney')) {
    /**
     * Parsing nominal uang yang aman dari format database (150000.00),
     * format ribuan titik Indonesia (150.000 / 1.500.000), maupun desimal koma (150.000,00).
     */
    function parseFlexibleMoney($val): float
    {
        if (is_int($val) || is_float($val)) {
            return (float) $val;
        }
        $val = trim((string) $val);
        if ($val === '') {
            return 0.0;
        }

        $val = preg_replace('/[^\d.,-]/', '', $val);

        if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
            if (strrpos($val, ',') > strrpos($val, '.')) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else {
                $val = str_replace(',', '', $val);
            }
            return (float) $val;
        }

        if (strpos($val, ',') !== false) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $val)) {
                return (float) str_replace(',', '', $val);
            }
            return (float) str_replace(',', '.', $val);
        }

        if (strpos($val, '.') !== false) {
            if (substr_count($val, '.') > 1) {
                return (float) str_replace('.', '', $val);
            }
            $parts = explode('.', $val);
            $decimalPart = $parts[1] ?? '';
            if (strlen($decimalPart) === 3 && strlen($parts[0]) >= 1 && strlen($parts[0]) <= 3) {
                return (float) str_replace('.', '', $val);
            }
            return (float) $val;
        }

        return (float) $val;
    }
}
