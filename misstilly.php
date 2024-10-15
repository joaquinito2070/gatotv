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
function generateRSS($videos, $lastUpdateTime, $simulatedTime, $timezone) {
    $rss = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
    $channel = $rss->addChild('channel');
    $channel->addChild('title', 'Miss Tilly Birthday Videos');
    $channel->addChild('link', 'https://www.youtube.com/playlist?list=PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET');
    $channel->addChild('description', 'Recent birthday videos from Miss Tilly');
    $channel->addChild('lastBuildDate', date('r', $lastUpdateTime));
    $channel->addChild('simulatedTime', $simulatedTime);
    $channel->addChild('timezone', $timezone);

    foreach ($videos as $video) {
        if (isset($video['birthday']) && $video['birthday']) {
            $item = $channel->addChild('item');
            $item->addChild('title', $video['title']);
            $link = 'https://www.youtube.com/watch?v=' . $video['videoId'];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
                $link = 'https://joaquinito02.es/disney.php?base64_url=' . base64_encode(gzcompress($link)) . '&time=' . urlencode($simulatedTime) . '&time_zone=' . urlencode($timezone);
            }
            $item->addChild('link', $link);
            $description = $video['birthday_message'];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
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
function generateJSONFeed($videos, $lastUpdateTime, $simulatedTime, $timezone) {
    $feed = [
        'version' => 'https://jsonfeed.org/version/1',
        'title' => 'Miss Tilly Birthday Videos',
        'home_page_url' => 'https://www.youtube.com/playlist?list=PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET',
        'feed_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'items' => [],
        'simulatedTime' => $simulatedTime,
        'timezone' => $timezone
    ];

    foreach ($videos as $video) {
        if (isset($video['birthday']) && $video['birthday']) {
            $url = 'https://www.youtube.com/watch?v=' . $video['videoId'];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
                $url = 'https://joaquinito02.es/disney.php?base64_url=' . base64_encode(gzcompress($url)) . '&time=' . urlencode($simulatedTime) . '&time_zone=' . urlencode($timezone);
            }
            $item = [
                'id' => $video['videoId'],
                'url' => $url,
                'title' => $video['title'],
                'content_text' => $video['birthday_message'],
                'date_published' => date('c', $lastUpdateTime)
            ];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
                $item['content_text'] .= ' ' . $video['disney_message'];
            }
            $feed['items'][] = $item;
        }
    }

    return json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Function to generate JSON:API
function generateJSONAPI($videos, $lastUpdateTime, $traceId, $playlistId, $simulatedTime, $timezone, $showBirthdays) {
    $data = [];
    foreach ($videos as $video) {
        if (isset($video['birthday']) && $video['birthday']) {
            $attributes = [
                'title' => $video['title'],
                'uploadDate' => $video['uploadDate'],
                'birthdayMessage' => $video['birthday_message'],
            ];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
                $attributes['disneyBirthdayMessage'] = $video['disney_message'];
            }
            $url = 'https://www.youtube.com/watch?v=' . $video['videoId'];
            if (isset($video['disney_birthday']) && $video['disney_birthday']) {
                $url = 'https://joaquinito02.es/disney.php?base64_url=' . base64_encode(gzcompress($url)) . '&time=' . urlencode($simulatedTime) . '&time_zone=' . urlencode($timezone);
            }
            $data[] = [
                'type' => 'birthdayVideos',
                'id' => $video['videoId'],
                'attributes' => $attributes,
                'links' => [
                    'self' => $url
                ]
            ];
        }
    }

    $jsonapi = [
        'jsonapi' => ['version' => '1.0'],
        'data' => $data,
        'meta' => [
            'traceId' => $traceId,
            'lastUpdate' => date('Y-m-d\TH:i:sP', $lastUpdateTime),
            'simulatedTime' => $simulatedTime,
            'timezone' => $timezone,
            'showBirthdays' => $showBirthdays
        ],
        'links' => [
            'self' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'playlist' => 'https://www.youtube.com/playlist?list=' . $playlistId
        ]
    ];

    return json_encode($jsonapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Function to determine if an update should occur and return the update time
function getUpdateTime($simulatedTime) {
    $currentTime = strtotime($simulatedTime);
    $midnight = strtotime(date('Y-m-d', $currentTime));
    $timeSinceMidnight = $currentTime - $midnight;
    
    // Update between 6 and 30 minutes after midnight, dynamically
    if ($timeSinceMidnight >= 360 && $timeSinceMidnight <= 1800) {
        $updateDelay = mt_rand(360, 1800); // Random delay between 6 and 30 minutes
        return $midnight + $updateDelay;
    } else {
        // If not within the update window, return the current time
        return $currentTime;
    }
}

// Get simulated time from GET parameter or use current time
$simulatedTime = isset($_GET['time']) ? $_GET['time'] : date('c');

// Get timezone from GET parameter or use default
$timezone = isset($_GET['time_zone']) ? $_GET['time_zone'] : 'UTC';

// Set the timezone
date_default_timezone_set($timezone);

// Fetch playlist data
$playlistId = 'PLfDtKI1uwng7Hb35F-ZTyReWO2H7O9WET';
$playlistData = fetchPlaylistData($playlistId);

// Process video data
$videos = [];
$startDate = new DateTime('2021-10-02');
$index = 0;

// Determine if birthdays should be shown
$currentTime = strtotime($simulatedTime);
$midnight = strtotime(date('Y-m-d', $currentTime));
$timeSinceMidnight = $currentTime - $midnight;

// Generate a random number between 360 and 1800 (6 to 30 minutes)
$randomDelay = mt_rand(360, 1800);

// Determine if birthdays should be shown based on the current time
$showBirthdays = ($timeSinceMidnight <= 86400); // Show birthdays for the entire day (86400 seconds)

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

    $simulatedDateTime = new DateTime($simulatedTime);
    $simulatedDateTime->setTimezone(new DateTimeZone($timezone));
    $video = [
        'title' => $videoRenderer['title']['runs'][0]['text'],
        'videoId' => $videoId,
        'uploadDate' => $uploadDate
    ];

    if ($showBirthdays) {
        $video['birthday'] = isBirthday($uploadDate, $simulatedDateTime->format('Y-m-d H:i:s'));
        $video['disney_birthday'] = isDisneyBirthday($simulatedDateTime->format('Y-m-d H:i:s'));

        if ($video['birthday']) {
            $video['birthday_message'] = "¡Hoy este vídeo está de cumpleaños!";
        }

        if ($video['disney_birthday']) {
            $video['disney_message'] = "¡Hoy Disney está de cumpleaños! Disney nació el 16 de octubre de 1923.";
        }
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
    'last_update' => date('Y-m-d\TH:i:sP', $lastUpdateTime),
    'simulated_time' => $simulatedDateTime->format('Y-m-d\TH:i:sP'),
    'timezone' => $timezone,
    'show_birthdays' => $showBirthdays
];

// Check if redirection is requested
if (isset($_GET['redir']) && $_GET['redir'] === 'true') {
    $birthdayVideo = null;
    $redirectWindow = $showBirthdays; // Use the same condition as showBirthdays

    if ($redirectWindow) {
        foreach ($videos as $video) {
            if (isset($video['birthday']) && $video['birthday']) {
                $birthdayVideo = $video;
                break;
            }
        }
    }
    if ($birthdayVideo) {
        $redirectUrl = 'https://www.youtube.com/watch?v=' . $birthdayVideo['videoId'];
        if (isset($birthdayVideo['disney_birthday']) && $birthdayVideo['disney_birthday']) {
            $redirectUrl = 'https://joaquinito02.es/disney.php?base64_url=' . base64_encode(gzcompress($redirectUrl)) . '&time=' . urlencode($simulatedTime) . '&time_zone=' . urlencode($timezone);
        }
        header("Location: " . $redirectUrl);
        exit;
    } else {
        // If no birthday video is found or outside the redirect window, return a 404 error
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'No birthday video found or outside redirect window']);
        exit;
    }
}

// Check if RSS format is requested
if (isset($_GET['format']) && $_GET['format'] === 'rss') {
    header("Content-Type: application/rss+xml; charset=UTF-8");
    echo generateRSS($videos, $lastUpdateTime, $simulatedTime, $timezone);
} elseif (isset($_GET['format']) && $_GET['format'] === 'json') {
    header("Content-Type: application/json; charset=UTF-8");
    echo generateJSONFeed($videos, $lastUpdateTime, $simulatedTime, $timezone);
} elseif (isset($_GET['format']) && $_GET['format'] === 'jsonapi') {
    header("Content-Type: application/vnd.api+json");
    echo generateJSONAPI($videos, $lastUpdateTime, $traceId, $playlistId, $simulatedDateTime->format('Y-m-d\TH:i:sP'), $timezone, $showBirthdays);
} else {
    // Output the JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>
