<?php
function company_read_json($path, $fallback = array())
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function company_value($source, $key, $fallback = '')
{
    return is_array($source) && array_key_exists($key, $source)
        ? $source[$key]
        : $fallback;
}

function company_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function company_filled($value)
{
    return is_array($value) ? count($value) > 0 : trim((string) $value) !== '';
}

function company_safe_url($url)
{
    $url = trim($url);
    if ($url === '') {
        return '#';
    }

    if (strpos($url, '/') === 0 || strpos($url, './') === 0) {
        return $url;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, array('http', 'https', 'mailto'), true) ? $url : '#';
}

function company_slugify($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function company_social_meta($platform)
{
    $key = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) $platform));
    $map = array(
        'x' => array('label' => 'X', 'icon' => 'fa-brands fa-x-twitter'),
        'twitter' => array('label' => 'X', 'icon' => 'fa-brands fa-x-twitter'),
        'ig' => array('label' => 'Instagram', 'icon' => 'fa-brands fa-instagram'),
        'instagram' => array('label' => 'Instagram', 'icon' => 'fa-brands fa-instagram'),
        'youtube' => array('label' => 'YouTube', 'icon' => 'fa-brands fa-youtube'),
        'github' => array('label' => 'GitHub', 'icon' => 'fa-brands fa-github'),
        'medium' => array('label' => 'Medium', 'icon' => 'fa-brands fa-medium'),
        'facebook' => array('label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f'),
        'threads' => array('label' => 'Threads', 'icon' => 'fa-brands fa-threads')
    );

    return isset($map[$key])
        ? $map[$key]
        : array('label' => (string) $platform, 'icon' => 'fa-solid fa-link');
}

function company_youtube_id($value)
{
    $value = trim((string) $value);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }

    $parts = parse_url($value);
    if (!is_array($parts) || !isset($parts['host'])) {
        return '';
    }

    $host = strtolower($parts['host']);
    $host = preg_replace('/^www\./', '', $host);
    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
    $candidate = '';

    if ($host === 'youtu.be') {
        $segments = explode('/', $path);
        $candidate = isset($segments[0]) ? $segments[0] : '';
    } elseif (in_array($host, array('youtube.com', 'm.youtube.com', 'youtube-nocookie.com'), true)) {
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['v'])) {
                $candidate = $query['v'];
            }
        }

        if ($candidate === '' && preg_match('#^(embed|shorts|live)/([A-Za-z0-9_-]{11})#', $path, $matches)) {
            $candidate = $matches[2];
        }
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : '';
}

$root = __DIR__;
$config = company_read_json($root . '/config.json');
$companyData = company_read_json($root . '/data/company.json');
$companies = isset($companyData[0]) && is_array($companyData[0]) ? $companyData : array($companyData);
$requestedCompany = isset($_GET['id']) ? company_slugify($_GET['id']) : '';
$company = $requestedCompany === '' && isset($companies[0]) && is_array($companies[0])
    ? $companies[0]
    : array();
foreach ($companies as $candidate) {
    if (is_array($candidate) && $requestedCompany !== '' && company_slugify(company_value($candidate, 'name')) === $requestedCompany) {
        $company = $candidate;
        break;
    }
}
if ($requestedCompany !== '' && !company_filled($company)) {
    http_response_code(404);
}
$media = is_array(company_value($company, 'media')) ? array_slice($company['media'], 0, 3) : array();
$milestones = is_array(company_value($company, 'milestones')) ? $company['milestones'] : array();
$fundraising = is_array(company_value($company, 'fundraising')) ? $company['fundraising'] : array();
$featured = is_array(company_value($company, 'featured')) ? $company['featured'] : array();
$team = is_array(company_value($company, 'team')) ? $company['team'] : array();
$social = is_array(company_value($company, 'social')) ? $company['social'] : array();
$youtubeId = company_youtube_id(company_value($company, 'hero_youtube'));

$siteName = company_value($config, 'site_name', 'BreakOut');
$companyName = company_value($company, 'name');
if (!company_filled($companyName)) {
    $companyName = 'Company record';
}
$logo = company_value($config, 'logo');
$companyLogo = company_value($company, 'logo');
$mediaStatus = strtoupper(str_replace('_', ' ', company_value($company, 'media_status', 'representative humans')));
$accentValue = company_value($config, 'accent');
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accentValue)
    ? $accentValue
    : '#3ed5e2';
