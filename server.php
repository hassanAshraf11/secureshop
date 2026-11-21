<?php

// Router for PHP's local server. Only files inside public/ may be served directly.
$public = realpath(__DIR__.'/public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = realpath($public.$path);
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require $public.'/index.php';
