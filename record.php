<?php
function record_read_json($path, $fallback = array())
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function record_slugify($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$root = __DIR__;
$requestedSlug = isset($_GET['slug']) ? record_slugify($_GET['slug']) : '';
$person = record_read_json($root . '/data/me.json');
$personName = isset($person['name']) ? $person['name'] : 'person';
$personSlug = isset($person['slug']) && trim((string) $person['slug']) !== ''
    ? record_slugify($person['slug'])
    : record_slugify($personName);

if ($requestedSlug !== '' && $requestedSlug === $personSlug) {
    require $root . '/profile.php';
    exit;
}

$_GET['id'] = $requestedSlug;
require $root . '/company.php';
