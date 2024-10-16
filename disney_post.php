<?php
// Set headers for CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Generate a unique X-Trace-ID
$traceId = uniqid('trace-', true);
header("X-Trace-ID: " . $traceId);

// Function to decode BASE64 in zlib
function decodeBase64Zlib($encodedString) {
    return gzuncompress(base64_decode($encodedString));
}

// Decode parameters
$url = decodeBase64Zlib($_GET['base64_url'] ?? '');
$requestHeaders = decodeBase64Zlib($_GET['base64_request_headers'] ?? '');
$userAgent = decodeBase64Zlib($_GET['base64_user_agent'] ?? '');
$start = decodeBase64Zlib($_GET['base64_start'] ?? '');
$end = decodeBase64Zlib($_GET['base64_end'] ?? '');
$simulatedTime = decodeBase64Zlib($_GET['base64_time'] ?? '');
$timezone = decodeBase64Zlib($_GET['base64_time_zone'] ?? '');

// Set timezone if provided, otherwise use UTC
if (!empty($timezone)) {
    date_default_timezone_set($timezone);
} else {
    date_default_timezone_set('UTC');
}

// Use simulated time if provided, otherwise use current time
$currentTime = !empty($simulatedTime) ? new DateTime($simulatedTime) : new DateTime();

// Check if it's Disney's birthday (October 16)
$isDisneyBirthday = ($currentTime->format('m-d') === '10-16');

if (!$isDisneyBirthday) {
    // If it's not Disney's birthday, return a 410 Gone error
    header("HTTP/1.1 410 Gone");
    echo json_encode(['error' => 'This service is only available on Disney\'s birthday (October 16)']);
    exit;
}

// Validate URL
if (filter_var($url, FILTER_VALIDATE_URL)) {
    // Set additional headers if provided
    if (!empty($requestHeaders)) {
        $headers = json_decode($requestHeaders, true);
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
    }

    // Set User-Agent if provided
    if (!empty($userAgent)) {
        header("User-Agent: $userAgent");
    }

    // Set start and end headers if provided
    if (!empty($start)) {
        $formattedStart = (new DateTime($start))->format('Y-m-d\TH:i:sP');
        header("X-Start: $formattedStart");
    }
    if (!empty($end)) {
        $formattedEnd = (new DateTime($end))->format('Y-m-d\TH:i:sP');
        header("X-End: $formattedEnd");
    }

    // Set simulated time header if provided
    if (!empty($simulatedTime)) {
        $formattedSimulatedTime = $currentTime->format('Y-m-d\TH:i:sP');
        header("X-Simulated-Time: $formattedSimulatedTime");
    }

    // Set timezone header if provided
    if (!empty($timezone)) {
        header("X-Timezone: $timezone");
    }

    // Redirect to the decoded URL
    header("Location: $url");
    exit;
} else {
    // Invalid URL
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(['error' => 'Invalid URL provided']);
}
?>
