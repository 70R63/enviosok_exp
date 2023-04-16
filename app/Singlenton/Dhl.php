<?php

namespace App\Singlenton;

use GuzzleHttp\Client;
use Log;
use Carbon\Carbon;
use Config;


/**
* Singlenton para contriuir una peticion de creacion de guia y validacion del token
* 
* @param string $token
* @param string $baseUri
*  
*/

class Dhl {

    private static $instance;

    private $token;
    private $baseUri;

    public $documento = 0; 
    private $trackingNumber = 0;
    private $scanEvents = array();
    private $paquete = array();
    private $exiteSeguimiento = false;
    private $quienRecibio = "No entregado aun"; 
    private $latestStatusDetail;
    private $fechaEntrega; //Validar uso en la case redpack y no en el controller
    private $pickupFecha;


    public function __construct(){

        
    }


    /**
     * Cliente busca crear una funcion donde se inicialice la peticion via guzzle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return GuzzleHttp\Client $response
     */

    private function clienteRest($body,$metodo = 'GET', $servicio, array $headers){
        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." INICIANDO-----------------");

        $client = new Client(['base_uri' => $this->baseUri]);
        
        $bodyJson = json_encode($body);
        Log::debug(print_r($bodyJson,true));

        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." FINALIZANDO-----------------");
        return $client->request($metodo,$servicio , [
                    'headers'   => $headers
                    ,'body'     => $bodyJson
                ]);
    }


   /**
     * documentation es la actividad para registrar nuestra peticion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function documentation($body){
        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);

        $basic = sprintf("%s:%s", Config('ltd.dhl.api_key'), Config('ltd.dhl.secret') );
        $authorization = sprintf("Basic %s",base64_encode($basic));

        $headers = ['Authorization' => $authorization  
                    ,'Content-Type' => 'application/json'
                ];

        $this->baseUri = Config('ltd.dhl.base_uri');
        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $response = $this->clienteRest($body, 'POST', Config('ltd.dhl.shipment.uri') , $headers);

        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $contenido = json_decode($response->getBody()->getContents());
        
        Log::debug(print_r($contenido,true) );
        $objResponse = $contenido;

        $packages = $objResponse->packages[0];
        
        $this->trackingNumber = $packages->trackingNumber;
        $this->documento = $objResponse->documents;


        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
       
    }

    /**
     * Rastreo busca los estatus con el LTD.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function trackingByNumber(array $body){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

        $authorization = sprintf("Bearer %s",$this->token);

        $headers = ['Authorization' => $authorization  
                    ,'Content-Type' => 'application/json'
                ];

        $this->baseUri = Config('ltd.redpack.rastreo.uri');
        $servicio = Config('ltd.redpack.rastreo.servicio');

        $response = $this->clienteRest($body, 'POST', $servicio, $headers);

        Log::debug(__CLASS__." ".__FUNCTION__." response ");
        $contenido = json_decode($response->getBody()->getContents());

        Log::debug(print_r($contenido,true));

        $pesoDimension = array();
        $objResponse = $contenido[0];
        if ( $objResponse->consumptionResultWS[0]->status === 1 ) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

            foreach ($objResponse->parcel as $key => $value) {
                Log::info(print_r($value,true));
                $pesoDimension['largo'] = $value->length;
                $pesoDimension['ancho'] = $value->width;
                $pesoDimension['alto'] = $value->high;
                $pesoDimension['peso'] = $value->weigth;

            }
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $this->paquete = $pesoDimension;
            $this->latestStatusDetail = $objResponse->lastSituation->idDesc;

            $this->ultimaFecha = "1999-12-31 23:59:59";
            if ($this->latestStatusDetail === 1) {
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $this->ultimaFecha = Carbon::parse($objResponse->dateSituation)->format('Y-m-d H:i:s');
            }
            $this->pickupFecha = $objResponse->dateDocumentation;
            $this->quienRecibio= $objResponse->personReceived;
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $this->exiteSeguimiento = true;

        }else{
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::info("Sin seguimiento ");
            $this->exiteSeguimiento = false;
        }
        
       
    }



    public function getTrackingNumber(){
        return $this->trackingNumber;
    }

    public function getDocumento(){
        return $this->documento;
    }

    
}
