<?php
$config = include 'config.php';
$api_key = $config['youtube_api_key'];

// Ermitteln der Server-IP-Adresse
$server_ip = file_get_contents('https://api.ipify.org');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtube_url = $_POST['youtube_url'];

    // Validate YouTube URL
    if (filter_var($youtube_url, FILTER_VALIDATE_URL) === FALSE) {
        die('Invalid YouTube URL');
    }

    // Extract video ID from YouTube URL
    preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $youtube_url, $matches);
    if (!isset($matches[1])) {
        die('Invalid YouTube URL format');
    }
    $video_id = $matches[1];

    // Use YouTube Data API to get video details
    $api_url = "https://www.googleapis.com/youtube/v3/videos?id=$video_id&key=$api_key&part=snippet";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // Use the server's user agent
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === FALSE) {
        die('Failed to fetch data from YouTube API');
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Failed to decode JSON response');
    }

    if (isset($data['items'][0]['snippet'])) {
        $title = $data['items'][0]['snippet']['title'];
        $description = $data['items'][0]['snippet']['description'];
        $thumbnail = $data['items'][0]['snippet']['thumbnails']['medium']['url'];

        // Generate preview HTML with embed code and Open Graph metadata
        $unique_id = uniqid();
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $preview_link = "$base_url/preview.php?id=$unique_id";

        $preview_html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>$title</title>
            <meta property='og:title' content='$title'>
            <meta property='og:description' content='$description'>
            <meta property='og:image' content='$thumbnail'>
            <meta property='og:url' content='$preview_link'>
            <meta property='og:type' content='video.other'>
            <meta property='og:video' content='https://www.youtube.com/embed/$video_id'>
            <meta property='og:video:secure_url' content='https://www.youtube.com/embed/$video_id'>
            <meta property='og:video:type' content='text/html'>
            <meta property='og:video:width' content='560'>
            <meta property='og:video:height' content='315'>
            <link rel='icon' href='$base_url/image/favicon.ico' />
            <link rel='apple-touch-icon' href='$base_url/image/favicon.ico' />
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .video-container {
                    position: relative;
                    padding-bottom: 50.25%; /* 16:9 */
                    height: 0;
                }
                .video-container iframe {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
            </style>
        </head>
        <body>
            <div class='video-container'>
                <iframe src='https://www.youtube.com/embed/$video_id' frameborder='0' allowfullscreen></iframe>
            </div>
            <h2>$title</h2>
            <p>$description</p>
        </body>
        </html>";

        // Save preview HTML to a file
        if (file_put_contents("previews/$unique_id.html", $preview_html) === FALSE) {
            die('Failed to save preview HTML');
        }

        // Redirect to the preview link
        header("Location: $preview_link");
        exit();
    } else {
        // Debug output
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die('Video not found');
    }
}
?>