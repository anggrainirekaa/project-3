<?php
/** Validasi input sisi server. Semua fungsi mengembalikan string error atau null. */
function v_required(string $label, string $value): ?string
{
    return trim($value) === '' ? "$label wajib diisi." : null;
}

function v_maxlen(string $label, string $value, int $max): ?string
{
    return mb_strlen($value) > $max ? "$label maksimal $max karakter." : null;
}

function v_int_min(string $label, $value, int $min): ?string
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        return "$label harus berupa bilangan bulat.";
    }
    return (int) $value < $min ? "$label tidak boleh kurang dari $min." : null;
}

function v_number_min(string $label, $value, float $min): ?string
{
    if (!is_numeric($value)) {
        return "$label harus berupa angka.";
    }
    return (float) $value < $min ? "$label tidak boleh negatif." : null;
}

function v_email_optional(string $label, string $value): ?string
{
    if (trim($value) === '') {
        return null;
    }
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label tidak valid.";
}

/** Gabungkan hasil validasi: buang null. */
function collect_errors(array $results): array
{
    return array_values(array_filter($results));
}

/** Sorting hanya boleh memakai kolom dalam whitelist. */
function safe_sort(string $requested, array $allowed, string $default): string
{
    return in_array($requested, $allowed, true) ? $requested : $default;
}

function safe_dir(string $requested): string
{
    return strtolower($requested) === 'desc' ? 'DESC' : 'ASC';
}
