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

// Function to fetch playlist data using Innertube
function fetchPlaylistData($playlistId) {
    $url = "https://www.youtube.com/youtubei/v1/browse?key=AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8";
    $data = [
        'context' => [
            'client' => [
                'clientName' => 'WEB',
                'clientVersion' => '2.20230427.01.00'
            ]
        ],
        'browseId' => "VL$playlistId"
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

// Function to check if a date is a birthday
function isBirthday($uploadDate) {
    $today = new DateTime();
    $upload = new DateTime($uploadDate);
    return $today->format('m-d') === $upload->format('m-d') && $today->format('Y') !== $upload->format('Y');
}

// Function to check if a date is Disney's birthday
function isDisneyBirthday($uploadDate) {
    $upload = new DateTime($uploadDate);
    return $upload->format('Y-m-d') === date('Y') . '-10-16';
}

// Fetch playlist data
$playlistId = 'PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET';
$playlistData = fetchPlaylistData($playlistId);

// Process video data
$videos = [];
foreach ($playlistData['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'][0]['playlistVideoListRenderer']['contents'] as $item) {
    $videoRenderer = $item['playlistVideoRenderer'];
    $uploadDate = $videoRenderer['publishedTimeText']['simpleText'];
    
    // Convert relative time to actual date
    $currentDate = new DateTime();
    $uploadDateTime = clone $currentDate;
    if (preg_match('/(\d+)\s+(day|month|year)s?\s+ago/', $uploadDate, $matches)) {
        $number = intval($matches[1]);
        $unit = $matches[2] . 's';
        $uploadDateTime->modify("-$number $unit");
    }

    $video = [
        'title' => $videoRenderer['title']['runs'][0]['text'],
        'videoId' => $videoRenderer['videoId'],
        'uploadDate' => $uploadDateTime->format('Y-m-d'),
        'birthday' => isBirthday($uploadDateTime->format('Y-m-d')),
        'disney_birthday' => isDisneyBirthday($uploadDateTime->format('Y-m-d'))
    ];

    if ($video['birthday']) {
        $video['birthday_message'] = "¡Hoy este vídeo está de cumpleaños!";
    }

    if ($video['disney_birthday']) {
        $video['disney_message'] = "Disney nació el 16 de octubre de 1923";
    }

    $videos[] = $video;
}

// Prepare the response
$response = [
    'playlist_id' => $playlistId,
    'videos' => $videos,
    'trace_id' => $traceId
];

// Output the JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
