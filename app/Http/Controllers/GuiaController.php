<?php

namespace App\Http\Controllers;

use App\Models\Guia;
use App\Models\EmpresaLtd;
use App\Models\Sucursal;
use App\Models\Cliente;
use App\Models\Cfg_ltd;
use App\Models\Servicio;
use App\Models\GuiasPaquete;
use App\Models\EmpresaEmpresas;

use App\Negocio\Saldos\Saldos;
use App\Negocio\Guias\Creacion as nCreacion;

use App\Mail\GuiaCreada;

use App\Dto\EstafetaDTO;
use App\Dto\FedexDTO;
use App\Dto\RedpackDTO;
use App\Dto\DhlDTO;

use App\Dto\Guia as GuiaDTO;

use App\Singlenton\Estafeta as sEstafeta;
use App\Singlenton\Fedex;
use App\Singlenton\Redpack;
use App\Singlenton\Dhl as sDhl;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Log;
use Mail;
use Config;
use Redirect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;


use App\Negocio\Guia as nGuia;

class GuiaController extends Controller
{

    const INDEX_r = "guia.index";

    const DASH_v = "guia.dashboard";
    const CREAR_v = "guia.crear";
    const EDITAR_v = "guia.editar";
    const SHOW_v = "guia.show";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            $ltdActivo = Cfg_ltd::pluck("nombre","id");            
            $servicioPluck = Servicio::pluck("nombre","id");
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
        /*
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename=zpl.txt');
        header("Content-Transfer-Encoding: binary");
    */
            Log::debug(__CLASS__." ".__FUNCTION__." Return View DASH_v ");
            return view(self::DASH_v 
                    ,compact( "ltdActivo", "servicioPluck")
                );
        } catch (\Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__);
            Log::debug(print_r($e->getMessage,true));
            Log::info("Error general ");       
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            Log::info(__CLASS__." ".__FUNCTION__);   
            $ltdActivo = EmpresaLtd::Ltds()
                    ->pluck("nombre","id");
            
            return view(self::CREAR_v 
                    ,compact("ltdActivo")
                );
            
        } catch (Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__);
            Log::info("Error general ");       
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." inicia ----------------------------");
        Log::debug(print_r($request->all(),true));
 
        if ($request->esManual != "NO") {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." iniciando no es manual ----------------------------");
            try{

                $cliente = new Cliente();
                $cliente->validaCliente($request);

                if ( !$cliente->getExiste() ) {
                    Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Cliente no existe");
            
                    $cliente->insertSemiManual($request);
                }
                
                if ($request->esManual === "SI") {
                    Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." iniciando SI es manual ----------------------------");
                    $remitente = new Sucursal();
                    $remitente->existe($request);
                    
                    if ( !$remitente->getExiste() ) {
                        $remitente->insertParse($request);
                    }
                    $request['sucursal_id']=$remitente->getId();
                } 
                
                $request['cliente_id']=$cliente->getId();
                

            } catch(\Illuminate\Database\QueryException $ex){ 
                Log::info(__CLASS__." ".__FUNCTION__." "."QueryException");
                Log::debug(print_r($ex,true)); 
                $mensaje= array($ex->errorInfo[2]);

                return back()
                ->with('dangers',$mensaje)
                ->withInput();

            } catch (Exception $e) {
                Log::info(__CLASS__." ".__FUNCTION__." "."Exception");
                Log::debug( $e->getMessage() );
                $mensaje= array($e->getMessage());

                return back()
                ->with('dangers',$mensaje)
                ->withInput();
            }

            Log::info(__CLASS__." ".__FUNCTION__."finalizando es manual ----------------------------");
        }//FIN if$request->esManual != "NO"


        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Case para mensajeria");
        switch ($request['ltd_id']) {
            case Config('ltd.fedex.id'):
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." ".Config('ltd.fedex.nombre') );
                return $this->fedex($request);
            break;
            case Config('ltd.estafeta.id'):
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." ".Config('ltd.estafeta.nombre') );
                return $this->estafeta($request);
            break;
            case Config('ltd.redpack.id'):
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." ".Config('ltd.redpack.nombre') );

                return $this->redpack($request);

            break;
            case Config('ltd.dhl.id'):
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." ".Config('ltd.dhl.nombre') );

                return $this->dhl($request);
                
            break;
            default:
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Validar ");
                return back()
                ->with('dangers',array("ERROR INESPERADO VALIDAR CON EL PORVEEDOR"))
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function show(Guia $guia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function edit(Guia $guia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Guia $guia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Guia  $guia
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $mensaje = "Error General, Consulte a su administrador"; 
        try {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            Log::debug($request->all());
            
            $nSaldo = new Saldos();
            $nSaldo->recuperar($request);

            $notices = $nSaldo->getMensaje();
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            return \Redirect::route(self::INDEX_r) -> withSuccess ($notices);


        } catch (ModelNotFoundException $e) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            $mensajeInterno=$e->getMessage();
            Log::debug(print_r($mensajeInterno,true));
            Log::info("ModelNotFoundException");       
            $mensaje = "ModelNotFoundException - Favor de buscar a tu administrador ";
        
        } catch (QueryException $e) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            $mensajeInterno=$e->getMessage();
            Log::debug(print_r($mensajeInterno,true));
            Log::info("QueryException");       
            $mensaje = "QueryException - Favor de buscar a tu administrador ";
        
        } catch (\Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
            $mensaje=$e->getMessage();
            Log::debug(print_r($mensaje,true));
            Log::info("Error general ");       
        }

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__); 
        return \Redirect::back()
                ->withErrors(array($mensaje))
                ->withInput();
    }


    /**
     * Caso de uso pra estafeta 
     *
     * @param  \App\Models\Guia  $guia
     * @param  LTD_ID = 2
     * @return \Illuminate\Http\Response
     */
    private function estafeta($request){
        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." estafeta iniciando ----------------------------");
        $mensaje = array();
        try {

            $requestInicial = $request->except(['_token']);
            $empresa_id = auth()->user()->empresa_id;
            $plataforma = 'WEB';
            
     
            $empresas = EmpresaEmpresas::where('empresa_id',$empresa_id)->pluck('id')->toArray();
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." Singlento Estafeta ");
            $sEstafeta = new sEstafeta($empresa_id,$plataforma );

            $dto = new EstafetaDTO();
            $body = $dto->parser($requestInicial,"WEB",$empresas);

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $sEstafeta -> envio($body);
            $resultado = $sEstafeta->getResultado();

            Log::debug(print_r($sEstafeta->getTrackingNumber() ,true));

            $trackingNumbers = explode("|", $sEstafeta->getTrackingNumber());
            Log::debug(print_r($trackingNumbers ,true));            
            

            $carbon = Carbon::now();
            $unique = md5( (string)$carbon);
            $carbon->settings(['toStringFormat' => 'Y-m-d-H-i-s.u']);
            $namePdf = sprintf("%s-%s-%s.pdf",(string)$carbon,$empresa_id,$unique);
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Storage::disk('public')->put($namePdf,base64_decode($sEstafeta->documento));

            $insert = GuiaDTO::estafeta($sEstafeta,$requestInicial,"WEB");

            $boolPrecio = true;
            $i=1;
            $numeroDeSolicitud = Carbon::now()->timestamp;
            $notices = array("Número de Solicitud: $numeroDeSolicitud ");
            foreach ($trackingNumbers as $key => $trackingNumber) {
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $insert['tracking_number'] = $trackingNumber;
                $insert['documento'] = $namePdf;
                $insert['numero_solicitud'] = $numeroDeSolicitud;
                Log::debug(print_r($insert ,true));

                if ($i > 1) {
                    Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." Limpiar costos");
                    $insert = nGuia::costosEnCero( $insert );
                }   
                $id = Guia::create($insert)->id;
                $notices[] = sprintf("El registro de la solicitud se genero con exito con el ID %s ", $id);

                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
                $guiaPaqueteInsert = GuiaDTO::validaPiezasPaquete($request, $key, $boolPrecio, $id);
                $boolPrecio = false;

                $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." idGuiaPaquite =$idGuiaPaquite");
                $i++;
            }
            

            /*
            * Mail::to($request->email)
            *    ->cc(Config("mail.cc"))
            *    ->send(new GuiaCreada($request, $id));
            */
            
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::debug(print_r($request->all(),true));
            
            $saldo = new Saldos();
            $saldo->menosPrecio($request["sucursal_id"], $request["precio"]);

            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);

            Log::info(__CLASS__." ".__FUNCTION__." store Fin ----------------------------");
            Log::debug(__CLASS__." ".__FUNCTION__." INDEX_r");
            return \Redirect::route(self::INDEX_r) -> withSuccess ($notices);

         } catch (\Spatie\DataTransferObject\DataTransferObjectError $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." DataTransferObjectError");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("DataTransferObjectError - Consulte a su proveedor");
            
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ConnectException");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("No se pudo establecer la conexion con el LTD");
       

        } catch (\GuzzleHttp\Exception\RequestException $re) {
            Log::info(__CLASS__." ".__FUNCTION__." RequestException INICIO ------------------");
            $response = ($re->getResponse());
            $responseContenido = json_decode($response->getBody()->getContents());    
            Log::debug(print_r($responseContenido,true));
            if (is_object($responseContenido)) {
                Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." is_object ");
                

                if ( isset($responseContenido->code) ) {
                    Log::debug(__CLASS__." ".__FUNCTION__." code 131 ");
                    $mensaje= array($responseContenido->description);

                } else {
                    Log::debug(__CLASS__." ".__FUNCTION__." code 131 else");
                    
                    $mensaje = array($responseContenido->error);                    
    
                }
                 
            } else{
               
                foreach ($responseContenido as $key => $value) {
                    $mensaje = array("desc$key"=> $value->description);                   
                }

            }
            Log::info(__CLASS__." ".__FUNCTION__." RequestException FIN ------------------");

        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ClientException");
            $response = json_decode($ex->getResponse()->getBody());
            Log::debug(print_r($response,true));
            $mensaje = array($response->errors[0]->code);
            
        } catch (\GuzzleHttp\Exception\InvalidArgumentException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." InvalidArgumentException");
            Log::debug($ex->getBody());
            $mensaje = array("Se ha producido un error interno favor de contactar al proveedor");

        } catch(\Illuminate\Database\QueryException $ex){ 
            Log::info(__CLASS__." ".__FUNCTION__." "."QueryException");
            Log::debug(print_r($ex,true)); 
            Log::debug(print_r($insert,true)); 
            $mensaje= array($ex->errorInfo[2], "Tracking ".$insert['tracking_number'], "Contactar a su proveedor para el registro");

        } catch (Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__." "."Exception");
            Log::debug( $e->getMessage() );
            $mensaje= $e->getMessage();
        }

        Log::info(__CLASS__." ".__FUNCTION__." Finaliza ---------------------------- ");

        return back()
                ->with('dangers',$mensaje)
                ->withInput();
    }


    /**
     * Caso de uso pra fedex
     *
     * @param  \App\Models\Guia  $guia
     * @param  LTD_ID = 1
     * @return \Illuminate\Http\Response
     */
    private function fedex($request){

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." iniciado ----------------------------");
        $mensaje = array();
        $insert = array();
        try {
            
            Log::debug($request);

            $requestInicial = $request->except(['_token']);
            $numeroDeSolicitud = Carbon::now()->timestamp;
            $nCreacion = new nCreacion();

            $nCreacion->fedex($request, "WEB");
            $nCreacion->recurenciaPorDocumento($request, $numeroDeSolicitud);

            $notices = $nCreacion->getNotices();
            
            /*
            * Mail::to($request->email)
            *    ->cc(Config("mail.cc"))
            *    ->send(new GuiaCreada($request, $id));
            */
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            //Log::debug(print_r($request->all(),true));
            
            $saldo = new Saldos();
            $saldo->menosPrecio($request["sucursal_id"], $request["precio"]);

            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::info(__CLASS__." ".__FUNCTION__."store Fin ----------------------------");
            Log::debug(__CLASS__." ".__FUNCTION__." INDEX_r");
            return \Redirect::route(self::INDEX_r) -> withSuccess ($notices);

         } catch (\Spatie\DataTransferObject\DataTransferObjectError $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." DataTransferObjectError");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("DataTransferObjectError - Consulte a su proveedor");
            
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ConnectException");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("No se pudo establecer la conexion con el LTD");
       

        } catch (\GuzzleHttp\Exception\RequestException $re) {
            Log::info(__CLASS__." ".__FUNCTION__." RequestException INICIO ------------------");
            $response = ($re->getResponse());
            $responseContenido = json_decode($response->getBody()->getContents());    

            if (is_object($responseContenido)) {
                Log::info(__CLASS__." ".__FUNCTION__." is_object ");
                Log::debug(print_r($responseContenido,true));

                if (isset($responseContenido->code) && $responseContenido->code === 131) {
                    Log::debug(__CLASS__." ".__FUNCTION__." code 131 ");
                    $mensaje= array($responseContenido->description);

                } else {
                    Log::debug(__CLASS__." ".__FUNCTION__." code 131 else");
                    
                    $mensaje = array($responseContenido->errors[0]->message);
                }
                 
            } else{
               
                foreach ($responseContenido as $key => $value) {
                    $mensaje = array("desc$key"=> $value->description);                   
                }

            }
            Log::info(__CLASS__." ".__FUNCTION__." RequestException FIN ------------------");

        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ClientException");
            $response = json_decode($ex->getResponse()->getBody());
            Log::debug(print_r($response,true));
            $mensaje = array($response->errors[0]->code);
            
        } catch (\GuzzleHttp\Exception\InvalidArgumentException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." InvalidArgumentException");
            Log::debug($ex->getBody());
            $mensaje = array("Se ha producido un error interno favor de contactar al proveedor");

        } catch(\Illuminate\Database\QueryException $ex){ 
            Log::info(__CLASS__." ".__FUNCTION__." "."QueryException");

            Log::debug(print_r($ex->getMessage(),true)); 
            Log::debug(print_r($insert,true));
            $mensaje= array($ex->errorInfo[2], "Tracking ".$insert['tracking_number'], "Contactar a su proveedor para el registro");

        } catch (Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__." "."Exception");
            Log::debug( $e->getMessage() );
            $mensaje= array($e->getMessage());
        }

        Log::info(__CLASS__." ".__FUNCTION__."FEDEX Finalizado ---------------------------- ");

        return back()
                ->with('dangers',$mensaje)
                ->withInput();
        
    }


    /**
     * Caso de uso pra redpack 
     *
     * @param  \App\Models\Guia  $guia
     * @param  LTD_ID = 3
     * @return \Illuminate\Http\Response
     */

    private function redpack($request){

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." iniciado ----------------------------");
        $mensaje = array();
        try {

            $requestInicial = $request->except(['_token']);

            $empresa_id = auth()->user()->empresa_id;
            
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
          
            $redpackDTO = new RedpackDTO();
            $etiqueta = $redpackDTO->parser($request);
            if($redpackDTO->getRangoExcedido()){
                return back()
                ->with('dangers',array("No se cuentan con Guias, valida con tu proveedor"))
                ->withInput();
            }

            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $redpack = new Redpack( $empresa_id );
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
                $guiaDTO->parseoRedpack($request,$redpack, "WEB", $namePdf);
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
                    $precioUnitario = $request['precio'];
                }
                $boolPrecio = false;

                if ( $request['piezas'] === 1 ) {
                    $guiaPaqueteInsert = array(
                        'peso' => $request['pesos'][$key]
                        ,'alto' => $request['altos'][$key]
                        ,'ancho' => $request['anchos'][$key]
                        ,'largo' => $request['largos'][$key]
                        ,'precio_unitario' => $precioUnitario
                        ,'guia_id' => $id
                    );    
                    
                } else {
                    if (count($request['pesos']) ===1) {
                        $key = 0;
                    }
                    $guiaPaqueteInsert = array(
                        'peso' => $request['pesos'][$key]
                        ,'alto' => $request['altos'][$key]
                        ,'ancho' => $request['anchos'][$key]
                        ,'largo' => $request['largos'][$key]
                        ,'precio_unitario' => $precioUnitario
                        ,'guia_id' => $id
                    );    

                }
                
                $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
                $i++;
            }
           
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::debug(print_r($request->all(),true));
            
            $saldo = new Saldos();
            $saldo->menosPrecio($request["sucursal_id"], $request["precio"]);

            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." store Fin ----------------------------");
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." INDEX_r");
            return \Redirect::route(self::INDEX_r) -> withSuccess ($notices);
            

         } catch (\Spatie\DataTransferObject\DataTransferObjectError $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." DataTransferObjectError");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("DataTransferObjectError - Consulte a su proveedor");
            
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ConnectException");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("No se pudo establecer la conexion con el LTD");
       

        } catch (\GuzzleHttp\Exception\RequestException $re) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." RequestException INICIO ------------------");
            $response = ($re->getResponse());
            Log::debug(print_r($re->getMessage(),true));
            $mensaje = array("RequestException - Consulte a su proveedor");
            
            Log::info(__CLASS__." ".__FUNCTION__." RequestException FIN ------------------");

        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ClientException");
            $response = json_decode($ex->getResponse()->getBody());
            Log::debug(print_r($response,true));
            $mensaje = array($response->errors[0]->code);
            
        } catch (\GuzzleHttp\Exception\InvalidArgumentException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." InvalidArgumentException");
            Log::debug($ex->getBody());
            $mensaje = array("Se ha producido un error interno favor de contactar al proveedor");

        } catch(\Illuminate\Database\QueryException $ex){ 
            Log::info(__CLASS__." ".__FUNCTION__." "."QueryException");
            Log::debug(print_r($ex->getMessage(),true)); 
            Log::debug(print_r($guiaDTO->insert,true));
            $mensaje= array($ex->errorInfo[2], "Tracking ".$guiaDTO->insert['tracking_number'], "Contactar a su proveedor para el registro");

        } catch (Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__." "."Exception");
            Log::debug( $e->getMessage() );
            $mensaje= array($e->getMessage());
        }

        Log::info(__CLASS__." ".__FUNCTION__."FEDEX Finalizado ---------------------------- ");

        return back()
                ->with('dangers',$mensaje)
                ->withInput();
        
    }

    /**
     * Caso de uso pra redpack 
     *
     * @param  \App\Models\Guia  $guia
     * @param  LTD_ID = 4
     * @return \Illuminate\Http\Response
     */

    private function dhl($request){

        Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." iniciado ----------------------------");
        $mensaje = array();
        try {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__);
            $empresa_id = auth()->user()->empresa_id;
            
            $dto = new DhlDTO();
            $dto->parser($request);
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
                $guiaDTO->parseoDhl($request,$sDhl, "WEB", $namePdf);
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
                    $precioUnitario = $request['precio'];
                }
                $boolPrecio = false;

                if ( $request['piezas'] === 1 ) {
                    $guiaPaqueteInsert = array(
                        'peso' => $request['pesos'][$key]
                        ,'alto' => $request['altos'][$key]
                        ,'ancho' => $request['anchos'][$key]
                        ,'largo' => $request['largos'][$key]
                        ,'precio_unitario' => $precioUnitario
                        ,'guia_id' => $id
                    );    
                    
                } else {
                    if (count($request['pesos']) ===1) {
                        $key = 0;
                    }
                    $guiaPaqueteInsert = array(
                        'peso' => $request['pesos'][$key]
                        ,'alto' => $request['altos'][$key]
                        ,'ancho' => $request['anchos'][$key]
                        ,'largo' => $request['largos'][$key]
                        ,'precio_unitario' => $precioUnitario
                        ,'guia_id' => $id
                    );    

                }
                
                $idGuiaPaquite = GuiasPaquete::create($guiaPaqueteInsert)->id;
                $i++;
            }
           
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::debug(print_r($request->all(),true));
            
            $saldo = new Saldos();
            $saldo->menosPrecio($request["sucursal_id"], $request["precio"]);

            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__);
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." store Fin ----------------------------");
            Log::debug(__CLASS__." ".__FUNCTION__." ".__LINE__." INDEX_r");
            return \Redirect::route(self::INDEX_r) -> withSuccess ($notices);
            

         } catch (\Spatie\DataTransferObject\DataTransferObjectError $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." DataTransferObjectError");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("DataTransferObjectError - Consulte a su proveedor");
            
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ConnectException");
            Log::debug(print_r($ex->getMessage(),true));
            
            $mensaje = array("No se pudo establecer la conexion con el LTD");
       

        } catch (\GuzzleHttp\Exception\RequestException $re) {
            Log::info(__CLASS__." ".__FUNCTION__." ".__LINE__." RequestException INICIO ------------------");

            $response = ($re->getResponse());
            $content = json_decode($response->getBody()->getContents() );
            Log::debug(print_r($content,true));
            Log::debug(print_r($re->getMessage(),true));
            $mensaje = array("RequestException - ".$content -> detail);
            
            Log::info(__CLASS__." ".__FUNCTION__." RequestException FIN ------------------");

        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." ClientException");
            $response = json_decode($ex->getResponse()->getBody());
            Log::debug(print_r($response,true));
            $mensaje = array($response->errors[0]->code);
            
        } catch (\GuzzleHttp\Exception\InvalidArgumentException $ex) {
            Log::info(__CLASS__." ".__FUNCTION__." InvalidArgumentException");
            Log::debug($ex->getBody());
            $mensaje = array("Se ha producido un error interno favor de contactar al proveedor");

        } catch(\Illuminate\Database\QueryException $ex){ 
            Log::info(__CLASS__." ".__FUNCTION__." "."QueryException");
            Log::debug(print_r($ex->getMessage(),true)); 
            Log::debug(print_r($guiaDTO->insert,true));
            $mensaje= array($ex->errorInfo[2], "Tracking ".$guiaDTO->insert['tracking_number'], "Contactar a su proveedor para el registro");

        } catch (Exception $e) {
            Log::info(__CLASS__." ".__FUNCTION__." "."Exception");
            Log::debug( $e->getMessage() );
            $mensaje= array($e->getMessage());
        }

        Log::info(__CLASS__." ".__FUNCTION__."FEDEX Finalizado ---------------------------- ");

        return back()
                ->with('dangers',$mensaje)
                ->withInput();
        
    }
    //FIN DHL

}
