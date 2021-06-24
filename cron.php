<?php

require_once 'app/init.php';

$websites = $api->get_websites_filtered();

foreach ($websites as $host => $website) {
    
    // checking if the host is in ignore domains
    if (in_array($host, $ignore_domains)) { continue; }

    // checking if the ssl is already enabled
    if (is_array($website['ssl']) || $website['ssl'] != '-1') { continue; }

    // checking domain's A record
    $dns_records = dns_get_record($host, DNS_A);
    $a_record_exists = false;
    if (!empty($dns_records)) {
        foreach ($dns_records as $dns_record) {
            if ($dns_record['ip'] === SERVER_IP) {
                $a_record_exists = true; break;
            }
        }
    }

    // if the A record is pointing to our server then setting ssl
    if ($a_record_exists) {
        // getting available ssl certificate
        $result = $api->get_certificate($host);
        if (!$result['status']) {
            // applying new ssl certificate
            $result = $api->apply_ssl($website['id'], $host);
        }

        if ($result['status']) {
            $enabled = $api->enable_ssl($host, $result['csr'], $result['private_key']);
            if ($enabled) {
                end_response(200, "Certificate enabled");
            } else {
                end_response(200, "Unable to enable certificate");
            }
        }
        
        end_response(200, "Something went wrong when applying for certificate");
    }
    
    end_response(200, "A record is not configured yet");
}

end_response(200, "Ok");
