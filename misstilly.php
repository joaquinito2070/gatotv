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
function isBirthday($uploadDate, $simulatedTime) {
    if ($uploadDate === 'unknown') return false;
    $today = new DateTime($simulatedTime);
    $upload = new DateTime($uploadDate);
    return $today->format('m-d') === $upload->format('m-d') && $today->format('Y') !== $upload->format('Y');
}

// Function to check if a date is Disney's birthday
function isDisneyBirthday($simulatedTime) {
    $today = new DateTime($simulatedTime);
    return $today->format('m-d') === '10-16';
}

// Function to generate RSS feed
function generateRSS($videos, $lastUpdateTime) {
    $rss = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
    $channel = $rss->addChild('channel');
    $channel->addChild('title', 'Miss Tilly Birthday Videos');
    $channel->addChild('link', 'https://www.youtube.com/playlist?list=PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET');
    $channel->addChild('description', 'Recent birthday videos from Miss Tilly');
    $channel->addChild('lastBuildDate', date('r', $lastUpdateTime));

    foreach ($videos as $video) {
        if ($video['birthday']) {
            $item = $channel->addChild('item');
            $item->addChild('title', $video['title']);
            $item->addChild('link', 'https://www.youtube.com/watch?v=' . $video['videoId']);
            $description = $video['birthday_message'];
            if ($video['disney_birthday']) {
                $description .= ' ' . $video['disney_message'];
            }
            $item->addChild('description', $description);
            $item->addChild('pubDate', date('r', $lastUpdateTime));
            $item->addChild('guid', $video['videoId']);
        }
    }

    return $rss->asXML();
}

// Function to generate JSON Feed
function generateJSONFeed($videos, $lastUpdateTime) {
    $feed = [
        'version' => 'https://jsonfeed.org/version/1',
        'title' => 'Miss Tilly Birthday Videos',
        'home_page_url' => 'https://www.youtube.com/playlist?list=PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET',
        'feed_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'items' => []
    ];

    foreach ($videos as $video) {
        if ($video['birthday']) {
            $item = [
                'id' => $video['videoId'],
                'url' => 'https://www.youtube.com/watch?v=' . $video['videoId'],
                'title' => $video['title'],
                'content_text' => $video['birthday_message'],
                'date_published' => date('c', $lastUpdateTime)
            ];
            if ($video['disney_birthday']) {
                $item['content_text'] .= ' ' . $video['disney_message'];
            }
            $feed['items'][] = $item;
        }
    }

    return json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Function to determine if an update should occur and return the update time
function getUpdateTime($simulatedTime) {
    $currentTime = strtotime($simulatedTime);
    $midnight = strtotime(date('Y-m-d', $currentTime));
    $timeSinceMidnight = $currentTime - $midnight;
    
    // Update between 6 and 30 minutes after midnight
    if ($timeSinceMidnight >= 360 && $timeSinceMidnight <= 1800) {
        return $midnight; // Return midnight (00:00:00) as the update time
    } else {
        // If not within the update window, return false
        return false;
    }
}

// Get simulated time from GET parameter or use current time
$simulatedTime = isset($_GET['time']) ? $_GET['time'] : date('c');

// Fetch playlist data
$playlistId = 'PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET';
$playlistData = fetchPlaylistData($playlistId);

// Process video data
$videos = [];
$startDate = new DateTime('2021-10-02');
$index = 0;

foreach ($playlistData['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'][0]['playlistVideoListRenderer']['contents'] as $item) {
    $videoRenderer = $item['playlistVideoRenderer'];
    $videoId = $videoRenderer['videoId'];
    
    if ($index === 0 && $videoId === 'Yaje-84F7CM') {
        $uploadDate = '2021-10-02';
    } elseif ($index < 7) {
        do {
            $startDate->modify('+1 day');
        } while ($startDate->format('N') < 6 || ($startDate >= new DateTime('2021-10-24') && $startDate <= new DateTime('2021-11-07')));
        $uploadDate = $startDate->format('Y-m-d');
    } elseif ($index >= 7 && $index < 16) {
        if ($index === 7) {
            $startDate = new DateTime('2021-11-13');
        } else {
            do {
                $startDate->modify('+1 day');
            } while ($startDate->format('N') < 6);
        }
        $uploadDate = $startDate->format('Y-m-d');
    } else {
        $uploadDate = 'unknown';
    }

    $video = [
        'title' => $videoRenderer['title']['runs'][0]['text'],
        'videoId' => $videoId,
        'uploadDate' => $uploadDate,
        'birthday' => isBirthday($uploadDate, $simulatedTime),
        'disney_birthday' => isDisneyBirthday($simulatedTime)
    ];

    if ($video['birthday']) {
        $video['birthday_message'] = "¡Hoy este vídeo está de cumpleaños!";
    }

    if ($video['disney_birthday']) {
        $video['disney_message'] = "¡Hoy Disney está de cumpleaños! Disney nació el 16 de octubre de 1923.";
    }

    $videos[] = $video;
    $index++;
}

// Get the update time
$lastUpdateTime = getUpdateTime($simulatedTime);

// Prepare the response
$response = [
    'playlist_id' => $playlistId,
    'videos' => $videos,
    'trace_id' => $traceId,
    'last_update' => $lastUpdateTime ? date('Y-m-d\TH:i:sP', $lastUpdateTime) : null,
    'simulated_time' => $simulatedTime
];

// Check if RSS format is requested
if (isset($_GET['format']) && $_GET['format'] === 'rss') {
    header("Content-Type: application/rss+xml; charset=UTF-8");
    echo generateRSS($videos, $lastUpdateTime ?: strtotime($simulatedTime));
} elseif (isset($_GET['format']) && $_GET['format'] === 'json') {
    header("Content-Type: application/json; charset=UTF-8");
    echo generateJSONFeed($videos, $lastUpdateTime ?: strtotime($simulatedTime));
} else {
    // Output the JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>
