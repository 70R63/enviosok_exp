var table;
var peso;
var piezas = 0;
var costoSeguro = 0;
var valorEnvio = 0;
var costoPesoExtra = 0;
var dimensional = 0;
var bascula = 0;
var sobrePesoKg = 0;
var costoCoberturaExtendida = 0;
var costoKgExtra = 0;
var saldoNegativo = false;
var saldoMinimo = 90;



function pesoDimensionalyBascula(){

    var piezas = $("#piezas").val()
    var iteracionClone = 0
    $('.registroMultipieza').each(function(){
        console.log("--------------"+iteracionClone)
        var control = +iteracionClone *4
        var indexPeso = 0 +control
        var indexLargo = 1 +control
        var indexAncho = 2 +control
        var indexAlto = 3 +control 
        

        var peso = $('.registroMultipieza .multi').get()[indexPeso].value
        var largo = $('.registroMultipieza .multi').get()[indexLargo].value
        var ancho = $('.registroMultipieza .multi').get()[indexAncho].value
        var alto = $('.registroMultipieza .multi').get()[indexAlto].value
        
        if ($('.registroMultipieza').length == 1){
            pesoBascula = peso*piezas
            pesoDimensional = (((alto*ancho*largo)/5000)*piezas)    
            
        }else{
            pesoBascula = peso
            pesoDimensional = ((alto*ancho*largo)/5000)
        }
        
        console.log("bascula "+pesoBascula+ ">"+ pesoDimensional+" dimensional")
        iteracionClone++

    })  

    pesoFacturado = pesoFacturado + ((bascula > dimensional) ? Math.ceil(bascula) : Math.ceil(dimensional));
    return pesoFacturado;
}

function pesofacturado(){

    var peso = 0
    var piezas = $("#piezas").val()
    var iteracionClone = 0
    var pesoFacturado =+0

    bascula = +0
    dimensional =+0

    $('.registroMultipieza').each(function(){
        console.log("--------------"+iteracionClone)
        var control = +iteracionClone *4
        var indexPeso = 0 +control
        var indexLargo = 1 +control
        var indexAncho = 2 +control
        var indexAlto = 3 +control 
        

        var peso = $('.registroMultipieza .multi').get()[indexPeso].value
        var largo = $('.registroMultipieza .multi').get()[indexLargo].value
        var ancho = $('.registroMultipieza .multi').get()[indexAncho].value
        var alto = $('.registroMultipieza .multi').get()[indexAlto].value

        if ($('.registroMultipieza').length == 1){
            bascula = peso*piezas
            dimensional = (((alto*ancho*largo)/5000)*piezas)    
            
        }else{
            bascula = peso
            dimensional = ((alto*ancho*largo)/5000)
        }
        
        pesoFacturado = pesoFacturado + ((bascula > dimensional) ? Math.ceil(bascula) : Math.ceil(dimensional));
        console.log("bascula "+bascula+ ">"+ dimensional+" dimensional")
        iteracionClone++

    })       
    console.log("peso facturado = "+pesoFacturado)
    $("#pesoFacturado").val(pesoFacturado);
}

function costoSeguroValidar(seguro){
    costoSeguro = 0;
    valorEnvio = 0;
    console.log("costoSeguroValidar "+seguro)
    
    if ($('#checkSeguro').is(":checked")) {
        valorEnvio = $("#valor_envio").val();
        costoSeguro = (valorEnvio * seguro)/100;  
    }
    return costoSeguro;
}

function preciofinal(dataRow){
    //Variable Global;
    piezas = $('#piezas').val();
    peso = $('#pesoFacturado').val();
    costoCoberturaExtendida = 0;
    costoPesoExtra = 0;

    costoSeguroValidar(dataRow.seguro);

    if (peso > dataRow.kg_fin) {
        sobrePesoKg = peso - dataRow.kg_fin ;
        costoPesoExtra = sobrePesoKg * dataRow.kg_extra ;
        costoKgExtra = dataRow.kg_extra;
    }

    console.log(dataRow.extendida_cobertura);
    var textAreaExtendida = dataRow.extendida_cobertura.toUpperCase()
    if ( textAreaExtendida == "SI"){
        costoCoberturaExtendida = dataRow.extendida
        console.log(costoCoberturaExtendida);
    } 

    return dataRow.costo+ costoPesoExtra + costoSeguro + costoCoberturaExtendida;
}

