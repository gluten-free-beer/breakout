<?php
function directory_read_json($path, $fallback = array())
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function directory_value($source, $key, $fallback = '')
{
    return is_array($source) && array_key_exists($key, $source)
        ? $source[$key]
        : $fallback;
}

function directory_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function directory_filled($value)
{
    return is_array($value) ? count($value) > 0 : trim((string) $value) !== '';
}

function directory_slugify($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$root = __DIR__;
$config = directory_read_json($root . '/config.json');
$person = directory_read_json($root . '/data/me.json');
$companyData = directory_read_json($root . '/data/company.json');
$companies = isset($companyData[0]) && is_array($companyData[0]) ? $companyData : array($companyData);

$siteName = directory_value($config, 'site_name', 'BreakOut');
$logo = directory_value($config, 'logo');
$accentValue = directory_value($config, 'accent');
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accentValue) ? $accentValue : '#3ed5e2';
$personSlug = directory_slugify(directory_value($person, 'slug', directory_value($person, 'name', 'person')));
$repositoryUrl = directory_value($config, 'repository_url');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A self-hosted directory of professional people and company records.">
    <title>Directory — <?= directory_e($siteName) ?></title>
    <link rel="alternate" type="application/rss+xml" title="<?= directory_e($siteName) ?> updates" href="./rss.xml">
    <link rel="stylesheet" href="./assets/breakout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>:root { --accent: <?= directory_e($accent) ?>; }</style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="./" aria-label="<?= directory_e($siteName) ?> directory">
            <?php if (directory_filled($logo)): ?>
                <img src="<?= directory_e($logo) ?>" alt="<?= directory_e($siteName) ?>">
            <?php else: ?>
                <span class="brand__mark" aria-hidden="true">B/O</span>
                <span><?= directory_e($siteName) ?></span>
            <?php endif; ?>
        </a>
        <p><?= directory_e(directory_value($config, 'page_title_suffix', 'Welcome to our professional records')) ?></p>
    </header>

    <main class="directory-main">
        <div class="record-bar" aria-label="Directory information">
            <span>PUBLIC RECORD DIRECTORY</span>
            <span>SELF-VERIFIED <i class="fa-solid fa-v" aria-hidden="true"></i></span>
            <span><?= directory_e(directory_value($config, 'directory_network_note', 'NETWORK OR NOT')) ?></span>
        </div>

        <section class="directory-intro" aria-labelledby="directory-title">
            <div class="directory-intro__copy">
                <p class="field-label"><?= directory_e(directory_value($config, 'directory_eyebrow', 'Unlinked')) ?></p>
                <h1 id="directory-title"><?= directory_e(directory_value($config, 'directory_heading', 'Free to be discovered.')) ?></h1>
                <p><?= directory_e(directory_value($config, 'directory_intro', 'Find the records below.')) ?></p>
            </div>
            <img
                class="directory-intro__art"
                src="./assets/breakout-team.png"
                alt="Two people working independently at home with a dog nearby"
            >
        </section>

        <section class="self-host-row" aria-labelledby="self-host-title">
            <div>
                <h2 id="self-host-title">BreakOut together?</h2>
            </div>
            <div>
                <p class="self-host-row__line">Host it yourself.</p>
                <a
                    class="repo-status"
                    href="<?= directory_e($repositoryUrl) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >VIEW PUBLIC REPOSITORY <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        <div class="directory-grid">
            <section class="directory-section" aria-labelledby="persons-title">
                <p class="section-number">PERSONS</p>
                <h2 id="persons-title">Persons</h2>
                <a class="directory-card directory-card--person" href="./<?= directory_e(rawurlencode($personSlug)) ?>">
                    <?php if (directory_filled(directory_value($person, 'portrait'))): ?>
                        <img src="<?= directory_e($person['portrait']) ?>" alt="">
                    <?php endif; ?>
                    <span>
                        <strong><?= directory_e(directory_value($person, 'name', 'Profile')) ?></strong>
                        <small><?= directory_e(directory_value($person, 'tagline')) ?></small>
                    </span>
                    <b aria-hidden="true">→</b>
                </a>
            </section>

            <section class="directory-section" aria-labelledby="companies-title">
                <p class="section-number">COMPANIES</p>
                <h2 id="companies-title">Company Profiles</h2>
                <?php foreach ($companies as $company): ?>
                    <?php if (is_array($company) && directory_filled(directory_value($company, 'name'))): ?>
                        <?php $companySlug = directory_slugify($company['name']); ?>
                        <a class="directory-card" href="./<?= directory_e(rawurlencode($companySlug)) ?>">
                            <?php if (directory_filled(directory_value($company, 'logo'))): ?>
                                <img src="<?= directory_e($company['logo']) ?>" alt="">
                            <?php endif; ?>
                            <span>
                                <strong><?= directory_e($company['name']) ?></strong>
                                <small><?= directory_e(directory_value($company, 'tagline')) ?></small>
                            </span>
                            <b aria-hidden="true">→</b>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <footer>
        <p><?= directory_e(directory_value($config, 'footer_note')) ?></p>
        <p>Records listed without algorithmic ranking.</p>
    </footer>
</body>
</html>
