# Google Places API Setup Guide

**Time Required:** 15–20 minutes  
**Prerequisites:** Google account (can use pleasureislanddesign@gmail.com)  
**Outcome:** Live review integration + API key + Place ID

---

## Step 1: Access Google Cloud Console

1. **Open Browser Tab:** https://console.cloud.google.com/
2. **Sign In:** Use pleasureislanddesign@gmail.com
3. **You should see:** Google Cloud Console dashboard

---

## Step 2: Create or Select Project

### If you don't have a project yet:

1. **Click project dropdown** (top-left, next to "Google Cloud")
2. **Click "NEW PROJECT"**
3. **Project name:** `Pleasure Island Design`
4. **Organization:** Leave blank (or select if applicable)
5. **Click "CREATE"**
6. **Wait ~30 seconds** for project to initialize
7. **When ready, click "SELECT PROJECT"**

### If you already have a project:

1. **Click project dropdown**
2. **Select "Pleasure Island Design"** (or your existing project)

---

## Step 3: Enable Google Places API

1. **Left sidebar → APIs & Services → Library**
2. **Search bar:** Type `Places API`
3. **Click on "Places API"** result
4. **Click the blue "ENABLE" button**
5. **Wait ~1 minute** for activation
6. You should see: "API Enabled" message with checkmark

---

## Step 4: Create API Key

