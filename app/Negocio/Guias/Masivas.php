<?php

namespace App\Negocio\Guias;

use Log;
use File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use App\Models\Guias\Masivas as mMasivas;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\Guia;
use App\Models\GuiasPaquete;

use App\Singlenton\Estafeta as sEstafeta;
use App\Singlenton\Fedex;
use App\Singlenton\Redpack as sRedpack;
use App\Singlenton\Dhl as sDhl;

use App\Dto\Guia as GuiaDTO;
use App\Dto\EstafetaDTO;
use App\Dto\FedexDTO;
use App\Dto\RedpackDTO;
use App\Dto\DhlDTO;

use App\Negocio\Guias\Cotizacion as nCotizacion;
use App\Negocio\Saldos\Saldos as nSaldos;

class Masivas {

	private $mensaje = array();
    private $trackingNumbers = null;
    private $tabla = array();
    private $insertGuia = array();
    private $namePdf = "";
    private $fedex = null;
    private $tarifa = array();
    private $csvHandle = null;
    private $nameCsv = "";
    
 
    /**
     * Metodo crear, se busca  insert guias basado en un csv
     * 
     * @param array $parametros
     * @return void
     */

    public function crear($file){
    	Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
        $date = Carbon::now();

        $numeroDeRegistros = 0;
        $numeroDeFallos = 0;
        $registroExitoso = 0;

        $ids = "";
        $fileNombre = $file->getClientOriginalName();

        $csvFile = fopen($file->getRealPath(), "r");
        $cabecera = fgetcsv($csvFile);
        //Log::debug(print_r($cabecera,true));
        
        $this->reporteFalloCsvInicio();
        $insertMasiva = array();
        while (($row = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            $numeroDeRegistros++;
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." INICIA NUEVA LINEA ================================= $numeroDeRegistros");
            $data = array("esManual"=>"SI");
            foreach ($cabecera as $key => $value) {
                //Log::debug(print_r("$key => $value",true));
                $data[$value] = $row[$key];
            }

            
            $empresa_id = $data['empresa_id'];
            $mensaje = "";
            try {
                $cliente = new Cliente();
                $cliente->validaCliente($data);
                if ( !$cliente->getExiste() ) {
                    Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Cliente no existe");
            
                    $cliente->insertSemiManual($data);
                }
                $data['cliente_id']=$cliente->getId();

                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $remitente = new Sucursal();
                $remitente->existe($data);
                
                if ( !$remitente->getExiste() ) {
                    Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                    $remitente->insertParse($data);
                }
                $data['sucursal_id']=$remitente->getId();
                $data['sucursal'] = $data['sucursal_id'];
                
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $data = $this->valoresCotizacion($data);

                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $this->tarifa($data);
                
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $data = $this->calculoPrecio($data);

                
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $empresas[]=2;
                $data['pesos'][]=$data['peso'];
                $data['largos'][]=$data['largo'];
                $data['anchos'][]=$data['ancho'];
                $data['altos'][]=$data['alto'];
                
                Log::debug(print_r($data,true));
                switch ($data['ltd_id']) {
                    case 1:
                        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                        $this->fedex($data);
                        $this->recurenciaPorDocumento($data);
                        break;
                    case 2:
                        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                        $this->estafeta($data,$empresas,$empresa_id );
                        $this->recurenciaPorTracking($data);
                        break;
                    case 3:
                        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                        $this->redpack($data,$empresa_id);
                        
                        break;
                    case 4:
                        $this->dhl($data,$empresa_id);
                        
                        break;
                    
                    default:
                        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                        Log::debug("Default agregar Exeption");
                        break;
                }
                $saldo = new nSaldos();
                $saldo->menosPrecio($data["sucursal_id"], $data["precio"]);
                $registroExitoso++;
                continue;
            
            } catch (ValidationException $e) {
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." ValidationException");
                $mensaje = $e->getMessage();
                Log::debug(print_r( $mensaje,true)   );
                $numeroDeFallos++;

            } catch (\GuzzleHttp\Exception\ServerException $e){
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." \GuzzleHttp\Exception\ServerException");
                $mensaje = $e->getResponse()->getBody()->getContents();
                Log::debug(print_r($mensaje,true));
                $numeroDeFallos++;

            } catch (\Exception $e) {
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Exeption");
                $mensaje = $e->getMessage();
                Log::debug(print_r($mensaje,true));
                $numeroDeFallos++;
            }

