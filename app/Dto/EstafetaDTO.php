<?php
namespace App\Dto;

use Log;

use App\Dto\Estafeta\API\V3\Label;
use App\Dto\Estafeta\API\V3\Identification;
use App\Dto\Estafeta\API\V3\SystemInformation;
use App\Dto\Estafeta\API\V3\LabelDefinition;
use App\Dto\Estafeta\API\V3\WayBillDocument;
use App\Dto\Estafeta\API\V3\ItemDescription;
use App\Dto\Estafeta\API\V3\ServiceConfiguration;
use App\Dto\Estafeta\API\V3\Location;
use App\Dto\Estafeta\API\V3\Insurance;
use App\Dto\Estafeta\API\V3\LocationLabel;
use App\Dto\Estafeta\API\V3\Destination;
use App\Dto\Estafeta\API\V3\Notified;
use App\Dto\Estafeta\API\V3\Contact;
use App\Dto\Estafeta\API\V3\Address;


/**
 * 
 */
class EstafetaDTO 
{
    public $data = null;
	
	function __construct()
	{
		// code...
	}

	function init( ){

		Log::info(__CLASS__." ".__FUNCTION__); 

        
		$Dralternativeinfo = new DrAlternativeInfo();  

        try{

            /* Se inicializa el WS para DEV*/
            $wsdl = config('ltd.estafeta');
            $path_to_wsdl = sprintf("%s%s",resource_path(), $wsdl );
            Log::debug($path_to_wsdl);

            Log::debug($this->data);
            $labelDTO = new Label($this->data);
			Log::debug(serialize($labelDTO));

            $client = new \SoapClient($path_to_wsdl, array('trace' => 1));
            ini_set("soap.wsdl_cache_enabled", "0");
            $response =$client->createLabel($labelDTO);

            Log::debug(__CLASS__." ".__FUNCTION__." ");
            Log::debug(print_r($response->globalResult,true));
            Log::debug(print_r($response->labelResultList,true));
            Log::info(__CLASS__." ".__FUNCTION__." Fin Try");
            return response()->json([
                'codigo' => $response->globalResult->resultCode,
                'descripcion' => $response->globalResult->resultDescription
                ,'pdf'  => base64_encode($response->labelPDF)
            ]);
                 
        
        } catch (DataTransferObjectError $exception) {
            Log::info(__CLASS__." ".__FUNCTION__." DataTransferObjectError "); 
            
            return response()->json([
                'codigo' => "11",
                'descripcion' => $exception->getMessage()
                ,'pdf'  => null
                ]);

        } catch(Exeption $e){
            Log::info(__CLASS__." ".__FUNCTION__."Exeption ");
        	return response()->json([
                'codigo' => "11",
                'descripcion' => $e->getMessage()
                ,'pdf'  => null
                ]);
        }


	}

     /**
     * Metodo para asignar los valores del request a una etiqueta 
     * para generar el venvio a la api de Fedex
     * 
     * 
     * @param \Illuminate\Http\Request $request
     * @return App\Dto\Fedex\Etiqueta Etiqueta
     */

    public function parser(array $data, $canal = 'API'){
        Log::debug(__CLASS__." ".__FUNCTION__." INICIO");

        $identification = new Identification([
                    'suscriberId' =>   Config('ltd.estafeta.cred.suscriberId')
                    ,'customerNumber' =>   Config('ltd.estafeta.cred.customerNumber')
                ]
            );
        $systemInformation = new SystemInformation();


        Log::debug(print_r($data,true));
       
        if($canal === "WEB"){
            Log::info(__CLASS__." ".__FUNCTION__." WEB");
            
            //$location = new LocationLabel();


            $labelDefinition = new labelDefinition([
                'wayBillDocument'   => $this->wayBillDocument($data)
                ,'itemDescription'  => $this->itemDescription($data)
                ,'serviceConfiguration'=> $this->serviceConfiguration($data)
                ,'location'         => $this->locationLabel($data)
                ]
            );

        } else {
            $labelDefinition = $data['labelDefinition'];
        }

        $body = new Label(
            [
                "identification" => $identification
                ,"systemInformation" => $systemInformation
                ,"labelDefinition"  => $labelDefinition
            ]
        );

        Log::debug("Label armada--------------------------");
        Log::debug(print_r(json_encode($body),true));

        Log::debug(__CLASS__." ".__FUNCTION__." FIN");
        return $body;
    }//fin public function parser


