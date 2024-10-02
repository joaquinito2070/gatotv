<?php
// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Generate a unique X-Trace-ID
$traceId = uniqid('trace-', true);
header("X-Trace-ID: " . $traceId);

// Function to fetch HTML content
function fetchHTML($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Get the channel and date from the URL
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$channel = isset($urlParts[2]) ? $urlParts[2] : '';
$date = isset($urlParts[3]) ? $urlParts[3] : '';

// Construct the URL
$url = "https://www.gatotv.com/canal/{$channel}/{$date}";

// Fetch the HTML content
$html = fetchHTML($url);

// Prepare the response
$response = [
    'html' => $html,
    'url' => $url,
    'trace_id' => $traceId
];

// Output the JSON response
echo json_encode($response);
?>



