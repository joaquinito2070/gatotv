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

// Decode the URL
$decodedUrl = base64_decode($base64Url);
$url = @gzuncompress($decodedUrl) ?: $decodedUrl;

// Check if it's a web browser
$isWebBrowser = !empty($_SERVER['HTTP_USER_AGENT']) && preg_match("/(Mozilla|Opera|Chrome|Safari|Firefox|Edge|MSIE|Trident)/i", $_SERVER['HTTP_USER_AGENT']);

if (isDisneyBirthday($simulatedTime)) {
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
                    window.location.href = '" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';
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
        header("Location: " . $url);
        exit;
    }
} else {
    // If it's not Disney's birthday, redirect immediately
    header("Location: " . $url);
    exit;
}
?>
