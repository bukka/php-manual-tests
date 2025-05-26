<?php

define('PHP_CLI_SERVER_ADDRESS', "localhost:8000");

function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
    switch($notification_code) {
        case STREAM_NOTIFY_MIME_TYPE_IS:
            echo "Found the mime-type: ", $message, PHP_EOL;
            break;
    }
}


$ctx = stream_context_create();
stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
echo file_get_contents("http://".PHP_CLI_SERVER_ADDRESS."/index.php", false, $ctx);
var_dump($http_response_header);

