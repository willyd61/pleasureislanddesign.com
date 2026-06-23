# Documentation Index

**Last Updated:** June 18, 2026  
**Project Status:** Production (live), Bugfix-1 in progress

---

## 📌 Active Documentation (Current Use)

### Deployment & Setup
- **[BUGFIX_IMPLEMENTATION.md](BUGFIX_IMPLEMENTATION.md)** ⭐ START HERE
  - Contact form, newsletter, reviews API, blog scheduler
  - Deployment steps, testing procedures, troubleshooting

- **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** ⭐ DEPLOYMENT GUIDE
  - Complete pre/post-deployment verification
  - Step-by-step deployment to GoDaddy
  - Monitoring checklist and sign-off

- **[GOOGLE_PLACES_API_SETUP.md](GOOGLE_PLACES_API_SETUP.md)** ⭐ GOOGLE API SETUP
  - Step-by-step Google Places API configuration
  - API key generation, Place ID lookup
  - Troubleshooting and cost management

### Project Management
- **[BUGFIX_AND_ROADMAP.md](BUGFIX_AND_ROADMAP.md)**
  - Detailed Phase 3 backlog (swag store, gallery, SEO, wiki, bot, blog)
  - Timeline, effort estimates, success metrics

- **[PROJECT_BACKLOG.md](PROJECT_BACKLOG.md)**
  - Product features and enhancements
  - User stories, acceptance criteria

### Code & Quality
- **[tests/unit-tests.php](tests/unit-tests.php)**
  - 24 automated tests (100% passing)
  - Run: `php tests/unit-tests.php`

- **[README.md](README.md)**
  - Project overview, current status, local setup
  - Prerequisites, installation, development

---

## 📚 Reference Documentation

### Security & Compliance
- **[SECURITY.md](SECURITY.md)**
  - Security policy, vulnerability reporting
  - Data protection, GDPR compliance

- **[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)**
  - Community guidelines

### Contributing
- **[CONTRIBUTING.md](CONTRIBUTING.md)**
  - Development workflow, PR process

### Changelog
- **[CHANGELOG.md](CHANGELOG.md)**
  - Version history, release notes

---

## 📂 Archived Documentation (Historical Reference)

These documents were used for earlier phases. Current information supersedes them.

- **DEPLOYMENT_STATUS.md** (May 2026)
  - Old go-live status. See DEPLOYMENT_CHECKLIST.md instead.

- **DNS_AND_CUTOVER.md** (May 2026)
  - Old cutover plan. See DEPLOYMENT_CHECKLIST.md instead.

- **GODADDY_DEPLOYMENT.md** (May 2026)
  - Old GoDaddy runbook. See BUGFIX_IMPLEMENTATION.md instead.

- **GO_LIVE_CHECKLIST.md** (May 2026)
  - Old go-live plan. See DEPLOYMENT_CHECKLIST.md instead.

- **HANDOFF_TO_COWORK.md** (June 4, 2026)
  - Session handoff. Deployment completed (see deployment docs above).

- **DEPLOYMENT.md**
  - General deployment guide. See BUGFIX_IMPLEMENTATION.md instead.

- **.CLAUDE_MEMORY.md**
  - Internal session notes. For reference only.

---

## 🗺️ Quick Navigation

### "I want to..."

**Deploy the bugfix-1 branch:**
→ Read [BUGFIX_IMPLEMENTATION.md](BUGFIX_IMPLEMENTATION.md), then [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)

**Set up Google Places API:**
→ Follow [GOOGLE_PLACES_API_SETUP.md](GOOGLE_PLACES_API_SETUP.md)

**Test the code locally:**
→ Run `php tests/unit-tests.php`

**Check project roadmap:**
→ See [BUGFIX_AND_ROADMAP.md](BUGFIX_AND_ROADMAP.md)

**Report a security issue:**
→ Read [SECURITY.md](SECURITY.md)

**Contribute code:**
→ Follow [CONTRIBUTING.md](CONTRIBUTING.md)

**Check recent changes:**
→ See [CHANGELOG.md](CHANGELOG.md)

---

## 📊 File Structure

```
.
├── index.html                    # Main website
├── 404.html                      # Error page
├── styles.css                    # Global styles
├── forms/
│   ├── contact.php              # Contact form handler (NEW - Enhanced)
│   ├── newsletter.php           # Newsletter handler (NEW - Enhanced)
│   └── get-reviews.php          # Reviews API (NEW - Enhanced)
├── blog/
│   ├── publish-scheduled.php    # Blog scheduler (NEW - Enhanced)
│   ├── scheduled-posts.json     # Publishing config (NEW)
│   └── drafts/                  # Draft posts (NEW)
├── gallery/                      # Before/after images
├── js/
│   └── pleasure-island-scripts.js  # Form handlers updated
├── tests/
│   └── unit-tests.php           # Unit tests (NEW - 24 passing)
├── .logs/                        # Auto-generated logs (runtime)
├── .data/                        # Auto-generated data (runtime)
├── .backups/                     # Auto-generated backups (runtime)
└── docs/                         # Documentation (📚 all active docs in root)
```

---

## ✅ Checklist: Before Deploying

- [ ] Read BUGFIX_IMPLEMENTATION.md
- [ ] Read DEPLOYMENT_CHECKLIST.md
- [ ] Run `php tests/unit-tests.php` (all passing)
- [ ] Configure Google Places API (GOOGLE_PLACES_API_SETUP.md)
- [ ] Test forms locally
- [ ] Push bugfix-1 branch to GitHub
- [ ] Deploy via GoDaddy cPanel Git
- [ ] Test contact form on production
- [ ] Test newsletter on production
- [ ] Test reviews API on production
- [ ] Monitor logs for 24 hours
- [ ] Create PR bugfix-1 → main

---

**Need help?** Check the README.md or relevant doc above, or search for your issue in archived docs.