function fechaTentativa(row){
    
    var ahora = new Date();
    var diaLaboral = 0
    
    ahora.setDate(ahora.getDate()+row.tiempo_entrega)

    if (ahora.getDay() === 0 || ahora.getDay() === 6 )
        diaLaboral = 2

    ahora.setDate(ahora.getDate()+diaLaboral)
    return ahora.toLocaleDateString('es-MX');
}


function obtenerCP(id, modelo) {
    console.log("cargadnp obtenerCP");
    $.ajax({
        /* Usar el route  */
        url: "api/cp",
        type: 'GET',
        /* send the csrf-token and the input to the controller */
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: "id="+id+"&modelo="+modelo
        
        /* remind that 'data' is the response of the AjaxController */
        }).done(function( response) {
            console.log("done");
            var contador = response.data.length

            if ("Sucursal" == modelo) {
                if (contador == 1) {
                    $("#cp").val(response.data[0].cp);    
                } else {
                    $("#cp").val("00000");
                }
                
            } else {
                if (contador == 1) {
                    $("#cp_d").val(response.data[0].cp);    
                } else {
                    $("#cp_d").val("00000");
                }
            }
            
        
        }).fail( function( data,jqXHR, textStatus, errorThrown ) {
            console.log( "fail" );
            console.log(textStatus);
            
            alert( data.responseJSON.message);

        }).always(function() {
            console.log( "complete" );
        });

}

function validaSaldo(response){
    console.log("validaSaldo")

    if (response.data.tipoPagoId == 2) {
         if (response.data.saldo <saldoMinimo) {
            saldoNegativo = true;
            swal(
                "El Saldo: "+response.data.saldo +" es menor al limite permitido",
                "Revisar con tu Administrador!",
                "error"
              )    
        }
        
        if (response.data.saldo <0) {
            saldoNegativo = true;
            swal(
                "Saldo Negativo: $"+response.data.saldo ,
                "Revisar con tu Administrador!",
                "error"
              )    
        }

       
    } else {

    }

    //tipo_pago_id
}


//actividades al vuelo

$("#limpiar").click(function() {
    $('#cotizacionesForm').trigger('reset');
    table.clear().draw();
    $('#cotizacionesForm').parsley().reset();
    //validar se se puede reutilizar
    $(".checkManualHtml").show()
    $(".clienteCombo").hide()
    $("#clienteIdCombo").removeAttr("required");
    $(".cotizacionManual").attr("readonly","true");
    $("#sucursal").attr("required","true");
    $("#cliente").attr("required","true");
    $("#cliente_id").removeAttr("required");
    
    $(".checkSemiHtml").show()
    $(".cotizacionSemi").attr("readonly","true");
    $("#esManual").val("NO");
    //FIN validar se se puede reutilizar

});


$("#cotizar").click(function(e) {
    console.log("cotizar")
    e.preventDefault();

    var form = $('#cotizacionesForm').parsley().refresh();
    var action = $('#cotizacionesForm').attr("action"); 
    console.log(action)
    saldoNegativo = false;
    if ( form.validate() ){
        $.ajax({
            /* Usar el route  */
            url: action,
            type: 'GET',
            /* send the csrf-token and the input to the controller */
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: $('#cotizacionesForm').serialize()
            
            /* remind that 'data' is the response of the AjaxController */
            }).done(function( response) {
                console.log("done");
                console.log(response.data.data);

                //validaSaldo(response)
                

                table = $('#cotizacionAjax').DataTable({
                    "oLanguage": {
                        "sEmptyTable": "No exiten tarifas con los datos para cotizar"
                    }
                    ,"processing": true,
                    "bDestroy": true,
                    order: [[1, 'desc']]

                    ,"data": response.data.data,
                    columnDefs: [
                        {  
                            targets: 10 
                            ,"createdCell": function(td, cellData, rowData, row, col) {
                                switch(cellData) {
                                    case "SI":
                                        $(td).addClass('text-danger si si-info');
                                        break;
                                    case "NO":
                                        $(td).addClass('text-success');

                                }
                            }
                        }
                    ]
                    ,"columns": [
                        { "data": "id" },
                        { "data": "nombre" },
                        { "data": "servicios_nombre" },
                        { "data": "fecha_tentativa" 
                            ,render: function (data, type, row) {
                                return fechaTentativa(row);   
                            } 
                        },
                        { "data": "zona" },
                        { "data": "costo" 
                            ,render: function (data,row) {
                                return '$ '+data;
                            } 
                        },
                        { "data": "kg_ini" },
                        { "data": "kg_fin" },
                        { "data": "kg_extra" },
                        { "data": "ocurre" },
                        { "data": "extendida_cobertura" },
                        { "data": "extendida" },
                        { "data": "seguro"
                            ,render: function (data, type, row, meta) {
                                return '$ '+costoSeguroValidar(row.seguro);   
                            } 
                        },
                        { "data": "costo_total"
                            ,render: function (data, type, row, meta) {
                                return '$ '+preciofinal(row);   
                            } 
                        }
                    ],
                    "autoWidth": false,
                });
                
            }).fail( function( data,jqXHR, textStatus, errorThrown ) {
                console.log( "fail" );
                console.log(textStatus);
                
                swal(
                    "Error!",
                    data.responseJSON.message,
                    "error"
                  )


            }).always(function() {
                console.log( "complete" );
            });
        
    } else {
        console.log( "enviosForm con errores" );
        return false;
    }
});