            $this->reporteFalloCsvAgregarRegistro($data['id'],$mensaje);
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            
            
        } //fin While
        fclose($csvFile);
        
        $this->reporteFalloCsvCerrar();
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $insertMasiva['user_id'] = auth()->user()->id;
        $insertMasiva['archivo_nombre']= $fileNombre;
        $insertMasiva['no_registros'] = $numeroDeRegistros;
        $insertMasiva['no_registros_fallo'] = $numeroDeFallos;
        $insertMasiva['archivo_fallo'] = $this->nameCsv;

        mMasivas::create($insertMasiva);

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
        $this->mensaje[]= sprintf("%s",$date->diffForHumans());
        $this->mensaje[]= sprintf("%s/%s registros insertados ",$registroExitoso,$numeroDeRegistros);
        
    }

	/**
     * Metodo tabla, Se obtienen los datos para armar la tabla  
     * 
     * @param array $parametros
     * @return void
     */

    public function tabla(){
    	Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

            $this->tabla = mMasivas::select("masivas.id","masivas.created_at", "user_id", "no_registros", "archivo_nombre" , "archivo_fallo"
                , "users.name")
                ->joinUsuario()
                ->where('masivas.created_at', '>', now()->subDays(30)->endOfDay())
                ->orderBy("masivas.id","desc")
                ->get()->toArray();
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
    
    }

    /**
     * Se obtienen los datos para armar el insert de estafeta
     * 
     * @param array $parametros
     * @return void
     */
    private function estafeta($data,$empresas,$empresa_id ){

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $dto = new EstafetaDTO();
        $body = $dto->parser($data,"WEB",$empresas);

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $sEstafeta = new sEstafeta($empresa_id );
        $sEstafeta -> envio($body);
        $resultado = $sEstafeta->getResultado();

        Log::debug(print_r($sEstafeta->getTrackingNumber() ,true));

        $this->trackingNumbers = explode("|", $sEstafeta->getTrackingNumber());
        Log::debug(print_r($this->trackingNumbers ,true));            
        

        $carbon = Carbon::now();
        $unique = md5( (string)$carbon);
        $carbon->settings(['toStringFormat' => 'Y-m-d-H-i-s.u']);
        $this->namePdf = sprintf("%s-%s-%s.pdf",(string)$carbon,$empresa_id,$unique);
        Storage::disk('public')->put($this->namePdf,base64_decode($sEstafeta->documento));

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $this->insertGuia = GuiaDTO::estafeta($sEstafeta,$data,"WEB");

    }



    /**
     * Se obtienen los datos para armar el insert de estafeta
     * 
     * @param array $parametros
     * @return void
     */
    private function redpack($data,$empresa_id ){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $redpackDTO = new RedpackDTO();
        $etiqueta = $redpackDTO->parser($data);
        if($redpackDTO->getRangoExcedido()){
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $mensaje = array("REDPACK RANGO EXCEDIDO EN FOLIOS");
            throw ValidationException::withMessages($mensaje);
        }

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $redpack = new sRedpack( $empresa_id );
        $redpack->documentation( $etiqueta );

        $boolPrecio = true;
        $i=1;
        $numeroDeSolicitud = Carbon::now()->timestamp;
        $notices = array("Número de Solicitud: $numeroDeSolicitud ");
        foreach ($redpack->getDocumento() as $key => $value) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Documento");
            Log::info(print_r($value,true));

            $carbon = Carbon::parse();
            $carbon->settings(['toStringFormat' => 'Y-m-d-H-i-s.u']);
            $unique = md5( (string)$carbon);
            $namePdf = sprintf("%s-doc-%s-%s.pdf",(string)$carbon,$key,$unique);

            Storage::disk('public')->put($namePdf,base64_decode( $value->label ));

            $guiaDTO = new GuiaDTO();
            $guiaDTO->parseoRedpack($data,$redpack, "WEB", $namePdf);
            $insert = $guiaDTO->getInsert();

            $insert['numero_solicitud'] = $numeroDeSolicitud;
            Log::debug(print_r($insert ,true));

            if ($i > 1) {
                Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." Limpiar costos");
                $insert = nGuia::costosEnCero( $insert );
            } 
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Guia::create");

            $id = Guia::create($insert)->id;
            $notices[] = sprintf("El registro de la solicitud se genero con exito con el ID %s ", $id);

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." GuiasPaquete::create");

            $precioUnitario = 0;
            if ($boolPrecio ){
                $precioUnitario = $data['precio'];
            }
            $boolPrecio = false;

            if ( $data['piezas'] === 1 ) {
                $guiaPaqueteInsert = array(
                    'peso' => $data['pesos'][$key]
                    ,'alto' => $data['altos'][$key]
                    ,'ancho' => $data['anchos'][$key]
                    ,'largo' => $data['largos'][$key]
                    ,'precio_unitario' => $data
                    ,'guia_id' => $id
                );    
                
            } else {
                if (count($data['pesos']) ===1) {
                    $key = 0;
                }
                $guiaPaqueteInsert = array(
                    'peso' => $data['pesos'][$key]
                    ,'alto' => $data['altos'][$key]
                    ,'ancho' => $data['anchos'][$key]
                    ,'largo' => $data['largos'][$key]
                    ,'precio_unitario' => $precioUnitario
                    ,'guia_id' => $id
                );    

            }
            
            $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
            $i++;
        }

    }//fin function estafeta

    /**
     * Se obtienen los datos para armar una peticion de DHL
     * 
     * @param array $parametros
     * @return void
     */
    private function dhl($data,$empresa_id ){

        $dto = new DhlDTO();
        $dto->parser($data);
        $etiqueta = $dto->getBody();

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

        $sDhl = new sDhl();
        $sDhl->documentation( $etiqueta );

        $boolPrecio = true;
        $i=1;
        $numeroDeSolicitud = Carbon::now()->timestamp;
        $notices = array("Número de Solicitud: $numeroDeSolicitud ");
        foreach ($sDhl->getDocumento() as $key => $value) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Documento");
            
            $carbon = Carbon::parse();
            $carbon->settings(['toStringFormat' => 'Y-m-d-H-i-s.u']);
            $unique = md5( (string)$carbon);
            $namePdf = sprintf("%s-doc-%s-%s.pdf",(string)$carbon,$key,$unique);

            Storage::disk('public')->put($namePdf,base64_decode( $value->content ));

            $guiaDTO = new GuiaDTO();
            $guiaDTO->parseoDhl($data,$sDhl, "WEB", $namePdf);
            $insert = $guiaDTO->getInsert();

            $insert['numero_solicitud'] = $numeroDeSolicitud;
            Log::debug(print_r($insert ,true));

            if ($i > 1) {
                Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." Limpiar costos");
                $insert = nGuia::costosEnCero( $insert );
            }   

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Guia::create");

            $id = Guia::create($insert)->id;
            $notices[] = sprintf("El registro de la solicitud se genero con exito con el ID %s ", $id);

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." GuiasPaquete::create");

            $precioUnitario = 0;
            if ($boolPrecio ){
                $precioUnitario = $data['precio'];
            }
            $boolPrecio = false;

            if ( $data['piezas'] === 1 ) {
                $guiaPaqueteInsert = array(
                    'peso' => $data['pesos'][$key]
                    ,'alto' => $data['altos'][$key]
                    ,'ancho' => $data['anchos'][$key]
                    ,'largo' => $data['largos'][$key]
                    ,'precio_unitario' => $precioUnitario
                    ,'guia_id' => $id
                );    
                
            } else {
                if (count($data['pesos']) ===1) {
                    $key = 0;
                }
                $guiaPaqueteInsert = array(
                    'peso' => $data['pesos'][$key]
                    ,'alto' => $data['altos'][$key]
                    ,'ancho' => $data['anchos'][$key]
                    ,'largo' => $data['largos'][$key]
                    ,'precio_unitario' => $precioUnitario
                    ,'guia_id' => $id
                );    

            }
            
            $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
            $i++;
        }
       
        Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);

    }


    /**
     * Se obtienen los datos para armar el insert de estafeta
     * 
     * @param array $parametros
     * @return void
     */
    private function recurenciaPorTracking($data){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $boolPrecio = true;
        $i=1;
        $numeroDeSolicitud = Carbon::now()->timestamp;
        $notices = array("Número de Solicitud: $numeroDeSolicitud ");
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        foreach ($this->trackingNumbers as $key => $trackingNumber) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $this->insertGuia['tracking_number'] = $trackingNumber;
            $this->insertGuia['documento'] = $this->namePdf;
            $this->insertGuia['numero_solicitud'] = $numeroDeSolicitud;
            Log::debug(print_r($this->insertGuia ,true));

            if ($i > 1) {
                Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." Limpiar costos");
                $this->insertGuia = nGuia::costosEnCero( $this->insertGuia );
            }
            $this->insertGuia['canal'] = "MSV";   
            $id = Guia::create($this->insertGuia)->id;
            $notices[] = sprintf("El registro de la solicitud se genero con exito con el ID %s ", $id);

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $guiaPaqueteInsert = GuiaDTO::validaPiezasPaquete($data, $key, $boolPrecio, $id);
            $boolPrecio = false;

            $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." idGuiaPaquite =$idGuiaPaquite");
            $i++;
        }


    }

    /**
     * Se obtienen los datos para armar el insert de estafeta
     * 
     * @param array $parametros
     * @return void
     */

    private function fedex($data ){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $fedexDTO = new FedexDTO();
        $etiqueta = $fedexDTO->parser($data);

        $this->fedex = Fedex::getInstance(Config('ltd.fedex.id'));
        $this->fedex->envio( json_encode($etiqueta, JSON_UNESCAPED_UNICODE));

        Log::info(__CLASS__." ".__FUNCTION__." GuiaDTO");
        $guiaDTO = new GuiaDTO();
        $guiaDTO->parseoFedex($data,$this->fedex, "WEB");
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $this->insert = $guiaDTO->getInsert();
    }

    private function recurenciaPorDocumento($data){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $boolPrecio = true;
        $i=1;
        $numeroDeSolicitud = Carbon::now()->timestamp;
        $notices = array("Número de Solicitud: $numeroDeSolicitud ");
        foreach ($this->fedex->getDocumentos() as $key => $documento) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            
            $this->insert['tracking_number'] = $documento->trackingNumber;
            $this->insert['documento'] = $documento->packageDocuments[0]->url;
            $this->insert['numero_solicitud'] = $numeroDeSolicitud;
            Log::debug(print_r($this->insert ,true));

            if ($i > 1) {
                Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." Limpiar costos");
                $this->insert = nGuia::costosEnCero( $this->insert );
            }   
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Guia::create");
            $this->insert['canal'] = "MSV";
            $id = Guia::create($this->insert)->id;
            $notices[] = sprintf("El registro de la solicitud se genero con exito con el ID %s ", $id);


            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $guiaPaqueteInsert = GuiaDTO::validaPiezasPaquete($data, $key, $boolPrecio, $id);
            $boolPrecio = false;

            $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." idGuiaPaquite =$idGuiaPaquite");
            $i++;
        }

    }


    /**
     * Se busca obtener una tarifa unica para la cotizacion
     * 
     * 
     * @param array $parametros
     * @return void
     */

    private function tarifa($data)
    {
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $nCotizacion = new nCotizacion();
        $nCotizacion->base($data,$data['ltd_id']);
        $tarifas = $nCotizacion->getTabla();
        Log::debug(print_r($tarifas,true));
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
        if (count($tarifas)==0) {
            $mensaje[] = sprintf("No se cuenta con tarifas ");
            throw ValidationException::withMessages($mensaje);
        }


        foreach ($tarifas as $key => $tarifa) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            if ( $nCotizacion->getTipoPagoId()==2 ) {
                $saldoMinimo = 90; 
                $saldo = $nCotizacion->getSaldo();

                if ($saldo < $saldoMinimo) {
                    $mensaje[] = sprintf("El Saldo: %s es menor al limite permitido",$saldo);
                    throw ValidationException::withMessages($mensaje);
                }

                if ($saldo < 0) {
                    $mensaje[] = sprintf("Saldo Negativo: $%s",$saldo);
                    throw ValidationException::withMessages($mensaje);
                }
                
            }

            $this->tarifa = $tarifa;
        }
        
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
    }//private function cotizacion()

    /**
     * Se busca cotizar con datos minomos y obtener el costo 
     * y validacion del saldo del cliente en cuestion
     * 
     * @param array $data
     * @return array data 
     */

    private function valoresCotizacion($data){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
        $data['costo_kg_extra']= 0;
        $data['costo_seguro'] = 0;
        $data['costo_extendida'] = 0;
        $data['sobre_peso_kg'] =0;
        $data['bSeguro'] = false;
       
        $data['peso_bascula'] = $data['peso'];
        $data['peso_dimensional'] = ($data['alto']*$data['ancho']*$data['largo'])/5000;
        
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $data['peso_facturado'] = ($data['peso_bascula'] > $data['peso_dimensional']) ? ceil($data['peso_bascula']) : ceil($data['peso_dimensional']) ;
        
        $data['pesoFacturado']=$data['peso_facturado'];

        
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $data['subPrecio'] = $data['costo_kg_extra']+$data['costo_seguro']+$data['costo_extendida'];


        return $data;
    }//private function cotizacion()

    private function calculoPrecio($data) {
        $data['zona'] = $this->tarifa['zona'];
        $data['costo_base'] = $this->tarifa['costo'];
        //Calcula sobre peso
        if ($data['peso_facturado'] > $this->tarifa['kg_fin'] ) {
            
            $data['sobre_peso_kg'] = $data['peso_facturado'] - $this->tarifa['kg_fin'];
            $data['costo_kg_extra'] = $data['sobre_peso_kg'] * $this->tarifa['kg_extra'];
        }
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        //valida Seguro
        if ($data['valor_envio'] > 0) {
            $data['costo_seguro'] = ($data['valor_envio']* $this->tarifa['seguro'])/100;
            $data['bSeguro'] = true;

        }

        $data['extendida'] = $this->tarifa['extendida_cobertura'];
        //Valida area extendida
        if ( $this->tarifa['extendida_cobertura'] === "SI"){
            $data['costo_extendida'] = $this->tarifa['extendida'];
            
        }
        $data['subPrecio'] = $data['subPrecio']+$data['costo_base'];
        $data['precio'] = round($data['subPrecio']*1.16, 2);
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        return $data;
    } 

    private function reporteFalloCsvInicio(){

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        $carbon = Carbon::parse();
        $carbon->settings(['toStringFormat' => 'Y-m-d_hms']);
        $timestamps = Carbon::now()->timestamp;
        $this->nameCsv = sprintf("csv/guias/masivas/%s-reportefallo.csv"
                ,(string)$carbon);


        $parametros['archivo_fallo'] =$this->nameCsv; 
        $filename =  public_path($this->nameCsv);
        $this->csvHandle = fopen($filename, 'w');

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$this->nameCsv.'"');
        header("Content-Transfer-Encoding: binary");
            
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        fputcsv($this->csvHandle, [
            "ID REGISTRO",
            "COMENTARIOS"
            
        ]);

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        
    }

    private function reporteFalloCsvAgregarRegistro($id, $mensaje){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        fputcsv($this->csvHandle, [
            $id, $mensaje
        ]);

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

    }

    private function reporteFalloCsvCerrar(){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
        fclose($this->csvHandle);
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);

    }


	public function getMensajes ()
    {
        return $this->mensaje;
    }

    public function getTabla()
    {
        return $this->tabla;
    }

}