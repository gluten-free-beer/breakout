<?php
function rss_read_json($path, $fallback = array())
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function rss_value($source, $key, $fallback = '')
{
    return is_array($source) && array_key_exists($key, $source)
        ? $source[$key]
        : $fallback;
}

function rss_xml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function rss_cdata($value)
{
    return str_replace(']]>', ']]]]><![CDATA[>', (string) $value);
}

function rss_absolute_url($base, $url)
{
    $url = trim((string) $url);
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $url = preg_replace('#^\./#', '', $url);
    return rtrim((string) $base, '/') . '/' . ltrim($url, '/');
}

function rss_safe_update_html($html)
{
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', (string) $html);
    $html = strip_tags($html, '<p><em><strong><br>');
    $html = preg_replace('/<(p|em|strong)\b[^>]*>/i', '<$1>', $html);
    return preg_replace('/<br\b[^>]*>/i', '<br>', $html);
}

function rss_mime_type($path)
{
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
    $types = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif'
    );

    return isset($types[$extension]) ? $types[$extension] : 'application/octet-stream';
}

$root = __DIR__;
$config = rss_read_json($root . '/config.json');
$person = rss_read_json($root . '/data/me.json');
$updates = is_array(rss_value($person, 'updates')) ? $person['updates'] : array();

$siteName = rss_value($config, 'site_name', 'BreakOut');
$siteUrl = rtrim(rss_value($config, 'site_url', 'https://example.com/breakout'), '/');
$personName = rss_value($person, 'name', 'Profile');
$personSlug = rss_value($person, 'slug', 'profile');
$profileUrl = $siteUrl . '/' . rawurlencode($personSlug);
$feedUrl = $siteUrl . '/rss.xml';
$latestTimestamp = 0;

foreach ($updates as $update) {
    if (is_array($update) && trim((string) rss_value($update, 'date')) !== '') {
        $timestamp = strtotime($update['date'] . ' 12:00:00 UTC');
        if ($timestamp !== false && $timestamp > $latestTimestamp) {
            $latestTimestamp = $timestamp;
        }
    }
}

if ($latestTimestamp === 0) {
    $latestTimestamp = time();
}

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title><?= rss_xml($personName . ' — ' . $siteName . ' updates') ?></title>
        <link><?= rss_xml($profileUrl) ?></link>
        <description><?= rss_xml(rss_value($person, 'tagline', 'Public professional updates.')) ?></description>
        <language>en-us</language>
        <lastBuildDate><?= rss_xml(gmdate(DATE_RSS, $latestTimestamp)) ?></lastBuildDate>
        <atom:link href="<?= rss_xml($feedUrl) ?>" rel="self" type="application/rss+xml" />
        <?php foreach ($updates as $index => $update): ?>
            <?php if (is_array($update) && (trim((string) rss_value($update, 'text')) !== '' || trim((string) rss_value($update, 'image')) !== '')): ?>
                <?php
                $date = rss_value($update, 'date');
                $timestamp = strtotime($date . ' 12:00:00 UTC');
                if ($timestamp === false) {
                    $timestamp = $latestTimestamp;
                }
                $image = rss_value($update, 'image');
                $imageUrl = $image !== '' ? rss_absolute_url($siteUrl, $image) : '';
                $description = rss_safe_update_html(rss_value($update, 'text'));
                if ($imageUrl !== '') {
                    $description .= '<p><img src="' . rss_xml($imageUrl) . '" alt="' . rss_xml(rss_value($update, 'image_alt')) . '"></p>';
                }
                $localImage = $image !== '' && strpos($image, './') === 0
                    ? $root . '/' . substr($image, 2)
                    : '';
                $imageLength = $localImage !== '' && is_file($localImage) ? filesize($localImage) : 0;
                ?>
                <item>
                    <title><?= rss_xml($personName . ' — ' . ($date !== '' ? $date : 'Update')) ?></title>
                    <link><?= rss_xml($profileUrl) ?></link>
                    <guid isPermaLink="false"><?= rss_xml($profileUrl . '#update-' . rawurlencode($date) . '-' . $index) ?></guid>
                    <pubDate><?= rss_xml(gmdate(DATE_RSS, $timestamp)) ?></pubDate>
                    <description><![CDATA[<?= rss_cdata($description) ?>]]></description>
                    <?php if ($imageUrl !== ''): ?>
                        <enclosure url="<?= rss_xml($imageUrl) ?>" length="<?= rss_xml($imageLength) ?>" type="<?= rss_xml(rss_mime_type($image)) ?>" />
                        <media:content url="<?= rss_xml($imageUrl) ?>" type="<?= rss_xml(rss_mime_type($image)) ?>" medium="image">
                            <media:description><?= rss_xml(rss_value($update, 'image_alt')) ?></media:description>
                        </media:content>
                    <?php endif; ?>
                </item>
            <?php endif; ?>
        <?php endforeach; ?>
    </channel>
</rss>
