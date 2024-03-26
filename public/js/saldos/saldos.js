$(document).ready(function() {
    
   initAjaxSaldoPorEmpresa()
})



function initAjaxSaldoPorEmpresa(){

    empresaIdHeader = $('#empresaIdHeader').val();
    console.log("hola " + empresaIdHeader )
    $.ajax({
        url: route('api.saldos.empresas',[1]),
        type: 'GET',
        data: "empresa_id="+empresaIdHeader,
        /* send the csrf-token and the input to the controller */
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        
    }).done(function( response ) {
        console.log( "response" );
        console.log(response.data);
        var saldo = response.data
        
        $("#spanSaldoPorEmpresa").text(saldo );
         
        if (saldo<0) {
            $("#spanBadge").addClass("badge-danger");
        } else {
            $("#spanBadge").addClass("badge-success");
        }
        
            
    }).fail( function( data,jqXHR, textStatus, errorThrown ) {
        console.log( "fail" );
        console.log(data);
        swal(
            "Error!",
            textStatus,
            "error"
          )


    }).always(function() {
        console.log( "complete tablaSaldosPagosResumenAjax" );
    });

}

var intervalId = window.setInterval(function(){
    
    //initAjaxSaldoPorEmpresa()
    

}, 10000);