table = $('#cotizacionAjax').DataTable({
    "oLanguage": {
        "sEmptyTable": "Ingresa los datos para cotizar"
    }
});

$('#cotizacionAjax tbody').on('click', 'tr', function () {
   
    saldoNegativo=false
    if (saldoNegativo) {
        swal(
            "Por el monento no puede hacer guias",
            "Revisar con tu Administrador!",
            "error"
          ) 

    } else {

        var dataRow = table.row(this).data(); 

        console.log(dataRow);
        //Valores de la cotizacion de la Forma Cotizacion
        var sucursal_id = $('#sucursal').val();
        var cliente_id = $('#cliente').val();
        var cp = $('#cp').val();
        var cp_d = $('#cp_d').val();
        var largo = $('#largo').val();
        var ancho = $('#ancho').val();
        var alto = $('#alto').val();
        var bSeguro = ( $('#checkSeguro').is(":checked") ? true : false);
        var valorEnvio = $('#valor_envio').val();
        var contenido = $('#contenido').val();
        var esManual = $("#esManual").val();
        var empresaId = $("#clienteIdCombo").val();

        //Inicializacion de variables del renglon de la cotizacion
        var tarifa_id = table.row(this).data()['id'];
        var ltd_nombre = table.row(this).data()['nombre'];
        var ltd_id = table.row(this).data()['ltds_id'];
        var servicioNombre = dataRow['servicios_nombre'];
        var servicioId  = dataRow['servicio_id'];
        var precio =  preciofinal(dataRow);
        var iva = precio*0.16;
        var precioIva = (precio+iva).toFixed(2);
        var ocurre  = dataRow['ocurre'];
        var areaExtendida  = dataRow['extendida_cobertura'];
        var zona  = dataRow['zona'];
        var costoBase  = dataRow['costo'];

        //valores para el modal resumen_cotizacion.blade
        $(".spanPrecio").text( precioIva );
        $("#spanMensajeria").text(ltd_nombre);
        $("#spanservicioId").text(servicioNombre);
        $("#spanRemitente").text(cp);
        $("#spanDestinatario").text(cp_d);
        $("#spanPieza").text(piezas);
        $("#spanSeguro").text(costoSeguro);
        $("#spanValorEnvio").text(valorEnvio);
        $("#spanPeso").text(peso);
        $("#spanCotizacionManual").text(esManual);
        $("#spanOcurre").text(ocurre);
        $("#spanAreaExtendida").text(areaExtendida);
        $("#spanZona").text(zona);

        //valores para request, campos ocultos guiastore_ocultos -> card_preciofinal
        pesofacturado()
        $("#precio").val(precioIva);
        $("#tarifa_id").val(tarifa_id);
        $("#sucursal_id").val(sucursal_id);
        $("#cliente_id").val(cliente_id);
        $("#ltd_nombre").val(ltd_nombre);
        $("#ltd_id").val(ltd_id);
        $("#piezas_guia").val(piezas);
        $("#servicio_id").val(servicioId);
        $("#peso_facturado").val(peso);
        $("#bSeguro").val(bSeguro);
        $("#costo_seguro").val(costoSeguro);
        $("#contenido_r").val(contenido);
        $("#extendida_r").val(areaExtendida);
        $("#valor_envio_r").val(valorEnvio);
        $("#esManual").val(esManual);
        $("#cp_manual").val(cp);
        $("#cp_d_manual").val(cp_d);
        $("#empresa_id").val(empresaId);
        $("#ocurre").val(ocurre);
        $("#zona").val(zona);
        $("#costo_base").val(costoBase);
        $("#costo_kg_extra").val(costoPesoExtra); //costoKgExtra
        $("#peso_dimensional").val(dimensional);
        $("#peso_bascula").val(bascula);
        $("#sobre_peso_kg").val(sobrePesoKg);
        $("#costo_extendida").val(costoCoberturaExtendida);
        
        
        
        var iteracionClone = 0
        var pesos = []
        var largos = []
        var anchos = []
        var altos = []

        $('.registroMultipieza').each(function(){
            console.log("--------------"+iteracionClone)
            var control = +iteracionClone *4
            var indexPeso = 0 +control
            var indexLargo = 1 +control
            var indexAncho = 2 +control
            var indexAlto = 3 +control 
            

            var peso = $('.registroMultipieza .multi').get()[indexPeso].value
            var largo = $('.registroMultipieza .multi').get()[indexLargo].value
            var ancho = $('.registroMultipieza .multi').get()[indexAncho].value
            var alto = $('.registroMultipieza .multi').get()[indexAlto].value
            
            pesos.push(peso)
            largos.push(largo)
            anchos.push(ancho)
            altos.push(alto)
            iteracionClone++
        })      

        $("#pesos").val(pesos);
        $("#largos").val(largos);
        $("#anchos").val(anchos);
        $("#altos").val(altos);

        var saldoPorEmpresa = document.getElementById("spanSaldoPorEmpresa").innerText;
        console.error(saldoPorEmpresa)

        console.log("crearPreferencia")
        crearPreferencia(precioIva,ltd_nombre, servicioNombre);
        $("#myModalMercadoPago").modal("show");
        //$("#myModal").modal("show");
        if (saldoPorEmpresa < 0) {

        } else {

        }
        
        

    }
    
});


