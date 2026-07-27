<?php
function read_json($path, $fallback = array())
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function value($source, $key, $fallback = '')
{
    return is_array($source) && array_key_exists($key, $source)
        ? $source[$key]
        : $fallback;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function filled($value)
{
    return is_array($value) ? count($value) > 0 : trim((string) $value) !== '';
}

function initial($value)
{
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr((string) $value, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return strtoupper(substr((string) $value, 0, 1));
}

function safe_url($url)
{
    $url = trim($url);
    if ($url === '') {
        return '#';
    }

    if (strpos($url, '/') === 0 || strpos($url, './') === 0) {
        return $url;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https', 'mailto'], true) ? $url : '#';
}

function slugify($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function find_company($companies, $reference)
{
    $reference = slugify($reference);
    foreach ($companies as $company) {
        if (is_array($company) && slugify(value($company, 'name')) === $reference) {
            return $company;
        }
    }

    return array();
}

function safe_update_html($html)
{
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', (string) $html);
    $html = strip_tags($html, '<p><em><strong><br>');
    $html = preg_replace('/<(p|em|strong)\b[^>]*>/i', '<$1>', $html);
    return preg_replace('/<br\b[^>]*>/i', '<br>', $html);
}

function render_entries($entries, $companies = array(), $includeCompanyLinks = false)
{
    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            $entry = array('title' => (string) $entry);
        }

        $title = value($entry, 'title', value($entry, 'label', value($entry, 'name')));
        $subtitle = value($entry, 'subtitle', value($entry, 'role', value($entry, 'status')));
        $period = value($entry, 'period', value($entry, 'years'));
        $summary = value($entry, 'summary', value($entry, 'description'));
        $companyReference = value($entry, 'company', $title);
        $linkedCompany = $includeCompanyLinks ? find_company($companies, $companyReference) : array();
        ?>
        <article class="entry">
            <div class="entry__heading">
                <div>
                    <?php if (filled($title)): ?><h3><?= e($title) ?></h3><?php endif; ?>
                    <?php if (filled($subtitle)): ?><p class="entry__subtitle"><?= e($subtitle) ?></p><?php endif; ?>
                </div>
                <?php if (filled($period)): ?><p class="entry__period"><?= e($period) ?></p><?php endif; ?>
            </div>
            <?php if (filled($summary)): ?><p><?= e($summary) ?></p><?php endif; ?>
            <div class="entry__links">
                <?php if (filled(value($entry, 'url'))): ?>
                    <a class="text-link" href="<?= e(safe_url((string) $entry['url'])) ?>">Visit</a>
                <?php endif; ?>
                <?php if (filled($linkedCompany)): ?>
                    <a class="text-link" href="./<?= e(rawurlencode(slugify($linkedCompany['name']))) ?>">Company record</a>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
}

$root = __DIR__;
$config = read_json($root . '/config.json');
$me = read_json($root . '/data/me.json');
$companyData = read_json($root . '/data/company.json');
$connections = read_json($root . '/data/connections.json');
$companies = isset($companyData[0]) && is_array($companyData[0]) ? $companyData : array($companyData);

$siteName = value($config, 'site_name', 'BreakOut');
$name = value($me, 'name', 'Profile');
$titleSuffix = value($config, 'page_title_suffix', 'Welcome to our professional records');
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) value($config, 'accent'))
    ? $config['accent']
    : '#3ed5e2';
$logo = value($config, 'logo');
$portrait = value($me, 'portrait');
$experience = is_array(value($me, 'experience')) ? $me['experience'] : array();
$projects = is_array(value($me, 'projects')) ? $me['projects'] : array();
$skills = is_array(value($me, 'skills')) ? $me['skills'] : array();
$education = is_array(value($me, 'education')) ? $me['education'] : array();
$links = is_array(value($me, 'links')) ? $me['links'] : array();
$intro = is_array(value($me, 'intro')) ? $me['intro'] : array();
$updates = is_array(value($me, 'updates')) ? $me['updates'] : array();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(value($me, 'tagline') . ' — ' . $titleSuffix) ?>">
    <title><?= e($name) ?> — <?= e($siteName) ?></title>
    <?php if (value($me, 'rss', false) === true): ?>
        <link rel="alternate" type="application/rss+xml" title="<?= e($name) ?> updates" href="./rss.xml">
    <?php endif; ?>
    <link rel="stylesheet" href="./assets/breakout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>:root { --accent: <?= e($accent) ?>; }</style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="./" aria-label="<?= e($siteName) ?> directory">
            <?php if (filled($logo)): ?>
                <img src="<?= e(safe_url((string) $logo)) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <span class="brand__mark" aria-hidden="true">B/O</span>
                <span><?= e($siteName) ?></span>
            <?php endif; ?>
        </a>
        <p><?= e($titleSuffix) ?></p>
    </header>

    <main>
        <div class="record-bar" aria-label="Record information">
            <span>PUBLIC PROFESSIONAL RECORD</span>
            <span>SELF-VERIFIED <i class="fa-solid fa-v" aria-hidden="true"></i></span>
            <span>NO ENGAGEMENT METRICS AVAILABLE</span>
        </div>

        <section class="hero" aria-labelledby="profile-name">
            <div class="hero__portrait">
                <?php if (filled($portrait)): ?>
                    <img src="<?= e(safe_url((string) $portrait)) ?>" alt="<?= e($name) ?>">
                <?php else: ?>
                    <span aria-hidden="true"><?= e(initial($name)) ?></span>
                <?php endif; ?>
            </div>

            <?php if (value($me, 'overworking', false) === true): ?>
                <img class="overwork-ribbon" src="./assets/overworking.png" alt="#overworking">
            <?php endif; ?>

            <div class="hero__copy">
                <p class="field-label">Professional person</p>
                <h1 id="profile-name"><?= e($name) ?></h1>
                <?php if (filled(value($me, 'tagline'))): ?>
                    <p class="tagline"><?= e($me['tagline']) ?></p>
                <?php endif; ?>
                <?php if (filled(value($me, 'location'))): ?>
                    <p class="location"><?= e($me['location']) ?></p>
                <?php endif; ?>

                <?php if (filled($links) || value($me, 'rss', false) === true): ?>
                    <nav class="profile-links" aria-label="Profile links">
                        <?php foreach ($links as $link): ?>
                            <?php if (is_array($link) && filled(value($link, 'label')) && filled(value($link, 'url'))): ?>
                                <a href="<?= e(safe_url((string) $link['url'])) ?>"><?= e($link['label']) ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (value($me, 'rss', false) === true): ?>
                            <a href="./rss.xml">RSS</a>
                        <?php endif; ?>
                        <button class="print-record" type="button" onclick="window.print()">Print Record</button>
                    </nav>
                <?php endif; ?>
            </div>
        </section>

        <div class="layout">
            <div class="content">
                <?php if (filled($intro)): ?>
                    <section class="panel" aria-labelledby="about-title">
                        <p class="section-number">SUMMARY</p>
                        <h2 id="about-title">Professional Person</h2>
                        <?php foreach ($intro as $paragraph): ?>
                            <?php if (filled($paragraph)): ?><?= safe_update_html($paragraph) ?><?php endif; ?>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if (filled($projects)): ?>
                    <section class="panel" aria-labelledby="projects-title">
                        <p class="section-number">OUTPUT</p>
                        <h2 id="projects-title">Selected Output</h2>
                        <?php render_entries($projects, $companies, true); ?>
                    </section>
                <?php endif; ?>

                <?php if (filled($experience)): ?>
                    <section class="panel panel--list" aria-labelledby="experience-title">
                        <p class="section-number">HISTORY</p>
                        <h2 id="experience-title">Evidence of Employment</h2>
                        <?php render_entries($experience); ?>
                    </section>
                <?php endif; ?>

                <?php if (filled($education)): ?>
                    <section class="panel panel--list" aria-labelledby="education-title">
                        <p class="section-number">CREDENTIALS</p>
                        <h2 id="education-title">Formal Credentials</h2>
                        <?php render_entries($education); ?>
                    </section>
                <?php endif; ?>
            </div>

            <aside>
                <?php if (filled($updates)): ?>
                    <section class="panel panel--compact activity-feed" aria-labelledby="activity-title">
                        <p class="section-label">Record Log</p>
                        <h2 id="activity-title">Updates</h2>
                        <ol class="timeline">
                            <?php foreach ($updates as $update): ?>
                                <?php if (is_array($update) && (filled(value($update, 'text')) || filled(value($update, 'image')))): ?>
                                    <li>
                                        <?php if (filled(value($update, 'date'))): ?>
                                            <time datetime="<?= e($update['date']) ?>"><?= e($update['date']) ?></time>
                                        <?php endif; ?>
                                        <?php if (filled(value($update, 'text'))): ?>
                                            <div class="timeline__text"><?= safe_update_html($update['text']) ?></div>
                                        <?php endif; ?>
                                        <?php if (filled(value($update, 'image'))): ?>
                                            <img
                                                class="timeline__image"
                                                src="<?= e(safe_url((string) $update['image'])) ?>"
                                                alt="<?= e(value($update, 'image_alt')) ?>"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endif; ?>

                <?php if (filled($skills)): ?>
                    <section class="panel panel--compact">
                        <p class="section-label">Self-assessed</p>
                        <h2>Declared Capabilities</h2>
                        <ul class="tags">
                            <?php foreach ($skills as $skill): ?>
                                <?php if (filled($skill)): ?><li><?= e($skill) ?></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if (filled(value($me, 'aside'))): ?>
                    <section class="aside-note">
                        <p><?= e($me['aside']) ?></p>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <footer>
        <p><?= e(value($config, 'footer_note')) ?></p>
        <p>Built with <?= e($siteName) ?>. No endorsements were algorithmically inferred.</p>
    </footer>
</body>
</html>
