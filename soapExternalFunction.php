<?php

require_once "vendor/econea/nusoap/src/nusoap.php";

$namespace = "soap2020.tolls_ms_by_id";
$server = new soap_server();
$server->configureWSDL("TollByIdSOAP",$namespace);
$server->wsdl->schemaTargetNamespace = $namespace;

$server->wsdl->addComplexType(
    'tollIdQuery',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'tollId' => array('name' => 'NumeroOrden', 'type'=>'xsd:integer'),
    )
);

$server->wsdl->addComplexType(
    'response',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'tollId' => array('name' => 'tollId', 'type' => 'xsd:integer'),
        'name' => array('name'=>'name', 'type'=>'xsd:string'),
        'coor_lat' => array('name'=>'coor_lat', 'type'=>'xsd:double'),
        'coor_lng' => array('name'=>'coor_lng', 'type'=>'xsd:double')
    )
);

$server->register(
    'findTollById',
    array('name' => 'tns:tollIdQuery'),
    array('name' => 'tns:response'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Recibe un id de un toll y devuelve todos sus atributos'
);

function findTollById($request){
    $endpoint = "http://54.89.0.221:80/graphql";

    $query = "query {
        tollById(tollId: ".$request["tollId"].") {
          tollId
          name
          coor_lat
          coor_lng
        }
      }";

    $data = array ('query' => $query);
    $data = http_build_query($data);

    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => $data,
            'header'=> "Content-type: application/x-www-form-urlencoded"
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents(sprintf($endpoint), false, $context);

    $result = json_decode($result);

    if ($result->data->tollById === null) { 
        return array(
            "name" => "Peaje no encontrado",
        );
    }

    return array(
        "tollId" => $result->data->tollById->tollId,
        "name" => $result->data->tollById->name,
        "coor_lat" => $result->data->tollById->coor_lat,
        "coor_lng" => $result->data->tollById->coor_lng
    );
}

$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();