$("#sucursal").change(function() {
    var idSucursal = $('#sucursal').val();
    console.log("sucursal "+idSucursal)
    obtenerCP(idSucursal, "Sucursal");

    direccionesPorEmpresa(idSucursal)
   
            
}); 

$("#cliente").change(function() {
    var idCliente = $('#cliente').val();
    console.log("sucursal "+idCliente)
    obtenerCP(idCliente, "Cliente");
});

$(function(){
    $(".multi").on("change keyup paste", function (){
        pesofacturado();
    });

    
    $("#handleCounterMax28").click( function (){
        pesofacturado();
    });
    
});

function obtenerClientes() {

    $.ajax({
        /* Usar el route  */
        url: route('api.clientes'),
        type: 'GET',
        /* send the csrf-token and the input to the controller */
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        //data: "clienid="+id
        
        /* remind that 'data' is the response of the AjaxController */
        }).done(function( response) {
            console.log("done");
            //console.log(response.data);
           
            $('#clienteIdCombo').empty();
            $("#clienteIdCombo").append('<option selector="0" value="0"> TODOS</option>');
            
            $.each(response.data,function(key, empresa) {
                $("#clienteIdCombo").append('<option selector='+key+' value="'+empresa.id+'" >'+empresa.nombre+'</option>');
              });   
            
        
        }).fail( function( data,jqXHR, textStatus, errorThrown ) {
            console.log( "fail" );
            console.log(textStatus);
            
            swal(
                "Error!",
                data.responseJSON.message,
                "error"
              );
            

        }).always(function() {
            console.log( "complete" );
        });

}


function direccionesPorEmpresa(idSucursa){
    console.log( "direccionesPorEmpresa" );

    $.ajax({
        /* Usar el route  */
        //url: route('api.cp.colonias'), 
        url: route('api.direcciones.tipo', [idSucursa]),
        type: 'GET',
        /* send the csrf-token and the input to the controller */
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        
        
        /* remind that 'data' is the response of the AjaxController */
        }).done(function( response) {
            console.log("done");
            console.log(response.data);
           
            $('#cliente').empty();
            $("#cliente").append('<option selector="0" value="0"> Selecciona</option>');
            $.each(response.data,function(key, empresa) {
                $("#cliente").append('<option selector='+key+' value="'+empresa.id+'" >'+empresa.nombre+'</option>');
              });   
            
        
        }).fail( function( data,jqXHR, textStatus, errorThrown ) {
            console.log( "fail" );
            console.log(textStatus);
            
            swal(
                "Error!",
                data.responseJSON.message,
                "error"
              );
            

        }).always(function() {
            console.log( "complete" );
    });
    
}

