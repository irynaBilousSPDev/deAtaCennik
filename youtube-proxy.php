<?php

//$my_youtube_api_key = 'AIzaSyAmv-a8SXP2ckayL-RawlIGSR6VA4oS3-E';
// $youtube_api_key = 'AIzaSyC-O5PCUNxaFhOdnJ-ZOcEG67f05xerpwo';

header('Content-Type: application/json');

// 🔒 Secure API Key Storage
$apiKey = 'AIzaSyC-O5PCUNxaFhOdnJ-ZOcEG67f05xerpwo';
if (!$apiKey) {
    error_log("YouTube API Error: Missing API Key.");
    echo json_encode(["error" => "Server misconfiguration: API Key missing"]);
    exit;
}

// 🔐 CORS Restriction - Allow only trusted domains
$allowedOrigins = [
    "https://yourdomain.com",
    "https://another-trusted-site.com"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// ✅ Securely Get API Parameters
$playlistId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
$videoId = filter_input(INPUT_GET, 'videoId', FILTER_SANITIZE_STRING);

// 🎯 Determine API Request Type
if ($playlistId) {
    // Fetch Playlist Videos
    $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&maxResults=10&playlistId={$playlistId}&key={$apiKey}";
} elseif ($videoId) {
    // Fetch Single Video Details
    $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id={$videoId}&key={$apiKey}";
} else {
    echo json_encode(["error" => "No valid parameters provided"]);
    exit;
}

// 🌐 Use cURL to Fetch YouTube Data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // ✅ Enable SSL verification for security
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "User-Agent: MyYouTubeAPI/1.0"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// 🛠️ Log API Response
error_log("YouTube API Request: $url");
error_log("YouTube API HTTP Response Code: $httpCode");

// ⚠️ Handle API Errors Gracefully
if ($httpCode !== 200 || !$response) {
    echo json_encode([
        "error" => "Failed to fetch YouTube data",
        "status" => $httpCode,
        "details" => $error ?: "Unknown Error"
    ], JSON_PRETTY_PRINT);
    exit;
}

// 📌 Validate JSON Response
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Invalid JSON response from YouTube API"], JSON_PRETTY_PRINT);
    exit;
}

// 🎯 Return JSON Response
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
