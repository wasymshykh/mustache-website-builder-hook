<?php

function request_body ()
{
    return json_decode(file_get_contents("php://input"));
}

function end_response ($status_code, $message)
{
    http_response_code($status_code);
    echo json_encode(['message' => $message]);
    die();
}

function validated_template ($company)
{
    if (!property_exists($company, 'template')) {
        return ['status' => false, 'message' => 'template is not sent!'];
    }

    $template_name = $company->template;
    $template_path =  DIR . '/templates/'.$template_name;
    if (!file_exists($template_path)) {
        return ['status' => false, 'message' => 'template does not exists'];
    }

    return ['status' => true, 'name' => $template_name, 'path' => $template_path];
}

function get_template_sub_directories (string $directory)
{
    $directories = ['/'];
    $dirs = [];
    $ignore = ['.', '..'];

    while (!empty($directories)) {
        $_dir = array_pop($directories);
        $file_names = scandir($directory . $_dir);

        foreach ($file_names as $file_name) {
            if (!in_array($file_name, $ignore)) {
                $file_path = $directory . $_dir  . $file_name;
                // check if file is an actual directory
                if (filetype($file_path) === 'dir') {
                    array_push($directories, $_dir . $file_name . '/');
                    array_push($dirs, $file_name);
                }
            }
        }
    }

    return $dirs;
}

function build_html_output($file_name, $template_file, $data, $mustache)
{

    $html_content = $mustache->loadTemplate(str_replace ('.html', '', $template_file))->render($data);

    $output_directory = OUTPUT_DIR;
    if (!is_dir($output_directory)) {
        mkdir($output_directory);
    }
    file_put_contents(OUTPUT_DIR . $file_name, $html_content);
}

function get_template_root_files (string $directory)
{
    $ignore = ['.', '..'];

    $files = [];

    $file_names = scandir($directory . '/');

    foreach ($file_names as $file_name) {
        if (!in_array($file_name, $ignore)) {
            $file_path = $directory . '/'  . $file_name;
            if (filetype($file_path) !== 'dir' && pathinfo($file_path)['extension'] === 'html') {
                // if file has .html extention
                array_push($files, $file_name);
            }
        }
    }

    return $files;
}

function clean_directory($directory)
{
    if (is_file($directory)) {
        return unlink($directory);
    } else if (is_dir($directory)) {
        $scan = glob(rtrim($directory,'/').'/*');
        foreach($scan as $index => $path) {
            clean_directory($path);
        }
        return @rmdir($directory);
    }
}
