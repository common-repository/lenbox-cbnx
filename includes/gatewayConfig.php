<?php

function lenbox_get_gateway_config($gate_id)
{
    $config = array(
        "lenbox_floa_cbnx" => array(
            "demande_api" => "/api/1.1/wf/getformsplit",
            "status_api" => "/api/1.1/wf/getformstatus",
            "type_demande" => "cbnx",
        ),
        "lenbox_carte" => array(
            "demande_api" => "/api/1.1/wf/getform1x",
            "status_api" => "/api/1.1/wf/getformstatus",
            "type_demande" => "carte",
        )
    );
    return array_key_exists($gate_id, $config) ? $config[$gate_id] : false;
}
