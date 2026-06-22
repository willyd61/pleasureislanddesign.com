<?php
/**
 * Google Places API Integration
 * Fetches live reviews from Pleasure Island Design Google Business listing
 * Requires: Google Cloud API key + Place ID (set in environment variables or config)
 *
 * Usage: /forms/get-reviews.php?cache=1
 * Returns: JSON array of reviews (name, rating, text, time)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// API Key: Set via environment variable or hardcode (NOT recommended)
// For now, return placeholder + instructions
define('GOOGLE_API_KEY', getenv('GOOGLE_PLACES_API_KEY') ?: '');
define('PLACE_ID', getenv('GOOGLE_PLACE_ID') ?: '');

// If keys not configured, return setup instructions
if (!GOOGLE_API_KEY || !PLACE_ID) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Google Places API not configured',
        'setup' => [
            'step1' => 'Create Google Cloud project at https://console.cloud.google.com',
            'step2' => 'Enable Places API for your project',
            'step3' => 'Create an API key (Restrict to: Places API)',
            'step4' => 'Find your Place ID: search "Pleasure Island Design Wilmington NC" on Google Maps, get the ID from URL',
            'step5' => 'Set environment variables: GOOGLE_PLACES_API_KEY and GOOGLE_PLACE_ID',
            'step6' => 'Or hardcode them below (not recommended for production)'
        ],
        'demo' => [
            ['name' => 'Nicole & Team (Demo)', 'rating' => 5, 'text' => 'Beautiful cabinet refinishing work!', 'time' => '2 weeks ago'],
            ['name' => 'Demo Review', 'rating' => 5, 'text' => 'Professional and fast turnaround.', 'time' => '1 month ago']
        ]
    ]);
    exit;
}

try {
    // Build Places API endpoint
    $url = "https://maps.googleapis.com/maps/api/place/details/json";
    $url .= "?place_id=" . urlencode(PLACE_ID);
    $url .= "&key=" . urlencode(GOOGLE_API_KEY);
    $url .= "&fields=reviews,rating,formatted_phone_number";

    // Fetch from Google
    $response = @file_get_contents($url);

    if ($response === false) {
        throw new Exception('Failed to fetch from Google Places API');
    }

    $data = json_decode($response, true);

    if (!isset($data['result']) || !isset($data['result']['reviews'])) {
        http_response_code(404);
        echo json_encode(['error' => 'No reviews found', 'raw' => $data]);
        exit;
    }

    $reviews = $data['result']['reviews'];

    // Format reviews for frontend (limit to 5, sort by rating desc)
    $formatted = [];
    foreach (array_slice($reviews, 0, 5) as $review) {
        $formatted[] = [
            'name' => $review['author_name'] ?? 'Anonymous',
            'rating' => $review['rating'] ?? 0,
            'text' => $review['text'] ?? '',
            'time' => $review['relative_time_description'] ?? 'Recently',
            'author_url' => $review['author_url'] ?? ''
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'reviews' => $formatted,
        'count' => count($formatted),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
