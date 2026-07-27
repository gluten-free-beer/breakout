# BreakOut

A small, self-hosted directory of professional records for people and
organizations that would rather publish their own URL than maintain a LinkedIn
page.

BreakOut uses plain PHP, JSON, CSS, and Apache rewrites. There is no build
process, database, account system, package manager, engagement metric, or
algorithmically inferred endorsement. Font Awesome icons are loaded from its
public CDN.

## Quick start

Create the live configuration and records from the public examples:

```sh
cp config.example.json config.json
cp data/me.example.json data/me.json
cp data/company.example.json data/company.json
cp data/connections.example.json data/connections.json
cp data/media-library.example.json data/media-library.json
```

Edit those new files with your information. They are intentionally ignored by
Git, so publishing a customized installation does not publish its records.

## Routes

With the example records and an installation at `/breakout`, the routes are:

- `/breakout/` — directory
- `/breakout/your-name` — person record generated from `me.json`
- `/breakout/example-company` — company record generated from its name
- `/breakout/rss.xml` — Record Log RSS feed

Every person and company record includes a **Print Record** button. It invokes
the browser print dialog and removes itself from the printed copy.

## Configuration

Copy `config.example.json` to `config.json`. Site-wide settings and directory
copy live in the new file:

```json
{
  "site_name": "BreakOut",
  "site_url": "https://example.com/breakout",
  "page_title_suffix": "Welcome to our professional records",
  "directory_eyebrow": "Professional Network, but not very linked",
  "directory_heading": "Free to be discovered.",
  "directory_intro": "Find the records below.",
  "directory_network_note": "NETWORK OR NOT",
  "repository_url": "https://github.com/gluten-free-beer/breakout",
  "logo": "./assets/breakout-logo.svg",
  "accent": "#3ed5e2",
  "footer_note": "This profile has not been optimized for recruiter discovery."
}
```

Set `site_url` to the final public location so profile and media URLs in the RSS
feed are correct.

## Person record

The person record lives in `data/me.json`. Its main fields are:

```json
{
  "slug": "your-name",
  "name": "Your Name",
  "tagline": "Professional Person | Building Something",
  "location": "Somewhere",
  "portrait": "",
  "intro": [
    "<p>Building a few things.</p><p>Learning more.</p>"
  ],
  "overworking": true,
  "rss": true
}
```

Optional values can be empty strings or empty lists. Empty sections are not
rendered. Set `overworking` to `true` to display the `#overworking` ribbon and
`false` to hide it.

### Links

Profile links use label-and-URL dictionaries:

```json
{
  "label": "Working Notes",
  "url": "https://example.com/notes"
}
```

`http`, `https`, relative, and `mailto` links are accepted.

### Experience and education

Experience and education accept either simple strings:

```json
"experience": ["finance", "fashion", "art advisory"]
```

or structured records:

```json
{
  "title": "Founder",
  "subtitle": "Company name",
  "period": "2022—present",
  "summary": "A short, readable description.",
  "url": "https://example.com"
}
```

### Projects

Projects appear under **Selected Output**:

```json
{
  "label": "Example Company",
  "status": "Active",
  "years": "2026—present",
  "description": "A project described without algorithmic embellishment.",
  "url": "https://example.com",
  "company": "example-company"
}
```

`status` is displayed as the entry subtitle and `years` as its period. When
`company` matches the slug generated from a record in `data/company.json`, the
project receives a **Company record** button.

### Record Log and RSS

The `updates` list supplies the **Record Log** timeline:

```json
{
  "date": "2026-01-01",
  "text": "<p><em>Hello World.</em></p>",
  "image": "",
  "image_alt": ""
}
```

Update and intro HTML is restricted to paragraphs, emphasis, strong text, and
line breaks. Images are optional and render responsively without being cropped.

When `rss` is `true`, the profile advertises and links to the RSS feed. Update
images are included as RSS enclosures and Media RSS content.

## Company records

Company records are the objects in `data/company.json`. A flat URL is generated
from each company name: `CanCanCan` becomes `/linkedin/cancancan`. The legacy
route `/linkedin/company.php?id=cancancan` remains available.

