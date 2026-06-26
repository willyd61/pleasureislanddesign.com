<?php
/**
 * Blog Post Publisher (Production-Ready)
 *
 * Scheduled Publishing System
 * - Reads scheduled-posts.json
 * - Publishes posts when publish_date arrives
 * - Auto-updates blog index & sitemap
 * - Comprehensive logging
 * - Rollback capability
 *
 * Usage:
 *   Manual: php blog/publish-scheduled.php
 *   Cron: 0 0 * * * php /path/to/blog/publish-scheduled.php
 */

require_once dirname(__DIR__) . '/forms/lib/form-helpers.php';

define('BLOG_ROOT', __DIR__);
define('CONFIG_FILE', BLOG_ROOT . '/scheduled-posts.json');
define('DRAFTS_DIR', BLOG_ROOT . '/drafts');
define('LOG_FILE', dirname(BLOG_ROOT) . '/.logs/blog-publisher.log');
define('BACKUP_DIR', dirname(BLOG_ROOT) . '/.backups');

// Ensure directories exist
foreach ([dirname(LOG_FILE), BACKUP_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

$start_time = microtime(true);
$stats = ['published' => 0, 'skipped' => 0, 'errors' => 0];

echo "[Blog Publisher] Starting...\n";
log_event('PUBLISHER_START');

// Load config
if (!file_exists(CONFIG_FILE)) {
    echo "[ERROR] Config not found: " . CONFIG_FILE . "\n";
    log_event('CONFIG_NOT_FOUND', ['file' => CONFIG_FILE]);
    exit(1);
}

$config = json_decode(file_get_contents(CONFIG_FILE), true);
if (!$config || !isset($config['posts'])) {
    echo "[ERROR] Invalid config format\n";
    log_event('INVALID_CONFIG');
    exit(1);
}

$today = gmdate('Y-m-d');
$published_posts = [];
$errors = [];

// Process posts
foreach ($config['posts'] as $post) {
    $slug = $post['slug'] ?? '';
    $title = $post['title'] ?? 'Untitled';
    $publish_date = $post['publish_date'] ?? '';

    // Validate post entry
    if (!$slug) {
        echo "[SKIP] No slug for post: $title\n";
        $stats['skipped']++;
        continue;
    }

    // Check if date has arrived
    if ($publish_date && $publish_date > $today) {
        echo "[SCHEDULED] {$title} (publishes on {$publish_date})\n";
        $stats['skipped']++;
        continue;
    }

    $draft_file = DRAFTS_DIR . '/' . pid_sanitize_slug($slug) . '.html';
    $live_file = BLOG_ROOT . '/' . pid_sanitize_slug($slug) . '.html';

    // Check if already published
    if (file_exists($live_file)) {
        echo "[EXISTS] {$title} already published\n";
        $stats['skipped']++;
        continue;
    }

    // Check draft exists
    if (!file_exists($draft_file)) {
        echo "[ERROR] Draft not found: $draft_file\n";
        $errors[] = "Draft missing for '{$title}'";
        $stats['errors']++;
        log_event('DRAFT_NOT_FOUND', ['slug' => $slug, 'file' => $draft_file]);
        continue;
    }

    // Validate HTML
    $content = file_get_contents($draft_file);
    if (!pid_validate_post_html($content)) {
        echo "[ERROR] Invalid HTML in draft: {$draft_file}\n";
        $errors[] = "Invalid HTML in '{$title}'";
        $stats['errors']++;
        log_event('INVALID_HTML', ['slug' => $slug]);
        continue;
    }

    // Backup before publishing
    $backup_file = BACKUP_DIR . '/' . pid_sanitize_slug($slug) . '_' . date('YmdHis') . '.html.bak';
    copy($draft_file, $backup_file);

    // Move to live
    if (rename($draft_file, $live_file)) {
        echo "[✓] Published: {$title}\n";
        $published_posts[] = [
            'slug' => $slug,
            'title' => $title,
            'publish_date' => $publish_date ?: $today,
            'backup' => $backup_file
        ];
        $stats['published']++;
        log_event('POST_PUBLISHED', ['slug' => $slug, 'title' => $title]);
    } else {
        echo "[ERROR] Failed to publish: {$title}\n";
        $errors[] = "Failed to move draft for '{$title}'";
        $stats['errors']++;
        log_event('PUBLISH_FAILED', ['slug' => $slug]);
    }
}

// Update blog assets if anything published
if ($stats['published'] > 0) {
    echo "\n[UPDATE] Regenerating blog index...\n";
    if (regenerate_blog_index()) {
        echo "[✓] Blog index updated\n";
        log_event('INDEX_UPDATED');
    } else {
        echo "[WARN] Failed to update index\n";
    }

    echo "[UPDATE] Regenerating sitemap...\n";
    if (regenerate_sitemap()) {
        echo "[✓] Sitemap updated\n";
        log_event('SITEMAP_UPDATED');
    } else {
        echo "[WARN] Failed to update sitemap\n";
    }
}

// Final report
$duration = round(microtime(true) - $start_time, 2);
echo "\n=== SUMMARY ===\n";
echo "Published: {$stats['published']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "Duration: {$duration}s\n";

if (!empty($errors)) {
    echo "\n[WARNINGS]\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

log_event('PUBLISHER_END', $stats);
exit($stats['errors'] > 0 ? 1 : 0);

// ===== HELPER FUNCTIONS =====

function regenerate_blog_index() {
    $blog_dir = BLOG_ROOT;
    $posts = [];
    $index_file = $blog_dir . '/index.html';

    // Scan for post files
    $files = @scandir($blog_dir);
    if ($files === false) {
        return false;
    }

    foreach ($files as $file) {
        if (in_array($file, ['.', '..', 'index.html', 'drafts', 'scheduled-posts.json', 'publish-scheduled.php'])) {
            continue;
        }
        if (!preg_match('/\.html$/', $file)) {
            continue;
        }

        $path = $blog_dir . '/' . $file;
        $content = @file_get_contents($path);
        if ($content === false) {
            continue;
        }

        // Extract metadata
        $title = 'Untitled';
        $date = date('Y-m-d');

        preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $content, $title_match);
        if (!empty($title_match[1])) {
            $title = htmlspecialchars(trim($title_match[1]));
        }

        preg_match('/<time[^>]*datetime="([^"]+)"/', $content, $date_match);
        if (!empty($date_match[1])) {
            $date = htmlspecialchars(trim($date_match[1]));
        }

        $posts[] = [
            'file' => $file,
            'title' => $title,
            'date' => $date
        ];
    }

    // Sort by date descending
    usort($posts, function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    // Build index HTML
    $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
    $html .= "  <meta charset=\"UTF-8\">\n";
    $html .= "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    $html .= "  <title>Pleasure Island Design - Blog Posts</title>\n";
    $html .= "</head>\n<body>\n<h1>Blog Posts</h1>\n<ul>\n";

    foreach ($posts as $post) {
        $url = htmlspecialchars($post['file']);
        $title = htmlspecialchars($post['title']);
        $date = htmlspecialchars($post['date']);
        $html .= "  <li><a href=\"{$url}\">{$title}</a> <time>{$date}</time></li>\n";
    }

    $html .= "</ul>\n</body>\n</html>\n";

    // Write index file
    if (file_put_contents($index_file, $html) === false) {
        return false;
    }

    return true;
}

function regenerate_sitemap() {
    $sitemap_file = dirname(BLOG_ROOT) . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return false;
    }

    $sitemap = @simplexml_load_file($sitemap_file);
    if ($sitemap === false) {
        return false;
    }

    $base_url = 'https://www.pleasureislanddesign.com/blog/';
    $blog_dir = BLOG_ROOT;

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
    $files = @scandir($blog_dir);
    if ($files === false) {
        return false;
    }

    foreach ($files as $file) {
        if (!preg_match('/^[a-z0-9\-]+\.html$/', $file)) {
            continue;
        }

        $loc = $base_url . $file;
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
    }

    return true;
}

function log_event($event, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $entry = "{$timestamp} | {$event} | " . json_encode($data) . "\n";
    @error_log($entry, 3, LOG_FILE);
}
?>
