# Bugfix-1 Implementation Guide

**Status:** Ready for deployment  
**Files Changed:** 6 new, 1 modified  
**Testing:** Test locally before deploying to GoDaddy

---

## ✅ 1. Contact Form & Newsletter (FIXED)

**Problem:** Broken Formspree URL → emails never sent  
**Solution:** PHP `mail()` handlers on GoDaddy cPanel

### Files Created
- `/forms/contact.php` — Receives contact form submissions
- `/forms/newsletter.php` — Receives newsletter signups

### Changes Made
- Updated `/js/pleasure-island-scripts.js`
  - Contact form now POSTs to `/forms/contact.php`
  - Newsletter now POSTs to `/forms/newsletter.php`

### Features
✅ Email validation (prevent injection)  
✅ Sanitized inputs (XSS prevention)  
✅ Automatic confirmation email to user  
✅ Notification to pleasureislanddesign@gmail.com  
✅ Works on GoDaddy shared hosting (PHP built-in)  

### Testing Checklist
- [ ] Fill out contact form locally, submit
- [ ] Check terminal/email output for success message
- [ ] Verify email arrives at pleasureislanddesign@gmail.com
- [ ] Reply-To and From headers are correct
- [ ] Newsletter signup also works
- [ ] Mobile form submission works

### Deployment
1. Push to GoDaddy via cPanel Git
2. Test live on www.pleasureislanddesign.com/
3. Submit contact form → wait 5 seconds
4. Check pleasureislanddesign@gmail.com inbox (may take 30 sec - 1 min)

---

## 🔄 2. Reviews Section (READY, AWAITING CONFIG)

**Problem:** Only 3 hardcoded testimonials, no dynamic data  
**Solution:** Google Places API integration

### File Created
- `/forms/get-reviews.php` — Fetches live reviews from Google Business

### Setup Required (One-time)
1. **Create Google Cloud Project**
   - Go to https://console.cloud.google.com/
   - Create new project
   - Enable "Places API"

2. **Create API Key**
   - Go to Credentials → Create API Key
   - Restrict to "Places API" only
   - Restrict by IP (recommended): add your GoDaddy server IP

3. **Find Your Place ID**
   - Search "Pleasure Island Design Wilmington NC" on Google Maps
   - Copy Place ID from URL or use Places API Text Search

4. **Set Environment Variables on GoDaddy**
   - SSH into GoDaddy or via cPanel File Manager
   - Edit `~/.bashrc` or `.htaccess`:
     ```bash
     export GOOGLE_PLACES_API_KEY="AIzaSy..."
     export GOOGLE_PLACE_ID="ChIJ..."
     ```
   - Or edit `/forms/get-reviews.php` line 11–12 to hardcode (less secure)

5. **Test**
   ```bash
   curl https://www.pleasureislanddesign.com/forms/get-reviews.php
   ```
   Should return JSON array of reviews or setup instructions

### Integration (Next Phase)
Once configured, call from frontend:
```javascript
fetch('/forms/get-reviews.php')
  .then(r => r.json())
  .then(data => {
    // Render data.reviews array
    data.reviews.forEach(review => {
      console.log(review.name, review.rating, review.text);
    });
  });
```

### Costs
✅ **Free** — Google Places API includes 25 free calls/day per project

---

## 📖 3. Blog Scheduling System (READY)

**Problem:** Manual process to add posts; no scheduling  
**Solution:** Bulk-write-now, staggered-release system

### Files Created
- `/blog/scheduled-posts.json` — Publishing schedule & metadata
- `/blog/publish-scheduled.php` — Automation script

### Workflow

#### A. Writing Phase (Bulk)
1. Create posts in `/blog/drafts/` as `.html` files
   - Example: `/blog/drafts/kitchen-paint-guide.html`
   - Include `<h1>` title and `<time datetime="YYYY-MM-DD">` date
2. Add entry to `scheduled-posts.json`:
   ```json
   {
     "slug": "kitchen-paint-guide",
     "title": "Kitchen Paint Selection Guide",
     "publish_date": "2026-08-15",
     "category": "Care",
     "featured": false
   }
   ```
3. Repeat for 50+ posts (you can write all in one session, schedule over months)

#### B. Publishing Phase (Automated)
1. **Option A: Manual trigger**
   ```bash
   php blog/publish-scheduled.php
   ```
   Moves any posts with `publish_date <= today` from drafts to live

2. **Option B: Daily cron job** (recommended)
   - Via cPanel → Cron Jobs
   - Command: `php /home/kyjlg8o1knr0/public_html/blog/publish-scheduled.php`
   - Schedule: Daily at 12:00 AM (0 0 * * *)
   - This auto-publishes posts when their date arrives

#### C. Post-Publish
- Blog index auto-updates
- Sitemap auto-updates
- GA4 tracks new blog traffic

