<?php
function ensure_data_directory(): void
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function data_file_path(string $name): string
{
    ensure_data_directory();
    return __DIR__ . '/data/' . $name . '.json';
}

function load_json_data(string $name, array $default = []): array
{
    $path = data_file_path($name);
    if (!file_exists($path)) {
        save_json_data($name, $default);
        return $default;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return $default;
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

function save_json_data(string $name, array $data): bool
{
    $path = data_file_path($name);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $file = fopen($path, 'c');
    if ($file === false) {
        return false;
    }
    flock($file, LOCK_EX);
    ftruncate($file, 0);
    fwrite($file, $json);
    fflush($file);
    flock($file, LOCK_UN);
    fclose($file);
    return true;
}
