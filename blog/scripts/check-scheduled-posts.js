/**
 * Check Scheduled Blog Posts
 * Verifies if any posts should be published today
 * Used by GitHub Actions workflow to determine if publisher should run
 */

const fs = require('fs');
const path = require('path');

const CONFIG_FILE = path.join(__dirname, '../scheduled-posts.json');
const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

try {
  const config = JSON.parse(fs.readFileSync(CONFIG_FILE, 'utf8'));

  if (!config.posts || !Array.isArray(config.posts)) {
    console.log('No posts configuration found');
    process.exit(0);
  }

  const postsToPublish = config.posts.filter(post => {
    const publishDate = post.publish_date || '';
    return publishDate && publishDate <= today && !fs.existsSync(
      path.join(__dirname, `../published/${post.slug}.html`)
    );
  });

  if (postsToPublish.length > 0) {
    console.log(`Found ${postsToPublish.length} post(s) to publish today:`);
    postsToPublish.forEach(post => {
      console.log(`  - ${post.title} (${post.slug})`);
    });
    process.exit(0);
  } else {
    console.log('No posts scheduled for today');
    process.exit(0);
  }
} catch (err) {
  console.error('Error checking scheduled posts:', err.message);
  process.exit(1);
}