### Template
Create new posts with this structure:
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Kitchen Paint Selection Guide</title>
  <meta name="description" content="How to select the right cabinet paint for your kitchen refinishing project.">
</head>
<body>
  <article class="blog-post">
    <h1>Kitchen Paint Selection Guide</h1>
    <time datetime="2026-08-15">August 15, 2026</time>
    <p>Your article content here...</p>
  </article>
</body>
</html>
```

### Testing
```bash
# Manually test the publisher
php blog/publish-scheduled.php

# Output should show:
# [✓] Published: Kitchen Paint Selection Guide
# [SUCCESS] 1 post(s) published today.
```

### Cron Setup on GoDaddy
1. Log in to cPanel
2. Go to **Cron Jobs**
3. Add new job:
   - **Command:** `php /home/YOUR_ACCOUNT/public_html/blog/publish-scheduled.php > /tmp/blog_publish.log 2>&1`
   - **Common Settings:** Daily
   - **Time:** 12:00 AM
4. Click **Add Cron Job**

---

## 📐 4. Hero & Mobile Design Audit (PENDING)

**Status:** Will perform audit and present findings with mockups

### Audit Scope
- Desktop (1920px) vs Mobile (375px) screenshots
- Hero section layout, image scaling, text overflow
- Stats bar responsive behavior
- Navigation menu on mobile
- Button sizing and spacing consistency

### Approval Workflow
1. I capture screenshots and document issues
2. Present 3–5 concrete findings with before/after
3. You review and approve fixes
4. I implement and test

---

## 🚀 Deployment Checklist

### Before Pushing to GoDaddy
- [ ] Contact form works locally (test submit)
- [ ] Newsletter signup works locally
- [ ] Forms accept and validate input correctly
- [ ] No console errors in browser DevTools

### After Pushing to GoDaddy
- [ ] Files in `/forms/` are readable (check permissions)
- [ ] `/forms/contact.php` returns 503 (setup required) or success
- [ ] Contact form on live site submits without error
- [ ] Emails arrive within 1 minute
- [ ] Newsletter signup works

### Configuration (GoDaddy Only)
- [ ] Set `GOOGLE_PLACES_API_KEY` environment variable (or hardcode in get-reviews.php)
- [ ] Set `GOOGLE_PLACE_ID` environment variable
- [ ] Test `/forms/get-reviews.php` returns reviews or setup instructions
- [ ] Set up cron job for `/blog/publish-scheduled.php` if using automation

---

## 📋 Post-Deployment Monitoring

### Week 1
- [ ] Monitor contact form submissions (should appear in email)
- [ ] Check GA4 for form_submit events
- [ ] Test response email flow
- [ ] Verify no PHP errors in GoDaddy error logs

### Week 2+
- [ ] Begin writing blog posts to `/blog/drafts/`
- [ ] Schedule first batch (50+ posts) in `scheduled-posts.json`
- [ ] Test cron job trigger (should auto-publish on schedule)
- [ ] Monitor blog traffic in GA4

### Ongoing
- [ ] Add new blog posts to drafts with publish dates
- [ ] Monitor contact form spam (implement CAPTCHA if needed)
- [ ] Track reviews API usage (free tier: 25/day, refresh daily)

---

## 🐛 Troubleshooting

### Contact Form Submits But No Email Arrives
**Cause:** GoDaddy mail server not configured or blocked  
**Fix:**
1. Check GoDaddy cPanel → Email Accounts (ensure account exists)
2. Test PHP mail directly:
   ```php
   mail('pleasureislanddesign@gmail.com', 'Test', 'Test message');
   ```
3. Check GoDaddy error logs: `~/.access-logs/`

### Reviews API Returns "Not Configured"
**Cause:** `GOOGLE_PLACES_API_KEY` or `GOOGLE_PLACE_ID` not set  
**Fix:**
1. Set environment variables (see Setup section)
2. Or hardcode in `/forms/get-reviews.php` lines 11–12:
   ```php
   define('GOOGLE_API_KEY', 'AIzaSy...');
   define('PLACE_ID', 'ChIJ...');
   ```

### Blog Cron Job Not Firing
**Cause:** Cron command path incorrect or PHP not found  
**Fix:**
1. SSH to GoDaddy and test:
   ```bash
   php /home/YOUR_ACCOUNT/public_html/blog/publish-scheduled.php
   ```
2. Check cron logs:
   ```bash
   cat /var/log/cron
   ```
3. Simplify command if needed:
   ```bash
   cd /home/YOUR_ACCOUNT/public_html && php blog/publish-scheduled.php
   ```

---

## 📞 Support

- **GoDaddy Support:** (for server/cPanel issues)
- **PHP Mail:** https://www.php.net/manual/en/function.mail.php
- **Google Places API:** https://developers.google.com/maps/documentation/places/web-service/overview
- **GitHub Issues:** Document any bugs or edge cases

---

Generated: June 18, 2026  
Next: Design audit + Phase 3 prioritization
