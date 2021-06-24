<?php

require_once 'app/init.php';

$websites = $api->get_websites_filtered();
if (empty($websites)) {
    new Logs(json_encode(['time' => date('Y-m-d h:s a'), 'hasError' => true, 'errors' => ["Unable reach panel API."]]), 'system');
    end_response(400, "Unable reach panel API.");
}

$host_name = "anywebsite.com";

if (!array_key_exists($host_name, $websites)) {

    if (array_key_exists('HTTP_REFERER', $_SERVER)) {
        $referrer = $_SERVER['HTTP_REFERER'];
    } else {
        $referrer = DEFAULT_REFERRER;
    }

    $result = $api->add_website($host_name, SERVER_DOMAIN_DIR, $referrer);
    
    if (!$result['status']) {
        new Logs(json_encode(['time' => date('Y-m-d h:s a'), 'hasError' => true, 'errors' => ['host' => $host_name, 'error' => "Unable to create website"]]), 'system');
        end_response(401, "Unable to create website");
    }

} else {
    end_response(200, "Website already exists");
}

end_response(200, "Website created");