    private function itemDescription($data){
        Log::debug(__CLASS__." ".__FUNCTION__." itemDescription INICIO -----------------");
        $itemDescription = new ItemDescription();

        $itemDescription->weight =$data['peso_facturado'];
        $itemDescription->height =$data['alto'];
        $itemDescription->length =$data['largo'];
        $itemDescription->width  =$data['ancho'];

        Log::debug(__CLASS__." ".__FUNCTION__." itemDescription FIN -----------------");
        return $itemDescription;
    }


    private function wayBillDocument($data){
        Log::debug(__CLASS__." ".__FUNCTION__." wayBillDocument INICIO -----------------");
        $wayBillDocument = new WayBillDocument();

        Log::debug(__CLASS__." ".__FUNCTION__." wayBillDocument FIN -----------------");
        return $wayBillDocument;
    }


    private function serviceConfiguration($data){
        Log::debug(__CLASS__." ".__FUNCTION__." serviceConfiguration INICIO -----------------");
        
        //["insurance"=>$this->insurance($data)]
        $serviceConfiguration = new ServiceConfiguration();
        
        $serviceConfiguration->serviceTypeId = Config('ltd.estafeta.servicio')[$data['servicio_id']];
        $serviceConfiguration->originZipCodeForRouting = $data['cp'];
        $serviceConfiguration->salesOrganization=Config('ltd.estafeta.cred.salesOrganization');

        $serviceConfiguration->isInsurance= false;//$data['bSeguro']; 

        Log::debug(__CLASS__." ".__FUNCTION__." serviceConfiguration FIN -----------------");
        return $serviceConfiguration;
    }

    private function insurance($data){
        Log::debug(__CLASS__." ".__FUNCTION__." insurance INICIO -----------------");

        $insurance = new Insurance();
        //$insurance->declaredValue= $data['']
        Log::debug(__CLASS__." ".__FUNCTION__." insurance FIN -----------------");
        return $insurance;
    }


    private function locationLabel($data){
        Log::debug(__CLASS__." ".__FUNCTION__." locationLabel INICIO -----------------");

        
        $locationLabel = new LocationLabel(
            [
                'origin'=>$this->origin($data)
                ,'destination'=>$this->destination($data)
                ,'notified'=>$this->notified($data)
            ]
        );

        Log::debug(__CLASS__." ".__FUNCTION__." locationLabel FIN -----------------");
        return $locationLabel;
    }

    private function origin($data){
        Log::debug(__CLASS__." ".__FUNCTION__." origin INICIO -----------------");

        $contact = new Contact();
        $address = new Address();

        $address->zipCode = $data['cp'];
        $address->roadName = $data['direccion'];
        $address->settlementName = $data['colonia'];
        $address->externalNum = $data['no_ext'];

        $contact->corporateName=$data['nombre'];
        $contact->contactName=$data['contacto'];
        $contact->cellPhone=$data['celular'];
        //$contact->=$data[''];

        $origin = new Location(
            [
                'contact'=> $contact
                ,'address'=> $address
            ]
        );

        Log::debug(__CLASS__." ".__FUNCTION__." origin FIN -----------------");
        return $origin;
    }

    private function destination($data){
        Log::debug(__CLASS__." ".__FUNCTION__." destination INICIO -----------------");

        $contact = new Contact();
        $address = new Address();

        $address->zipCode = $data['cp_d'];
        $address->roadName = $data['direccion_d'];
        $address->settlementName = $data['colonia_d'];
        $address->externalNum = $data['no_ext_d'];

        $contact->corporateName=$data['nombre_d'];
        $contact->contactName=$data['contacto_d'];
        $contact->cellPhone=$data['celular_d'];


        $homeAddress = new Location(
            [
                'contact'=> $contact
                ,'address'=> $address
            ]
        );

        $destination = new Destination(['homeAddress'=>$homeAddress]);

        Log::debug(__CLASS__." ".__FUNCTION__." destination FIN -----------------");
        return $destination;
    }

    private function notified($data){
        Log::debug(__CLASS__." ".__FUNCTION__." notified INICIO -----------------");

        $contact = new Contact();
        $address = new Address();

        $address->zipCode = $data['cp_d'];
        $address->roadName = $data['direccion_d'];
        $address->settlementName = $data['colonia_d'];
        $address->externalNum = $data['no_ext_d'];

        $contact->corporateName=$data['nombre_d'];
        $contact->contactName=$data['contacto_d'];
        $contact->cellPhone=$data['celular_d'];


        $residence = new Location(
            [
                'contact'=> $contact
                ,'address'=> $address
            ]
        );

        $notified = new Notified( ['residence'=>$residence]);

        Log::debug(__CLASS__." ".__FUNCTION__." notified FIN -----------------");
        return $notified;
    }
}

?>