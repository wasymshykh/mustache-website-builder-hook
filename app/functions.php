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

function validated_template($company)
{
    if (!property_exists($company, 'template')) {
        return ['status' => false, 'message' => 'template is not sent!'];
    }

    $template_name = $company->template;
    $template_path =  DIR . '/templates/'.$template_name;
    if (!file_exists($template_path)) {
        return ['status' => false, 'message' => 'template does not exists'];
    }

    return ['status' => true, 'name' => $template_name];

}