A complete record can contain:

```json
{
  "name": "Example Company",
  "logo": "./assets/example.logo.png",
  "tagline": "What the company says in one line.",
  "industry": "Extremely Serious Research",
  "location": "New York, NY",
  "url": "https://example.com",
  "hero_youtube": "",
  "summary": "What the company says it does.",
  "fundraising": {
    "round": "Pre-Seed",
    "target": "$900K",
    "status": "Open"
  },
  "milestones": {
    "2026": ["Launched something."],
    "next": ["Launch something else."]
  },
  "team": [
    {
      "role": "Principal",
      "name": "Your Name",
      "profile": "your-name"
    }
  ]
}
```

Team `profile` values link back to the matching person route.

### Social links and video

The optional `social` dictionary maps platform names or abbreviations to public
URLs:

```json
{
  "social": {
    "X": "https://x.com/example",
    "IG": "https://instagram.com/example"
  }
}
```

Known platforms receive their Font Awesome icon; unknown names receive a
generic link icon.

Set `hero_youtube` to a YouTube URL or eleven-character video ID to display a
responsive, privacy-enhanced `youtube-nocookie.com` embed below the heading.
Standard watch, short, live, share, and embed URLs are accepted.

### Featured system

The optional `featured` dictionary creates the turquoise feature block:

```json
{
  "featured": {
    "name": "Record Mascot",
    "role": "Special assistant",
    "image": "./assets/mascot.png",
    "summary": "A short description."
  }
}
```

### Representative imagery

Company pages render at most three media entries:

```json
{
  "media_status": "synthetic_representative_humans",
  "media_disclaimer": "Synthetic representative imagery. No actual employees were photographed, consulted, or inconvenienced. Actual workplaces, personnel, products, services, and apparent levels of enthusiasm may vary.",
  "media": [
    {
      "src": "./assets/company/company.synthetic.webp",
      "alt": "People allegedly working"
    }
  ]
}
```

Keep creator credits with each image. Unassigned assets and their credits can
be catalogued in `data/media-library.json`.

## Assets

Place images under `assets/` and reference them with paths relative to the
BreakOut folder:

- brand and record artwork: `assets/`
- company photography: `assets/company/`
- Record Log images: `assets/updates/`

WebP is recommended for photographic portraits and other large images.
See [`ASSETS.md`](ASSETS.md) for the license covering the artwork included with
the public template.

## Keeping records private from Git

The live configuration, JSON records, generated HTML, and installation-specific
media directories are listed in `.gitignore`. The repository contains only
sanitized `*.example.json` records.

Public-facing information is not necessarily secret, but Git preserves old
versions. Keep live records out of the repository unless you deliberately want
their history published too.

## Apache deployment

Upload this directory to the desired public folder and include its `.htaccess`
file. The server needs:

- PHP 5.4 or newer
- Apache `mod_rewrite`
- `.htaccess` overrides enabled for rewrite rules

The local `.htaccess` disables directory listings, preserves real files and
folders, maps `rss.xml`, and sends flat person and company slugs through the
record router.

If this folder sits inside an older site with its own catch-all rewrite rules,
the parent server configuration must allow this directory’s `.htaccess` to take
control before the parent catch-all handles the request.

## Local preview

From this folder:

```sh
php -S 127.0.0.1:8080
```

Then open:

- `http://127.0.0.1:8080/`
- `http://127.0.0.1:8080/profile.php`
- `http://127.0.0.1:8080/company.php?id=poqrr`
- `http://127.0.0.1:8080/rss.php`

PHP’s built-in server does not read `.htaccess`, so the clean routes are
available when served through Apache, not through this basic preview command.

## Connections

`data/connections.json` is reserved for the future public registry—the part
where BreakOut can become a network without requiring everyone to live on one
platform. It is currently not rendered.

## Credits

BreakOut was built by Robin in collaboration with Codex (Coco), who contributed to implementation, structure, and debugging. Robin assumes full responsibility for the satire and any resulting professional discomfort. Coco is innocent.
Special thanks to DALL·E, who was absolutely cooking. 🔥🐸
