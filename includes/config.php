<?php
/**
 * config.php - central settings for the whole project.
 * Everyone includes this (csv_helper.php already does it for you).
 */

date_default_timezone_set('Asia/Manila');

// Folder holding the CSV files. ATT_DATA_DIR env var is only used by tests.
define('DATA_DIR', rtrim(getenv('ATT_DATA_DIR') ?: __DIR__ . '/../data', '/') . '/');

define('USERS_CSV',      DATA_DIR . 'users.csv');
define('ATTENDANCE_CSV', DATA_DIR . 'attendance.csv');

// Column order of each CSV file (first line of the file)
define('USERS_HEADERS',      ['user_id', 'name', 'pin_hash', 'face_descriptor', 'created_at']);
define('ATTENDANCE_HEADERS', ['user_id', 'name', 'date', 'time_in', 'time_out']);

// Face matching: lower distance = more similar. Smaller threshold = stricter.
define('FACE_MATCH_THRESHOLD', 0.5);
define('FACE_DESCRIPTOR_LENGTH', 128);   // face-api.js gives 128 numbers per face