function crearPreferencia(precioIva,ltd_nombre, servicioNombre){

    const mp = new MercadoPago('TEST-ee7e0c16-35b1-4cd2-b742-56da4a3eccce',
            {locale: "es-MX"}
        );
    const bricksBuilder = mp.bricks();

        
    element = document.getElementById("wallet_container");
    element.remove(); // Elimina el div con el id 'div-02'

    $('#mercadopago').append('<div id="wallet_container"></div>');

    var settings = {
        "url": "https://api.mercadopago.com/checkout/preferences",
        "method": "POST",
        "timeout": 0,
          "headers": {
            "Content-Type": "application/json",
            "Authorization": "Bearer TEST-4198938082880779-032602-a9f12f338449b61fac78b09778fef4c7-150057237"
          },
        "data": JSON.stringify({
            "items": [
              {
                "title": ltd_nombre +" - "+servicioNombre,
                "description": ltd_nombre +" - "+servicioNombre,
                "picture_url": "http://www.myapp.com/myimage.jpg",
                "category_id": "car_electronics",
                "quantity": 1,
                "currency_id": "MXP",
                "unit_price": parseFloat(precioIva)
              }
            ],
            "payment_methods": {
                "excluded_payment_methods": [
                    {
                        id: "amex"
                    },
                    {
                        id: "debmaster"
                    }
                ],
                "excluded_payment_types": [
                    {
                        id: "ticket"
                    }
                ]
            },
            installments: 1,
            auto_return: "approved",
            back_urls: {
                success: "http://local.enviosok.com/dashboard",
                failure: "http://local.enviosok.com/dashboard",
                pending: "https://www.tu-sitio/pendings"
            },
        }),
    };

    $.ajax(settings).done(function (response) {
        console.log(response);
        mp.bricks().create("wallet", "wallet_container", {
            initialization: {
                preferenceId: response.id,
                redirectMode: "self"
            },
            customization: {
                texts: {
                    valueProp: 'smart_option',
                },
                visual:{
                    borderRadius: '6px',
                    verticalPadding : '8px',

                }
            },
            callbacks: {
                onError: (error) => console.error(error),
                onReady: () => console.error("onReady")
            }
        });
    }).fail( function( data,jqXHR, textStatus, errorThrown ) {
        console.log( "fail" );
        console.log(textStatus);
        
        swal(
            "Error!",
            data.responseJSON.message,
            "error"
          )


    }).always(function() {
        console.log( "complete" );
    });
    
}

$("#addRow").click(function () {
    console.log('AddRow')
    var piezas = $("#piezas").val()  
    var multipiezas = $(".registroMultipieza").length;
    
    
    var html = $("#clone").clone(true,true)

    $('.registroMultipieza').each(function( index ) {
        $(this).remove();    
        
    });

    console.log("piezas -> "+piezas)
    for (let i = 0; i < piezas ; i++) {
        console.log("iteracion piezas")
        html.clone(true,true).appendTo( "#multiPieza" ).show()
    }

    pesofacturado();
    
});

// La fucnion habilita el modo edicion de los CP, esto ayuda a realizar una cotizacion manual basda en tarifias de un cliente

$('#checkCotizacionManual').change(function() {

    console.log("checkCotizacionManual");
    var checkSeguro = $(this).is( ":checked" )
    if ( checkSeguro ) {
        $("#cliente").removeAttr("required");
        $(".cotizacionSemi").removeAttr("readonly");
        //$("cotizacionSemi").removeAttr("required");
        $("#esManual").val("SEMI");
        $(".checkSemiHtml").hide()
    } else {
        $(".checkSemiHtml").show()
        $(".cotizacionSemi").attr("readonly","true");
        $("#cliente").attr("required","true");
        $("#esManual").val("NO");
    }
  });

$('#checkManual').change(function() {

    console.log("checkManualHtml");
    var check = $(this).is( ":checked" )
    if ( check ) {
        $(".checkManualHtml").hide()
        $(".clienteCombo").show()
        $("#clienteIdCombo").attr("required","true");
        obtenerClientes()
        $(".cotizacionManual").removeAttr("readonly");
        $("#sucursal").removeAttr("required");
        $("#cliente").removeAttr("required");
        $("#cliente_id").attr("required","true");
        $("#esManual").val("SI");
        
    } else {
        $(".checkManualHtml").show()
        $(".clienteCombo").hide()
        $("#clienteIdCombo").removeAttr("required");
        $(".cotizacionManual").attr("readonly","true");
        $("#sucursal").attr("required","true");
        $("#cliente").attr("required","true");
        $("#cliente_id").removeAttr("required");
        $("#esManual").val("NO");
    }
});


