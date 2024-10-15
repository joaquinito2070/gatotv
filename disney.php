<?php
// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Generate a unique X-Trace-ID
$traceId = uniqid('trace-', true);
header("X-Trace-ID: " . $traceId);

// Get timezone from GET parameter or use default
$timezone = isset($_GET['time_zone']) ? $_GET['time_zone'] : 'UTC';

// Get simulated time from GET parameter or use current time
$simulatedTime = isset($_GET['time']) ? $_GET['time'] : date('c');

// Set the timezone
date_default_timezone_set($timezone);

// Function to check if it's Disney's birthday
function isDisneyBirthday($simulatedTime) {
    $date = new DateTime($simulatedTime);
    return $date->format('m-d') === '10-16';
}

// Get the base64_url parameter
$base64Url = isset($_GET['base64_url']) ? $_GET['base64_url'] : '';

// Check if URL is specified
if (empty($base64Url)) {
    header("HTTP/1.0 404 Not Found");
    echo "Error 404: URL not specified";
    exit;
}

// Decode the URL
$decodedUrl = base64_decode($base64Url);
$url = @gzuncompress($decodedUrl) ?: $decodedUrl;

// Check if the URL is valid
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    header("HTTP/1.0 404 Not Found");
    echo "Error 404: Invalid URL";
    exit;
}

// Check if it's a web browser
$isWebBrowser = !empty($_SERVER['HTTP_USER_AGENT']) && preg_match("/(Mozilla|Opera|Chrome|Safari|Firefox|Edge|MSIE|Trident)/i", $_SERVER['HTTP_USER_AGENT']);

if (isDisneyBirthday($simulatedTime)) {
    // Prepare data for redirection
    $redirectData = [
        'base64_url' => base64_encode(gzcompress($url)),
        'base64_request_headers' => base64_encode(gzcompress(json_encode(getallheaders()))),
        'base64_user_agent' => base64_encode(gzcompress($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'base64_start' => base64_encode(gzcompress(date('c', strtotime('+10 seconds')))),
        'base64_end' => base64_encode(gzcompress(date('c', strtotime('+20 seconds')))),
        'base64_time' => base64_encode(gzcompress($simulatedTime)),
        'base64_time_zone' => base64_encode(gzcompress($timezone))
    ];

    $redirectUrl = 'disney_post.php?' . http_build_query($redirectData);

    if ($isWebBrowser) {
        // Output HTML for web browsers
        header("Content-Type: text/html; charset=UTF-8");
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>¡Feliz Cumpleaños Disney!</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                h1 { color: #1E90FF; }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = '" . str_replace('&amp;', '&', htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8')) . "';
                }, 10000);
            </script>
        </head>
        <body>
            <h1>¡Hoy Disney está de cumpleaños!</h1>
            <p>Serás redirigido en 10 segundos...</p>
        </body>
        </html>";
    } else {
        // For non-web browsers, wait 10 seconds then redirect
        sleep(10);
        header("Location: " . $redirectUrl);
        exit;
    }
} else {
    // If it's not Disney's birthday, return a 410 Gone error
    header("HTTP/1.1 410 Gone");
    header("Content-Type: text/plain; charset=UTF-8");
    echo "Error 410: Este servicio solo está disponible durante el cumpleaños de Disney (16 de octubre).";
    exit;
}
?>
