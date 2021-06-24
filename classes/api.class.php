<?php

class ApiServer {
    
    private $API_KEY = API_KEY;
    private $PANEL = SERVER_PROTOCOL."://".SERVER_IP.":".SERVER_PORT."/";
    
    private function get_token ()
    {
  		$now_time = time();
    	$payload = ['request_token' => md5($now_time.''.md5($this->API_KEY)), 'request_time' => $now_time];
    	return $payload;    
    }
    
    private function http_post_cookie ($url, $data, $timeout = 60)
    {
        $cookie_file = './'.md5($this->PANEL).'.cookie';
        if(!file_exists($cookie_file)){
            $fp = fopen($cookie_file,'w+');
            fclose($fp);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
        // removing cookie jar file
        unlink($cookie_file);
        return $output;
    }
    
	public function add_website ($domain, $path, $referrer)
	{
		$url = $this->PANEL.'/site?action=AddSite';
		
		$payload = $this->get_token ();
		$payload['webname'] = '{"domain":"'.$domain.'","domainlist":[],"count":0}';
		$payload['path'] = $path.$domain;
		$payload['type_id'] = 0;
		$payload['type'] = 'PHP';
		$payload['version'] = '74';
		$payload['ps'] = $referrer;
		$payload['port'] = 80;
		$payload['ftp'] = false;
		$payload['sql'] = false;
		$payload['codeing'] = 'utf-8';
		$payload['set_ssl'] = 0;
		$payload['force_ssl'] = 0;
		
		$result = $this->http_post_cookie ($url, $payload);
		
		$check = json_decode($result, true);
        if (!empty($check) && is_array($check)) {
            return ['status' => true];
        }
      	return ['status' => false];
	}

    public function get_certificate ($domain)
	{
		$url = $this->PANEL.'/site?action=GetSSL';
		
		$payload = $this->get_token ();
		$payload['siteName'] = $domain;
		
		$result = $this->http_post_cookie ($url, $payload);
		$check = json_decode($result, true);

        if (!empty($check) && is_array($check)) {
            if (array_key_exists('csr', $check)) {
                return ['status' => true, 'csr' => $check['csr'], 'private_key' => $check['key']];
            }
            return ['status' => false];
        }
        return ['status' => false];
	}
    
	public function apply_ssl ($website_id, $domain)
	{
		$url = $this->PANEL.'/acme?action=apply_cert_api';
		
		$payload = $this->get_token ();
		$payload['domains'] = json_encode([$domain]);
		$payload['auth_type'] = 'http';
		$payload['auth_to'] = $website_id;
		$payload['auto_wildcard'] = "0";
		$payload['id'] = $website_id;
		
		$result = $this->http_post_cookie ($url, $payload);
		
		$check = json_decode($result, true);
        
        if (!empty($check) && is_array($check)) {
            if (array_key_exists('cert', $check)) {
                return ['status' => true, 'csr' => $check['cert'].$check['root'], 'cert' => $check['cert'], 'root' => $check['root'], 'private_key' => $check['private_key']];
            }

            return ['status' => false];
        }
        return ['status' => false];
	}

    public function enable_ssl ($domain, $csr, $key)
    {
        $url = $this->PANEL.'/site?action=SetSSL';

		$payload = $this->get_token();
        $payload['type'] = "1";
        $payload['siteName'] = $domain;
        $payload['key'] = $key;
        $payload['csr'] = $csr;

		$result = $this->http_post_cookie($url, $payload);
        
		$check = json_decode($result, true);
        
        if (!empty($check) && is_array($check)) {
            if (array_key_exists('status', $check) && $check['status'] == true) {
                return ['status' => true];
            }
            return ['status' => false];
        }
        return ['status' => false];
    }
	
	public function get_websites () {
		$url = $this->PANEL.'/data?action=getData&table=sites';
		$p_data = $this->get_token();
		$p_data['limit'] = '100';

		$result = $this->http_post_cookie ($url, $p_data);
		$data = json_decode($result, true);
      	return $data;
	}

    public function get_websites_filtered ()
    {
        $result = $this->get_websites();
        $websites = [];
        if (!empty($result) && array_key_exists('data', $result) && is_array($result['data'])) {
            foreach ($result['data'] as $website) {
                $websites[$website['name']] = $website;
            }
        }
        return $websites;
    }
    
}
