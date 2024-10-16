<?php
// Set headers for CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Function to get YouTube video details
function getYouTubeVideoDetails($videoId, $client) {
    $url = "https://www.youtube.com/youtubei/v1/player?key=AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8";
    $data = [
        'context' => [
            'client' => [
                'clientName' => $client,
                'clientVersion' => '2.20200720.00.00'
            ]
        ],
        'videoId' => $videoId
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

// Get video ID from URL
$videoId = $_GET['v'] ?? '';

if (empty($videoId)) {
    die('Video ID is required');
}

// Get simulated time and timezone from URL or use defaults
$simulatedTime = $_GET['time'] ?? date('Y-m-d\TH:i:sP');
$timezone = $_GET['time_zone'] ?? 'UTC';

// Set timezone
date_default_timezone_set($timezone);

// Create DateTime object for simulated time
$simulatedDateTime = new DateTime($simulatedTime);

// Check if it's Disney's birthday (October 16)
$isDisneyBirthday = ($simulatedDateTime->format('m-d') === '10-16');

// Array of clients to try
$clients = ['mweb', 'ios', 'tvhtml5_simply_embedded_player', 'tvhtml5'];

$videoUrl = '';

foreach ($clients as $client) {
    $videoDetails = getYouTubeVideoDetails($videoId, $client);
    
    if (isset($videoDetails['streamingData']['formats'])) {
        foreach ($videoDetails['streamingData']['formats'] as $format) {
            if ($format['itag'] == 18) {
                $videoUrl = $format['url'];
                break 2;
            }
        }
    }
}

if (empty($videoUrl)) {
    die('Unable to find video URL');
}

// Instead of trying to redirect, we'll return a 410 Gone error
header("HTTP/1.1 410 Gone");
echo json_encode(['error' => 'This service is no longer available']);
exit;
?>
