<?php
$config = include 'config.php';
$api_key = $config['youtube_api_key'];

// Ermitteln der Server-IP-Adresse
$server_ip = file_get_contents('https://api.ipify.org');

if (isset($_POST['youtube_url'])) {
    $youtube_url = $_POST['youtube_url'];
    $video_id = '';

    // Extract video ID from YouTube URL
    if (preg_match('/[\\?&]v=([^&#]*)/', $youtube_url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtu\\.be\\/([^\\?&#]*)/', $youtube_url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtube\\.com\\/embed\\/([^\\?&#]*)/', $youtube_url, $matches)) {
        $video_id = $matches[1];
    }

    if ($video_id) {
        // Use YouTube Data API to get video details
        $api_url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=$video_id&key=$api_key";

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

        if (isset($data['items'][0])) {
            $title = $data['items'][0]['snippet']['title'];
            $thumbnail = $data['items'][0]['snippet']['thumbnails']['high']['url']; // Use high resolution thumbnail
            $description = $data['items'][0]['snippet']['description'];

            // Generate a unique ID for the preview link
            $unique_id = uniqid();
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $preview_link = "$base_url/preview.php?id=$unique_id";

            // Save preview HTML to a file
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
                <meta property='og:video:width' content='1280'>
                <meta property='og:video:height' content='960'>
                <link rel='icon' href='$base_url/image/favicon.ico' />
                <link rel='apple-touch-icon' href='$base_url/image/favicon.ico' />
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                        background-color: var(--background-color); /* Use the CSS variable for background color */
                        color: var(--text-color); /* Use the CSS variable for text color */
                    }
                    .video-container {
                        position: relative;
                        padding-bottom: 56.25%; /* 16:9 */
                        height: 0;
                    }
                    .video-container iframe {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                    }
                    .thumbnail {
                        display: none; /* Hide the thumbnail from human viewers */
                    }
                    .title {
                        font-size: 1.5em;
                        margin-bottom: 10px;
                    }
                    .description {
                        font-size: 1em;
                    }
                </style>
            </head>
            <body>
                <div class='video-container'>
                    <iframe src='https://www.youtube.com/embed/$video_id' frameborder='0' allowfullscreen></iframe>
                </div>
                <img src='$thumbnail' alt='$title' class='thumbnail'>
                <h2 class='title'>$title</h2>
                <p class='description'>$description</p>
            </body>
            </html>";

            if (file_put_contents("previews/$unique_id.html", $preview_html) === FALSE) {
                die('Failed to save preview HTML');
            }

            // Redirect to the preview link
            header("Location: $preview_link");
            exit();
        } else {
            echo 'No video found';
        }
    } else {
        echo 'Invalid YouTube URL';
    }
} else {
    echo 'Please enter a YouTube URL';
}
?>