1. **Left sidebar → APIs & Services → Credentials**
2. **Click blue "+ CREATE CREDENTIALS" button** (top-left)
3. **Select "API Key"** from dropdown
4. **A dialog appears with your new key** — **Copy it to a safe place** (we'll use it soon)
   - Format: `AIzaSy...` (very long string)
   - **Don't close this dialog yet!**

### Restrict the API Key (Security)

1. **In the dialog, click "Restrict Key"**
2. **Application restrictions:**
   - Select: **"HTTP referrers (web sites)"**
   - Add: `https://www.pleasureislanddesign.com/*`
   - Add: `https://pleasureislanddesign.com/*`
3. **API restrictions:**
   - Select: **"Restrict key"**
   - Choose: **"Places API"** from dropdown
   - Deselect any other APIs
4. **Click "SAVE"**
5. **Copy your API Key again** and save to a text file

**Your API Key:**
```
AIzaSy... [paste your full key here]
```

---

## Step 5: Find Your Place ID

### Method A: Via Google Maps (Easiest)

1. **Open new tab:** https://www.google.com/maps
2. **Search:** `Pleasure Island Design Wilmington NC`
3. **Click the business result** (first one)
4. **Copy the URL** from address bar
   - Look for: `?cid=XXXXXXXXXX` or `!4m...`
   - Example: `https://www.google.com/maps/place/Pleasure+Island+Design/@34.2243...?cid=17863584759`
   - The number after `cid=` is part of your Place ID

5. **Alternative:** Right-click on the business marker → "What's here?" → Copy coordinates

**Your Place ID (format ChIJ...):**
```
[Paste here after you find it]
```

### Method B: Via Google Places API (Alternative)

1. **Open browser console:** F12 → Console tab
2. **Run this (replace YOUR_API_KEY with the key you just created):**
   ```javascript
   fetch('https://maps.googleapis.com/maps/api/place/textsearch/json?query=Pleasure+Island+Design+Wilmington+NC&key=YOUR_API_KEY')
     .then(r => r.json())
     .then(d => {
       console.log('Place ID:', d.results[0]?.place_id);
       console.log('Full result:', d.results[0]);
     })
   ```
3. **Look for:** `place_id: "ChIJ..."`

---

## Step 6: Configure on GoDaddy

You now have:
- **API Key:** `AIzaSy...`
- **Place ID:** `ChIJ...`

### Option A: Via .htaccess (Recommended for Security)

1. **GoDaddy cPanel → File Manager**
2. **Navigate to:** `/public_html/`
3. **Open `.htaccess`** file (or create if missing)
4. **Add these lines at the end:**
   ```apache
   SetEnv GOOGLE_PLACES_API_KEY "AIzaSy..."
   SetEnv GOOGLE_PLACE_ID "ChIJ..."
   ```
   *(Replace with your actual key and ID)*

5. **Save file**
6. **Restart Apache** (Cppanel → Restart Services, or wait ~1 minute)

### Option B: Hardcoding in PHP (Simpler, Less Secure)

1. **GoDaddy cPanel → File Manager**
2. **Navigate to:** `/public_html/forms/`
3. **Open `get-reviews.php`**
4. **Find lines 14–15:**
   ```php
   $api_key = getenv('GOOGLE_PLACES_API_KEY') ?: '';
   $place_id = getenv('GOOGLE_PLACE_ID') ?: '';
   ```
5. **Replace with:**
   ```php
   $api_key = 'AIzaSy...'; // Replace with your key
   $place_id = 'ChIJ...';  // Replace with your Place ID
   ```
6. **Save file**

---

## Step 7: Test the Integration

### Local Test (Before Deployment)

1. **Browser console:** F12 → Console
2. **Run:**
   ```javascript
   fetch('/forms/get-reviews.php')
     .then(r => r.json())
     .then(d => {
       console.log('Reviews:', d.reviews);
       console.log('Count:', d.count);
     })
   ```
3. **You should see:** 4–6 real reviews from Google with names, ratings, text
4. **If you see demo reviews:** API not configured yet, check keys

### Production Test (After Deployment)

1. **Visit:** https://www.pleasureislanddesign.com/
2. **Scroll to** "Reviews" or "Testimonials" section
3. **Open browser console:** F12 → Network tab
4. **Look for** `/forms/get-reviews.php` request
5. **Check response:** Should contain real reviews, not demo

---

## Step 8: Monitor API Usage

### View API Statistics

1. **Google Cloud Console → APIs & Services → Quotas**
2. **Look for "Places API"** in the list
3. **Free tier quota:** 25 requests per day
4. **Your usage:** Shows number of API calls made

### Increase Quota (if needed)

1. **Click "Places API"** in Quotas list
2. **Click "EDIT QUOTAS"** (pencil icon)
3. **Increase "Requests per day"** slider
4. **You may need to enable billing** (only charged if over free tier)

---

## Troubleshooting

### "API Not Configured" Response

**Problem:** Reviews page shows setup instructions instead of live reviews

**Diagnosis:**
```javascript
// In browser console, check if environment variables are set
fetch('/forms/get-reviews.php')
  .then(r => r.json())
  .then(d => console.log(d.error))
```

**Solutions:**
1. Verify API key is in .htaccess or hardcoded
2. Restart Apache in cPanel (Restart Services)
3. Clear browser cache (Ctrl+Shift+Delete)
4. Wait 2 minutes for environment variables to load

### "Invalid API Key" or "Permission Denied"

**Problem:** API key exists but is invalid

**Solutions:**
1. Re-copy API key from Google Cloud Console
2. Verify key hasn't been regenerated/deleted
3. Check Places API is enabled (Google Cloud → APIs & Services → Library)
4. Verify API key restrictions: ensure "Places API" is selected

### "Place Not Found"

**Problem:** Place ID doesn't return results

**Solutions:**
1. Verify Place ID format (should start with `ChIJ`)
2. Re-search for "Pleasure Island Design Wilmington NC" on Google Maps
3. Ensure the business is verified on Google Business Profile
4. Try alternate Place ID from Google Places Text Search

### Reviews Show as "Demo"

**Problem:** Reviews are hardcoded demo reviews, not live

**Diagnosis:**
1. Check API response: `fetch('/forms/get-reviews.php').then(r=>r.json()).then(console.log)`
2. If response has `"demo": true`, API is failing silently
3. Check GoDaddy error logs: `.logs/reviews-api.log`

**Solutions:**
1. Verify API key is valid and not rate-limited
2. Check Place ID is correct
3. Ensure business has reviews on Google
4. Contact Google Cloud Support if API is unreachable

---

## API Cost & Quotas

### Free Tier
- **Limit:** 25 requests per calendar day
- **Cost:** $0
- **Resets:** Daily at midnight UTC

### Paid Tier (if you exceed free tier)
- **Cost:** ~$17 per 1,000 requests
- **Only charged** if you exceed 25/day
- **You must enable billing** to go over free tier

### Optimization
- API responses are cached for 1 hour
- Same reviews shown to all users for 1 hour window
- Caching keeps costs low even with high traffic

---

## Next Steps

1. **Verify configuration** (test in browser console)
2. **Deploy to GoDaddy** (push bugfix-1 branch)
3. **Test on production** (https://www.pleasureislanddesign.com)
4. **Monitor API usage** (Google Cloud Console → Quotas)
5. **Alert:** If approaching 25 requests/day, consider enabling billing or adjusting cache TTL

---

## Saved Credentials

**IMPORTANT:** Keep these safe and do NOT commit to GitHub

```
API Key: AIzaSy[...]

Place ID: ChIJ[...]

Config Location: /public_html/.htaccess (Option A) 
                 or /public_html/forms/get-reviews.php (Option B)
```

---

## Support

- **Google Cloud Help:** https://cloud.google.com/docs/authentication/api-keys
- **Places API Docs:** https://developers.google.com/maps/documentation/places/web-service
- **Rate Limits:** https://developers.google.com/maps/billing-and-pricing

---

Generated: June 18, 2026  
Last Updated: [Your completion date]
