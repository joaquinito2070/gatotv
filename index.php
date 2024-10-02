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

// Function to fetch HTML content and convert it to JSON
function fetchAndConvertToJSON($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);
    
    // Use DOMDocument to parse HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    // Convert DOM to array
    $array = domToArray($dom->documentElement);
    
    return $array;
}

// Helper function to convert DOM to array
function domToArray($root) {
    $array = array();
    
    if ($root->hasAttributes()) {
        foreach ($root->attributes as $attr) {
            $array['@attributes'][$attr->nodeName] = $attr->nodeValue;
        }
    }
    
    if ($root->hasChildNodes()) {
        if ($root->childNodes->length == 1 && $root->childNodes->item(0)->nodeType == XML_TEXT_NODE) {
            $array['_value'] = $root->childNodes->item(0)->nodeValue;
        } else {
            foreach ($root->childNodes as $child) {
                if ($child->nodeType != XML_TEXT_NODE) {
                    if (!isset($array[$child->nodeName])) {
                        $array[$child->nodeName] = array();
                    }
                    $array[$child->nodeName][] = domToArray($child);
                }
            }
        }
    }
    
    return $array;
}

// Get the channel and date from the URL
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$channel = isset($urlParts[2]) ? $urlParts[2] : '';
$date = isset($urlParts[3]) ? $urlParts[3] : '';

// Construct the URL
$url = "https://www.gatotv.com/canal/{$channel}/{$date}";

// Fetch the HTML content and convert to JSON
$jsonData = fetchAndConvertToJSON($url);

// Prepare the response
$response = [
    'data' => $jsonData,
    'url' => $url,
    'trace_id' => $traceId
];

// Output the JSON response
echo json_encode($response);
?>
