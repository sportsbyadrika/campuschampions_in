<?php
$code = 500;
$heading = 'Something went wrong';
$message = ($customMessage ?? '') ?: 'An unexpected error occurred. Our team has been notified.';
require __DIR__ . '/_layout.php';