$disclaimer = company_value(
    $company,
    'media_disclaimer',
    'Representative imagery only. Actual workplace, personnel, products, and services may vary.'
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= company_e(company_value($company, 'tagline', 'Independent company record')) ?>">
    <title><?= company_e($companyName) ?> — <?= company_e($siteName) ?></title>
    <link rel="alternate" type="application/rss+xml" title="<?= company_e($siteName) ?> updates" href="./rss.xml">
    <link rel="stylesheet" href="./assets/breakout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>:root { --accent: <?= company_e($accent) ?>; }</style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="./" aria-label="<?= company_e($siteName) ?> directory">
            <?php if (company_filled($logo)): ?>
                <img src="<?= company_e(company_safe_url((string) $logo)) ?>" alt="<?= company_e($siteName) ?>">
            <?php else: ?>
                <span class="brand__mark" aria-hidden="true">B/O</span>
                <span><?= company_e($siteName) ?></span>
            <?php endif; ?>
        </a>
        <p><?= company_e(company_value($config, 'page_title_suffix', 'Welcome to our professional records')) ?></p>
    </header>

    <main class="company-main">
        <div class="record-bar" aria-label="Record information">
            <span>PUBLIC COMPANY RECORD</span>
            <span>SELF-VERIFIED <i class="fa-solid fa-v" aria-hidden="true"></i></span>
            <span>NO SYNERGY SCORE AVAILABLE</span>
        </div>

        <section class="company-heading" aria-labelledby="company-name">
            <?php if (company_filled($companyLogo)): ?>
                <img class="company-mark" src="<?= company_e(company_safe_url((string) $companyLogo)) ?>" alt="">
            <?php endif; ?>
            <p class="field-label">Organization claiming to exist</p>
            <h1 id="company-name"><?= company_e($companyName) ?></h1>
            <?php if (company_filled(company_value($company, 'tagline'))): ?>
                <p class="tagline"><?= company_e($company['tagline']) ?></p>
            <?php endif; ?>
            <dl class="company-meta">
                <?php if (company_filled(company_value($company, 'industry'))): ?>
                    <div><dt>Industry</dt><dd><?= company_e($company['industry']) ?></dd></div>
                <?php endif; ?>
                <?php if (company_filled(company_value($company, 'location'))): ?>
                    <div><dt>Location</dt><dd><?= company_e($company['location']) ?></dd></div>
                <?php endif; ?>
                <?php if (company_filled(company_value($company, 'url'))): ?>
                    <div>
                        <dt>External evidence</dt>
                        <dd><a href="<?= company_e(company_safe_url((string) $company['url'])) ?>">Company website</a></dd>
                    </div>
                <?php endif; ?>
            </dl>

            <div class="company-actions">
                <?php if (company_filled($social)): ?>
                    <nav class="company-social" aria-label="<?= company_e($companyName) ?> social links">
                        <?php foreach ($social as $platform => $url): ?>
                            <?php if (company_filled($url) && company_safe_url((string) $url) !== '#'): ?>
                                <?php $socialMeta = company_social_meta($platform); ?>
                                <a
                                    href="<?= company_e(company_safe_url((string) $url)) ?>"
                                    aria-label="<?= company_e($companyName . ' on ' . $socialMeta['label']) ?>"
                                    title="<?= company_e($socialMeta['label']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="<?= company_e($socialMeta['icon']) ?>" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <button class="company-print print-record" type="button" onclick="window.print()">Print Record</button>
            </div>
        </section>

        <?php if ($youtubeId !== ''): ?>
            <section class="company-video" aria-labelledby="company-video-title">
                <p class="section-number">FEATURED VIDEO</p>
                <h2 id="company-video-title">Company-Issued Moving Pictures</h2>
                <div class="company-video__frame">
                    <iframe
                        src="https://www.youtube-nocookie.com/embed/<?= company_e($youtubeId) ?>"
                        title="<?= company_e($companyName) ?> featured video"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                </div>
            </section>
        <?php endif; ?>

        <?php if (company_filled(company_value($company, 'summary'))): ?>
            <section class="panel company-summary" aria-labelledby="company-summary-title">
                <p class="section-number">STATEMENT</p>
                <h2 id="company-summary-title">What the Company Says It Does</h2>
                <p><?= company_e($company['summary']) ?></p>
                <?php if (company_filled($fundraising)): ?>
                    <dl class="funding-grid">
                        <?php foreach (array('round' => 'Round', 'target' => 'Target', 'status' => 'Status') as $key => $label): ?>
                            <?php if (company_filled(company_value($fundraising, $key))): ?>
                                <div><dt><?= company_e($label) ?></dt><dd><?= company_e($fundraising[$key]) ?></dd></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (company_filled($team)): ?>
            <section class="panel company-team" aria-labelledby="company-team-title">
                <p class="section-number">PERSONNEL</p>
                <h2 id="company-team-title">People Attached to This Record</h2>
                <dl>
                    <?php foreach ($team as $person): ?>
                        <?php if (is_array($person) && company_filled(company_value($person, 'name'))): ?>
                            <div>
                                <dt><?= company_e(company_value($person, 'role', 'Person')) ?></dt>
                                <dd>
                                    <?php if (company_filled(company_value($person, 'profile'))): ?>
                                        <a href="./<?= company_e(rawurlencode(company_slugify($person['profile']))) ?>"><?= company_e($person['name']) ?></a>
                                    <?php else: ?>
                                        <?= company_e($person['name']) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            </section>
        <?php endif; ?>

        <?php if (company_filled($featured)): ?>
            <section class="featured-system" aria-labelledby="featured-system-title">
                <?php if (company_filled(company_value($featured, 'image'))): ?>
                    <img src="<?= company_e(company_safe_url((string) $featured['image'])) ?>" alt="<?= company_e(company_value($featured, 'name')) ?>">
                <?php endif; ?>
                <div>
                    <p class="section-label">Featured system / resident bird</p>
                    <h2 id="featured-system-title">Meet <?= company_e(company_value($featured, 'name')) ?></h2>
                    <?php if (company_filled(company_value($featured, 'role'))): ?>
                        <p class="tagline"><?= company_e($featured['role']) ?></p>
                    <?php endif; ?>
                    <?php if (company_filled(company_value($featured, 'summary'))): ?>
                        <p><?= company_e($featured['summary']) ?></p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (company_filled($milestones)): ?>
            <section class="panel company-summary" aria-labelledby="milestones-title">
                <p class="section-number">RECORD</p>
                <h2 id="milestones-title">Milestones and Stated Intentions</h2>
                <?php foreach ($milestones as $period => $items): ?>
                    <?php if (is_array($items) && company_filled($items)): ?>
                        <article class="entry">
                            <h3><?= company_e($period) ?></h3>
                            <ul>
                                <?php foreach ($items as $item): ?>
                                    <li><?= company_e($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if (company_filled($media)): ?>
            <section class="company-media" aria-labelledby="company-media-title">
                <div class="company-media__header">
                    <div>
                        <p class="section-number">REPRESENTATIVE IMAGERY</p>
                        <h2 id="company-media-title">People Allegedly at Work</h2>
                    </div>
                    <p class="media-status"><?= company_e($mediaStatus) ?></p>
                </div>

                <div class="company-gallery">
                    <?php foreach ($media as $image): ?>
                        <?php if (is_array($image) && company_filled(company_value($image, 'src'))): ?>
                            <figure>
                                <img
                                    src="<?= company_e(company_safe_url((string) $image['src'])) ?>"
                                    alt="<?= company_e(company_value($image, 'alt')) ?>"
                                    loading="lazy"
                                >
                                <?php if (company_filled(company_value($image, 'credit'))): ?>
                                    <figcaption>
                                        <?php if (company_filled(company_value($image, 'credit_url'))): ?>
                                            <a href="<?= company_e(company_safe_url((string) $image['credit_url'])) ?>">
                                                <?= company_e($image['credit']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= company_e($image['credit']) ?>
                                        <?php endif; ?>
                                    </figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <p class="legal-ish"><strong>Visual disclaimer:</strong> <?= company_e($disclaimer) ?></p>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p><?= company_e(company_value($config, 'footer_note')) ?></p>
        <p>Corporate enthusiasm shown may not reflect typical working conditions.</p>
    </footer>
</body>
</html>
