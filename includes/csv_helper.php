<?php
/**
 * csv_helper.php - generic, safe CSV reading/writing.
 * Knows nothing about users or attendance. Every write uses flock() so two
 * people using the system at the same time cannot corrupt the file.
 */
require_once __DIR__ . '/config.php';

/** Stops spreadsheet formula injection (cells starting with = + - @). */
function csvCell($value): string {
    $v = (string)$value;
    if ($v !== '' && strpos("=+-@", $v[0]) !== false) {
        return "'" . $v;
    }
    return $v;
}

/** Creates the file (and folder) with its header row if it doesn't exist yet. */
function csvEnsureFile(string $file, array $headers): void {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fh = fopen($file, 'c+');
    if (!$fh) throw new RuntimeException("Cannot open $file");
    flock($fh, LOCK_EX);
    if (fstat($fh)['size'] === 0) {
        fputcsv($fh, $headers, ',', '"', '');
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
}

/** Internal: reads headers+rows from an already-locked handle. */
function csvReadHandle($fh): array {
    rewind($fh);
    $headers = fgetcsv($fh, 0, ',', '"', '');
    if ($headers === false || $headers === [null]) return [[], []];
    $rows = [];
    while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        if ($line === [null]) continue;               // skip blank lines
        $row = [];
        foreach ($headers as $i => $h) $row[$h] = $line[$i] ?? '';
        $rows[] = $row;
    }
    return [$headers, $rows];
}

/** Internal: rewrites the whole file from an already-locked handle. */
function csvWriteHandle($fh, array $headers, array $rows): void {
    rewind($fh);
    ftruncate($fh, 0);
    fputcsv($fh, $headers, ',', '"', '');
    foreach ($rows as $r) {
        $line = [];
        foreach ($headers as $h) $line[] = csvCell($r[$h] ?? '');
        fputcsv($fh, $line, ',', '"', '');
    }
    fflush($fh);
}

/** Returns every row as an associative array. [] if file missing/empty. */
function readCSV(string $file): array {
    if (!file_exists($file)) return [];
    $fh = fopen($file, 'r');
    if (!$fh) return [];
    flock($fh, LOCK_SH);
    [, $rows] = csvReadHandle($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $rows;
}

/** First row where $row[$field] == $value, or null. */
function findInCSV(string $file, string $field, string $value): ?array {
    foreach (readCSV($file) as $row) {
        if (($row[$field] ?? null) === $value) return $row;
    }
    return null;
}

/** Adds one row at the end. Returns true on success. */
function appendCSV(string $file, array $headers, array $row): bool {
    csvEnsureFile($file, $headers);
    $fh = fopen($file, 'a');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    $line = [];
    foreach ($headers as $h) $line[] = csvCell($row[$h] ?? '');
    $ok = fputcsv($fh, $line, ',', '"', '') !== false;
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

/**
 * Adds a row ONLY if no existing row has the same $keyField value.
 * The check and the write happen under one lock, so two people can't
 * register the same ID at the same moment. Returns false if duplicate.
 */
function appendCSVUnique(string $file, array $headers, array $row, string $keyField): bool {
    csvEnsureFile($file, $headers);
    $fh = fopen($file, 'c+');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    [, $rows] = csvReadHandle($fh);
    foreach ($rows as $r) {
        if (($r[$keyField] ?? null) === (string)($row[$keyField] ?? '')) {
            flock($fh, LOCK_UN);
            fclose($fh);
            return false;
        }
    }
    fseek($fh, 0, SEEK_END);
    $line = [];
    foreach ($headers as $h) $line[] = csvCell($row[$h] ?? '');
    $ok = fputcsv($fh, $line, ',', '"', '') !== false;
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

/**
 * Updates every row for which $match($row) returns true, applying $changes.
 * Returns how many rows were changed.
 */
function updateCSVWhere(string $file, callable $match, array $changes): int {
    if (!file_exists($file)) return 0;
    $fh = fopen($file, 'c+');
    if (!$fh) return 0;
    flock($fh, LOCK_EX);
    [$headers, $rows] = csvReadHandle($fh);
    $count = 0;
    foreach ($rows as &$r) {
        if ($match($r)) {
            foreach ($changes as $k => $v) {
                if (in_array($k, $headers, true)) $r[$k] = $v;
            }
            $count++;
        }
    }
    unset($r);
    if ($count > 0) csvWriteHandle($fh, $headers, $rows);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $count;
}

/** Simple version: update rows where $keyField == $keyValue. */
function updateCSV(string $file, string $keyField, string $keyValue, array $changes): int {
    return updateCSVWhere($file, fn($r) => ($r[$keyField] ?? null) === $keyValue, $changes);
}

/** Deletes rows where $keyField == $keyValue. Returns number deleted. */
function deleteCSV(string $file, string $keyField, string $keyValue): int {
    if (!file_exists($file)) return 0;
    $fh = fopen($file, 'c+');
    if (!$fh) return 0;
    flock($fh, LOCK_EX);
    [$headers, $rows] = csvReadHandle($fh);
    $kept = array_values(array_filter($rows, fn($r) => ($r[$keyField] ?? null) !== $keyValue));
    $deleted = count($rows) - count($kept);
    if ($deleted > 0) csvWriteHandle($fh, $headers, $kept);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $deleted;
}
