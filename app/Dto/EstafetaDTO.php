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

use Carbon\Carbon;

#CLASES DE NEGOCIO 
use App\Models\LtdTipoServicio;


use Illuminate\Validation\ValidationException;

/**
 * 
 */
class EstafetaDTO 
{
    public $data = null;
    private $ltdTipoServicio = null; 
	
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

    public function parser(array $data, $canal = 'API', array $empresas){
        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." INICIO");

        $this->ltdTipoServicio = LtdTipoServicio::where('service_id',$data['servicio_id'])
            ->where('ltd_id',2)
            //->whereIN('empresa_id',$empresas)
            ->where('estatus',1)
            ->first()
            
            ;

        Log::debug(($this->ltdTipoServicio)); 

        if ( !isset($this->ltdTipoServicio->client_id)) {
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            throw ValidationException::withMessages(array("No Existen Servicios, Favor de Validar con el Administrador"));
        }
        
        
        $identification = new Identification([
                    'suscriberId' => $this->ltdTipoServicio->client_id 
                    ,'customerNumber' =>  $this->ltdTipoServicio->customer_number
                ]
            );
        $systemInformation = new SystemInformation();
       
        if($canal === "WEB"){
            Log::info(__CLASS__." ".__FUNCTION__." WEB");

            $labelDefinition = new labelDefinition([
                'wayBillDocument'   => $this->wayBillDocument($data)
                ,'itemDescription'  => $this->itemDescription($data)
                ,'serviceConfiguration'=> $this->serviceConfiguration($data, $empresas)
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

        Log::debug(__CLASS__." ".__FUNCTION__." FIN");
        return $body;
    }//fin public function parser


    private function itemDescription($data){
        Log::debug(__CLASS__." ".__FUNCTION__." itemDescription INICIO -----------------");
        $itemDescription = new ItemDescription();

        $itemDescription->weight =$data['peso_facturado'];

        foreach ($data['pesos'] as $key => $value) {
            
            $itemDescription->length +=$data['largos'][$key];
            $itemDescription->width  +=$data['anchos'][$key];
            $itemDescription->height += $data['altos'][$key];
        }
        

        Log::debug(__CLASS__." ".__FUNCTION__." itemDescription FIN -----------------");
        return $itemDescription;
    }


    private function wayBillDocument($data){
        Log::debug(__CLASS__." ".__FUNCTION__." wayBillDocument INICIO -----------------");


        $wayBillDocument = new WayBillDocument();
        if( !empty($data['contenido']) ){
            $wayBillDocument->content=str_split($data['contenido'],25)[0]; 
        }

        if ( strlen($data['direccion2_d']) < 2   ) {
            $aditionalInfo = "NA";
        } else {
            $aditionalInfo = str_split($data['direccion2_d'],25)[0]; 
        }
        

        $wayBillDocument->aditionalInfo = $aditionalInfo;
        Log::debug(__CLASS__." ".__FUNCTION__." wayBillDocument FIN -----------------");
        return $wayBillDocument;
    }


    private function serviceConfiguration($data, $empresas){
        Log::debug(__CLASS__." ".__FUNCTION__." serviceConfiguration INICIO -----------------");
        
        $serviceConfiguration = new ServiceConfiguration();
        $serviceConfiguration->quantityOfLabels = $data['piezas'];
 
        $serviceConfiguration->serviceTypeId = $this->ltdTipoServicio->service_id_ltd;
        $serviceConfiguration->originZipCodeForRouting = $data['cp'];
        $serviceConfiguration->salesOrganization= $this->ltdTipoServicio->sales_organization ;
        

        $serviceConfiguration->isInsurance=false;
        if ($data['bSeguro']==="true"){
            $serviceConfiguration->isInsurance = true;

            $declaredValue = sprintf("%.2f",$data['valor_envio']);
            $serviceConfiguration->insurance = array('contentDescription' => str_split($data['contenido'],100)[0]
                ,'declaredValue' => $declaredValue);
        }

        $serviceConfiguration->effectiveDate=Carbon::now()->addMonth()->format('Ymd');

        Log::debug(__CLASS__." ".__FUNCTION__." serviceConfiguration FIN -----------------");
        return $serviceConfiguration;
    }

    private function insurance($data){
        Log::debug(__CLASS__." ".__FUNCTION__." insurance INICIO -----------------");

        $insurance = new Insurance();
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

        $address = new Address();
        $address->zipCode = $data['cp'];
        $address->roadName = str_split($data['direccion'],50)[0];
        $address->settlementName = str_split($data['colonia'],57)[0];
        $address->externalNum = $data['no_ext'] ;
        $address->indoorInformation = (empty($data['no_int']) ? "" : $data['no_int']);

        $contact = new Contact();
        $contact->corporateName= str_split($data['nombre'],50)[0];
        $contact->contactName= str_split($data['contacto'],30)[0];
        $contact->cellPhone=$data['celular'];
        

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

        $address = new Address();

        $address->zipCode = $data['cp_d'];
        $address->roadName = str_split($data['direccion_d'],50)[0];
        $address->settlementName = $data['colonia_d'];
        $address->externalNum = $data['no_ext_d'];
        $address->indoorInformation = (empty($data['no_int_d']) ? "" : $data['no_int_d']); 
        
        $contact = new Contact();        
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
        $address->roadName = str_split($data['direccion_d'],50)[0];
        $address->settlementName = $data['colonia_d'];
        $address->externalNum = $data['no_ext_d'];
        $address->indoorInformation = (empty($data['no_int_d']) ? "" : $data['no_int_d']);
        
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
