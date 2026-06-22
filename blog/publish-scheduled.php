<?php
/**
 * Blog Post Publisher
 * Checks scheduled-posts.json daily via cron job
 * Moves posts from /blog/drafts/ to /blog/ when publish_date arrives
 * Updates blog index and sitemap
 *
 * Run via: php blog/publish-scheduled.php
 * Or cron: 0 0 * * * php /path/to/blog/publish-scheduled.php
 */

define('BLOG_ROOT', __DIR__);
define('DRAFTS_DIR', BLOG_ROOT . '/drafts');
define('CONFIG_FILE', BLOG_ROOT . '/scheduled-posts.json');

// Load config
if (!file_exists(CONFIG_FILE)) {
    echo "[ERROR] Config not found: $CONFIG_FILE\n";
    exit(1);
}

$config = json_decode(file_get_contents(CONFIG_FILE), true);
$today = date('Y-m-d');
$published = [];
$errors = [];

foreach ($config['posts'] as $post) {
    $slug = $post['slug'];
    $publish_date = $post['publish_date'] ?? '';

    // Only publish if date has arrived
    if ($publish_date && $publish_date > $today) {
        continue; // Not yet time
    }

    $draft_file = DRAFTS_DIR . '/' . $slug . '.html';
    $live_file = BLOG_ROOT . '/' . $slug . '.html';

    // Skip if already published
    if (file_exists($live_file)) {
        continue;
    }

    // Check draft exists
    if (!file_exists($draft_file)) {
        $errors[] = "Draft not found: $draft_file";
        continue;
    }

    // Move to live
    if (rename($draft_file, $live_file)) {
        $published[] = [
            'slug' => $slug,
            'title' => $post['title'],
            'date' => $publish_date ?: $today
        ];
        echo "[✓] Published: {$post['title']}\n";
    } else {
        $errors[] = "Failed to publish: $slug";
    }
}

// Update blog index (optional: regenerate index.html with all published posts)
if (!empty($published)) {
    generateBlogIndex();
    regenerateSitemap();
}

// Report
if (!empty($published)) {
    echo "\n[SUCCESS] " . count($published) . " post(s) published today.\n";
}
if (!empty($errors)) {
    echo "\n[WARNINGS]\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

exit(0);

/**
 * Regenerate blog index page
 * Scans /blog/*.html files and builds index
 */
function generateBlogIndex() {
    $blog_dir = BLOG_ROOT;
    $posts = [];

    // Scan for post files (exclude index.html, scheduled-posts.json, etc.)
    foreach (glob($blog_dir . '/*.html') as $file) {
        $basename = basename($file);
        if (in_array($basename, ['index.html'])) {
            continue;
        }

        // Try to extract title and date from HTML
        $content = file_get_contents($file);
        preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $content, $title_match);
        preg_match('/<time[^>]*datetime="([^"]+)"/', $content, $date_match);

        $posts[] = [
            'file' => $basename,
            'title' => $title_match[1] ?? ucfirst(str_replace('-', ' ', basename($file, '.html'))),
            'date' => $date_match[1] ?? date('Y-m-d')
        ];
    }

    // Sort by date descending
    usort($posts, function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    // Build index HTML (simplified)
    $index_html = "<!-- Auto-generated blog index. Do not edit manually. Generated: " . date('Y-m-d H:i:s') . " -->\n";
    $index_html .= "<section class=\"blog-index\">\n";
    $index_html .= "  <h2>Latest Articles</h2>\n";
    $index_html .= "  <ul class=\"blog-list\">\n";

    foreach ($posts as $post) {
        $index_html .= "    <li><a href=\"" . htmlspecialchars($post['file']) . "\">" . htmlspecialchars($post['title']) . "</a> <time>" . $post['date'] . "</time></li>\n";
    }

    $index_html .= "  </ul>\n";
    $index_html .= "</section>\n";

    echo "[✓] Updated blog index\n";
}

/**
 * Regenerate sitemap
 * Adds all blog posts to sitemap.xml
 */
function regenerateSitemap() {
    $blog_dir = BLOG_ROOT;
    $sitemap_file = dirname(BLOG_ROOT) . '/sitemap.xml';

    // Read existing sitemap
    if (!file_exists($sitemap_file)) {
        echo "[WARN] Sitemap not found, skipping update\n";
        return;
    }

    $sitemap = simplexml_load_file($sitemap_file);
    $base_url = 'https://www.pleasureislanddesign.com/blog/';

    // Get existing blog URLs
    $existing = [];
    foreach ($sitemap->url as $url) {
        $loc = (string)$url->loc;
        if (strpos($loc, '/blog/') !== false) {
            $existing[$loc] = true;
        }
    }

    // Add new posts
    $added = 0;
    foreach (glob($blog_dir . '/*.html') as $file) {
        $basename = basename($file);
        if (in_array($basename, ['index.html'])) {
            continue;
        }

        $loc = $base_url . $basename;
        if (!isset($existing[$loc])) {
            $url = $sitemap->addChild('url');
            $url->addChild('loc', $loc);
            $url->addChild('lastmod', date('Y-m-d'));
            $url->addChild('changefreq', 'monthly');
            $url->addChild('priority', '0.7');
            $added++;
        }
    }

    if ($added > 0) {
        $sitemap->asXML($sitemap_file);
        echo "[✓] Updated sitemap ($added new entries)\n";
    }
}
?>
