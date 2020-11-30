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
    'loader' => new Mustache_Loader_FilesystemLoader($template['path'], ['extension' => '.html'])
    ]);


// check for files with r.s.t data
$directories = get_template_files($template['path']);

foreach ($directories as $directory => $files) {
    foreach ($files as $file) {
        
        $data_to_pass = $data;
        if ($directory !== '/') {
            $directory = str_replace('/', '', $directory);
            if (array_key_exists($directory, $data->company->category[0])) {                
                $data_to_pass = $data->company->category[0]->$directory;
            }
        }

        build_html_file($directory, $file, $data_to_pass, $m);
    }
}
