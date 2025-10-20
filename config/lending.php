<?php

return [
    'default_due_hours' => env('LENDING_DEFAULT_DUE_HOURS', 4),
    'violation_limit' => env('LENDING_VIOLATION_LIMIT', 3),
    'sanction_length_days' => env('LENDING_SANCTION_LENGTH_DAYS', 7),
];
