<?php

use App\Http\Controllers\API\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\LoginController  as AuthController;
use App\Http\Controllers\API\GuiaController;
use App\Http\Controllers\API\CotizacionController;
use App\Http\Controllers\API\EmpresaLtdController;
use App\Http\Controllers\API\DireccionController;
use App\Http\Controllers\API\CPController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\ReportesController;
use App\Http\Controllers\API\Reportes\RepesajeController;
use App\Http\Controllers\API\Saldos\PagosController;
use App\Http\Controllers\API\Saldos\SaldosController;
use App\Http\Controllers\API\Reportes\PagosController as ReportesPagoController;
use App\Http\Controllers\API\Ltd\FedexController;
use App\Http\Controllers\API\Ltd\EstafetaController;


use App\Http\Controllers\API\DEV\GuiaController as DevGuiaController ;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register',[AuthController::class,'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('domicilio', [ApiController::class,'domicilio'])->name('api.domicilio');
Route::post('getColonias', [ApiController::class,'getColonias'])->name('api.getColonias');

Route::middleware('auth:sanctum')->get('/ping', function (Request $request) {

    return response()->json([
            'status' => true,
            'message' => "Ping successfully!",
        ], 200);
});

//SADEMIO
Route::name('api')->group(function () {
    Route::name('sademio.')->group(function () {
        Route::group(['prefix'=>'SADEMIO'], function(){
            Route::post('/login', [AuthController::class, 'login'])->name('login');
        });
    });
});

//Route::domain('local.xpertamexico.com')->group(function () {
    Route::middleware(['throttle:100,1','validaToken'])->group(function(){
        Route::post('logout', [AuthController::class, 'logout']);



        Route::controller(GuiaController::class)->group(function(){
            Route::get('ltds', 'creacion');
            Route::post('fedex', 'fedex');
            Route::post('estafeta', 'estafeta');
            Route::post('dev/estafeta', 'estafeta');
            Route::get('rastreoTabla', 'rastreoTabla');
        });


        Route::name('api.')->group(function () {
            //MENU FEDEX

            Route::name('enviosperros.')->group(function () {
                Route::group(['prefix'=>'enviosperros'], function(){
                    Route::name('fedex.')->group(function () {
                        Route::group(['prefix'=>'fedex'], function(){

                            Route::get('/greeting', function () {
                                return 'Hello World';
                            })->name("greeting");

                            Route::controller(FedexController::class)->group(function(){
                                Route::post('terrestre', 'terrestre')->name("terrestre");

                            });

                            Route::controller(FedexController::class)->group(function(){
                                Route::post('diasig', 'diasig')->name("diasig");

                            });

                            Route::controller(FedexController::class)->group(function(){
                                Route::get('cotizacion/{servicio}', 'cotizacion')->name("cotizacion");

                            });

                        });
                    });
                });
            });


            //SADEMIO
             Route::name('sademio.')->group(function () {
                Route::group(['prefix'=>'SADEMIO'], function(){

                    Route::name('estafeta.')->group(function () {
                        Route::group(['prefix'=>'estafeta'], function(){

                            Route::get('/greeting', function () {
                                return 'Hello World';
                            })->name("greeting");

                            Route::controller(EstafetaController::class)->group(function(){
                                Route::post('{servicios}', 'creacion')->name("creacion");
                            });
                        });
                    });
                });
            });

        });// FIN api.

    });
//});



//MIDDLEWARE PARA AJAX DESDE WEB
Route::middleware(['throttle:100,1','auth'])->group(function () {
    Route::name('api.')->group(function () {
        //Carga los metodos basicos index, store, update , etc
        Route::apiResource('cotizaciones', CotizacionController::class);

        Route::controller(CotizacionController::class)->group(function(){
            Route::get('cp', 'cp');
        });

        Route::apiResource('empresaltd', EmpresaLtdController::class);

        Route::controller(GuiaController::class)->group(function(){
            Route::get('guiasTabla', 'guiasTabla');
            Route::post('rastreoActualizar', 'rastreoActualizar');
        });

        Route::controller(DireccionController::class)->prefix('direccion')->group(function(){
            Route::get('{cliente}', 'index')->name("direcciones.tipo");

        });

        Route::controller(CPController::class)->group(function(){
            Route::get('cp/colonias', 'colonias')->name("cp.colonias");
        });

        Route::controller(ClienteController::class)->group(function(){
            Route::get('clientes', 'clientes')->name("clientes");
        });

        //MENU REPORTES
        Route::group(['prefix'=>'reportes','as'=>'reportes.'], function(){
            Route::controller(ReportesController::class)->group(function(){
                Route::get('ventas', 'reportes')->name("ventas");
                Route::post('ventas', 'creacion')->name("creacion");
            });

            Route::group(['prefix'=>'repesajes','as'=>'repesajes.'], function(){
                Route::controller(RepesajeController::class)->group(function(){
                    Route::get('repesajes', 'reportes')->name("repensajes");
                    Route::post('repesajes', 'creacion')->name("creacion");
                });
            });

            Route::group(['prefix'=>'pagos','as'=>'pagos.'], function(){
                Route::controller(ReportesPagoController::class)->group(function(){
                    Route::get('index', 'index')->name("index");
                    Route::post('creacion', 'creacion')->name("creacion");
                });
            });
        });

        //MENU SALDOS
        Route::group(['prefix'=>'saldos','as'=>'saldos.'], function(){
            Route::controller(PagosController::class)->group(function(){
                Route::get('pagos/resumen', 'tablaPagosResumen')->name("pagos_resumen");

            });

            Route::controller(PagosController::class)->group(function(){
                Route::get('pagos/{empresa_id}', 'tablaPagos')->name("pagos");

            });

            Route::controller(SaldosController::class)->group(function(){
                Route::get('empresas', 'porEmpresa')->name("empresas");

            });

        });






    });
});
//Fin Middileware

