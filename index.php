<?php

require 'app/functions.php';
require 'vendor/autoload.php';

define('DIR', __DIR__);

$data = request_body ();

if (!property_exists($data, 'company')) {
    end_response(401, "Company is not available in request");
}

$template = validated_template($data->company);
if (!$template['status']) {
    end_response(401, $template['message']); 
}

$m = new Mustache_Engine([
    'entity_flags' => ENT_QUOTES,
    'escape' => function($value) { return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');},
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates/'.$template['name'].'/', ['extension' => '.html'])
    ]);


// check for files with r.s.t data


echo $m->loadTemplate('home')->render(['company' => $data->company]);


