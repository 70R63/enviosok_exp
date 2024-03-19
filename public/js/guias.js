var table;
$(document).ready(function() {
    if ($('#guiasTablaAjax').length) {
        guiasTabla()     
    }

}) 

function esRetornoRemitente(row){
    var leyenda = row.contacto
    if (row.canal === "RET")
        leyenda = row.contacto_d

    return leyenda
}

function esRetornoDestinatario(row){
    var leyenda = row.contacto_d
    if (row.canal === "RET")
        leyenda = row.contacto

    return leyenda
}


function guiasTabla(){

    $.ajax({
        url: 'api/guiasTabla',
        type: 'GET',
        /* send the csrf-token and the input to the controller */
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        
        
        /* remind that 'data' is the response of the AjaxController */
    }).done(function( response) {
        //console.log(response.data)
        table = $('#guiasTablaAjax').DataTable({
                    "oLanguage": {
                        "sEmptyTable": "No se puede mostrar los registros"
                    }
                    ,processing: true
                    ,serverSide: false 
                    ,pagingType: "full_numbers"
                    ,deferRender: true
                    ,bDestroy: true
                    ,data: response.data.tabla
                    ,autoWidth: false
                    ,order: [[0, 'desc']]
                    ,lengthMenu: [
                        [ 10, 25, 50, -1 ],
                        [ '10', '25', '50', 'Todo' ]
                    ]
                    ,dom: 'Bfrtip'
                    ,buttons: [ 
                        'pageLength'
                      ,{ 
                         extend: 'excelHtml5'
                         , footer: true
                         , charset: 'utf-8' 
                         , fieldSeparator: ','
                         ,fieldBoundary: ''
                         ,exportOptions: {
                            columns: ':not(.notexport)'
                         }
                         ,customizeData: function(data) {
                           for(var i = 0; i < data.body.length; i++) {
                            console.log("custom")
                             data.body[i][13] = '\0' + data.body[i][13];
                           }
                         } 
                      }
                      ,{ 
                         extend: 'pdf'
                         ,orientation: 'landscape'
                         , footer: true 
                         ,exportOptions: {
                            columns: ':not(.notexport)'
                         } 
                      }
                      
                   ]
                    ,columns: [
                        { "data": "id" } //0
                        ,{ "data": "mensajeria"
                                ,render: function(data, type, row){   
                                return documentoRetorno(row, response.data.rol); 
                            }
                        }
                        
                        ,{ "data": "mensajeria" }
                        ,{ "data": "servicio_nombre" }
                        ,{ "data": "usuario" }
                        ,{ "data": "nombre" } //5
                        ,{ "data": "contacto"
                                ,render: function(data, type, row){   
                                return esRetornoRemitente(row); 
                            }
                        }
                        ,{ "data": "contacto_d" 
                                ,render: function(data, type, row){   
                                return esRetornoDestinatario(row); 
                            }
                        }
                        ,{ "data": "cp" }
                        ,{ "data": "ciudad" }
                        ,{ "data": "cp_d" } //10
                        ,{ "data": "ciudad_d" }
                        ,{ "data": "creada" }
                        ,{ "data": "canal" }
                        ,{ "data": "tracking_number" }
                        ,{ "data": "precio" }//15
                        ,{ "data": "piezas" }
                        ,{ "data": "peso_u" }
                        ,{ "data": "alto_u" }
                        ,{ "data": "ancho_u" }
                        ,{ "data": "largo_u" }//20
                        ,{ "data": "valor_envio" }
                        ,{ "data": "seguro" }
                        ,{ "data": "extendida" }
                        ,{ "data": "zona" }

                    ],
                });
        table.columns( [8,9,10,11,15,16,17,18,19,20,21,22] ).visible( false );
            
    }).fail( function( data,jqXHR, textStatus, errorThrown ) {
        console.log( "fail" );
        console.log(data);
        swal(
            "Error!",
            textStatus,
            "error"
          )


    }).always(function() {
            console.log( "complete guiasTablaAjax" );
    });
}

function documentoRetorno(row, rol){

    console.log(rol)

    var doc = documento(row)

    return doc;
}

function documento(row){
    if (row.ltd_id==1) {
        cantidad = row.documento.split("|")
        var html = ""
        cantidad.forEach( (data) => {    
            html=html+ '<a href="'+data+'" target="_blank" rel="noopener noreferrer"><i class="text-info tx-20 fa fa-archive" data-toggle="tooltip" title="" data-original-title="fa fa-archive"></i></a>'
        })
        
    } else{
        html = '<a href="storage/'+row.documento+'" target="_blank" rel="noopener noreferrer"><i class="text-info tx-20 fa fa-archive" data-toggle="tooltip" title="" data-original-title="fa fa-archive"></i></a>'
    }
    return html;
}



$("#guiasTablaAjax").on( "click", "#eliminarGuia", function() {
    console.log("modalEliminarGuia")
//    console.log($(this).parent().parent().parent().parent().data() )
    var dataRow = table.row( $(this).parent().parent().parent() ).data(); 

    console.log(dataRow);

    //valores para el modal 
    $(".idGuia").text( dataRow.id );
    $("#empresaNombre").text( dataRow.nombre );
    $("#precio").text( dataRow.precio );

    $("#idGuiaForm").val( dataRow.id );
    $("#ciaForm").val( dataRow.cia );
    $("#precioForm").val( dataRow.precio );

    $("#modalEliminarGuia").modal("show");
});


 $( "#guiasTablaAjax" ).on( "click", "span", function() {

    var dataRow = table.row( $(this).parent().parent() ).data(); 

    console.log(dataRow);

    //valores para el modal 
    $("#spanID").text( dataRow.id );
    $("#spanRemitente").text( dataRow.cp );
    $("#spanDestinatario").text( dataRow.cp_d );
    $("#spanNuevoDestinatario").text( dataRow.cp );
    $("#spanNuevoRemitente").text( dataRow.cp_d );
    $("#guia_id").val( dataRow.id );
    
    $("#myModal").modal("show");
});