//ejecucion
 Route::controller(GuiaController::class)->group(function(){
    Route::get('rastreoActualizar', 'rastreoActualizarAutomatico')->name("rastreoConsola");
});

//AMBIENTE DEV TEMPORAL

Route::name('api.dev.')->group(function () {
    Route::group(['prefix'=>'dev/'], function(){
        Route::name('enviosperros.')->group(function () {
            Route::group(['prefix'=>'enviosperros'], function(){
                Route::post('/login', [AuthController::class, 'login'])->name('login');
            });
        });

        Route::name('sademio.')->group(function () {
            Route::group(['prefix'=>'SADEMIO'], function(){
                Route::post('/login', [AuthController::class, 'login'])->name('login');
            });
        });
    });
});


Route::middleware(['throttle:10,1','validaToken'])->group(function(){
    Route::controller(DevGuiaController::class)->group(function(){
        Route::post('dev/estafeta', 'estafeta');
    });

    Route::name('api.dev.')->group(function () {

        Route::group(['prefix'=>'dev/'], function(){

            Route::name('enviosperros.')->group(function () {
                Route::group(['prefix'=>'enviosperros'], function(){

                    Route::name('fedex.')->group(function () {
                        Route::group(['prefix'=>'fedex'], function(){

                            Route::get('/greeting', function () {
                                return 'Hello World';
                            })->name("greeting");

                            Route::controller(FedexController::class)->group(function(){
                                Route::post('terrestre', 'terrestreDEV')->name("terrestreDEV");

                            });

                            Route::controller(FedexController::class)->group(function(){
                                Route::post('diasig', 'diasigDEV')->name("diasigDEV");

                            });

                            Route::controller(FedexController::class)->group(function(){
                                Route::get('cotizacion/{servicio}', 'cotizacionDEV')->name("cotizacionDEV");

                            });

                        });
                    });
                });
            });

            //SADEMIO
             Route::name('sademio.')->group(function () {
                Route::group(['prefix'=>'SADEMIO'], function(){

                    Route::name('estafeta.')->group(function () {
                        Route::group(['prefix'=>'estafeta'], function(){

                            Route::get('/greeting', function () {
                                return 'Hello World';
                            })->name("greeting");

                            Route::controller(EstafetaController::class)->group(function(){
                                Route::post('{servicio}', 'creacionDEV')->name("creacionDEV");
                            });
                        });
                    });
                });
            });


        });// FIN api.
    });
});



//AMBINTE DEV.OPERANDOEXPERTAMENTE.COM

Route::name('api.v1.')->group(function () {
    Route::group(['prefix'=>'v1/'], function(){

        Route::group(['prefix'=>'{empresa}'], function(){
            Route::post('/login', [AuthController::class, 'login'])->name('login');
        });

    });
});


Route::middleware(['throttle:50,1','AccesosApi'])->group(function(){
    Route::name('api.v1.')->group(function () {
    Route::group(['prefix'=>'v1/'], function(){

        Route::group(['prefix'=>'empresas/{empresa}'
            , 'as'=>'empresas.']
            , function(){


            Route::get('/greeting', function () {
                                return 'Hello World';
                            })->name("greeting");

            Route::group(['prefix'=>'ltds/{ltds}/servicios/{servicios}'
                            ,'as' => 'ltds.servicios.' ]
                        ,function(){

                 Route::controller(CotizacionController::class)->group(function(){
                    Route::get('cotizaciones', 'cotizaciones')->name('cotizaciones');
                });


            });


            Route::group(['prefix'=>'ltds/estafeta', 'as'=>'ltds.estafeta.'], function(){
                Route::group(['prefix'=>'servicios/{servicios}', 'as'=>'servicios.'], function(){

                    Route::controller(EstafetaController::class)->group(function(){
                        Route::post('guia/{formatoImpresion?}', 'creacion')->name("guia");
                    });
                });
            });


            Route::group(['prefix'=>'ltds/fedex', 'as'=>'ltds.fedex.'], function(){
                Route::group(['prefix'=>'servicios/{servicios}', 'as'=>'servicios.'], function(){

                    Route::controller(FedexController::class)->group(function(){
                        Route::post('guia/{formatoImpresion?}', 'creacion')->name("guias");


                    });
                });
            });


        });

    });
    });
});


