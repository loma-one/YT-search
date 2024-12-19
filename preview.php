<?php
if (isset($_GET['id'])) {
    $unique_id = $_GET['id'];
    $preview_file = "previews/$unique_id.html";

    if (file_exists($preview_file)) {
        echo file_get_contents($preview_file);
    } else {
        echo 'Preview not found';
    }
}
?>
