<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'];

    // Validate API key (optional)
    if (empty($api_key)) {
        die('API key is required');
    }

    // Update the config file
    $config = include 'config.php';
    $config['youtube_api_key'] = $api_key;

    $config_content = "<?php\nreturn " . var_export($config, true) . ";\n";
    file_put_contents('config.php', $config_content);

    echo 'API key has been set successfully.';
}
?>
