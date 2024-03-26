<!-- Modal -->
<script src="https://sdk.mercadopago.com/js/v2">
    
    
</script>
<script type="text/javascript">
    //const mp = new MercadoPago('TEST-21790bfd-c517-494f-a444-ef70f555a49b');
    //const bricksBuilder = mp.bricks();
</script>
<div class="modal" id="myModalMercadoPago">

    <div class="modal-dialog" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">
                    <span id="spanTitulo"> </span>
                </h6>
                <button aria-label="Close" class="close" data-dismiss="modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card custom-card pricingTable2">
                    <div class="pricingTable2-header">
                        <h2>PRECIO </h2> <h6>CON IVA </h6>
                    </div>
                    <div class="pricing-plans  bg-primary">
                        <span class="price-value1">
                            $<span id="spanPrecio" class="spanPrecio"></span> MXP
                        </span>
                    </div>
                    <div class="pricingContent2">
                        <ul>
                            <h4>
                                <li><b>Mensajeria:</b> <span id="spanMensajeria"> </span> - <span id="spanservicioId"> </span> </li>
                                <li><b>Remitente Postal:</b> <span id="spanRemitente"> </span>
                                    
                                </li>
                                <li><b>Destinatorio Postal:</b> <span id="spanDestinatario"> </span>
                                    
                                </li>
                                <li>
                                    <b>Piezas:</b> <span id="spanPieza"></span>,
                                    <b>Peso Facturado:</b> <span id="spanPeso"></span> Kg.
                                </li>
                                <li>
                                    <b>Área Extendida:</b><span id="spanAreaExtendida"></span> Genera costo adicional
                                    
                                </li>
                            </h4>
                            <div>
                                <b>Valor de Envio:</b>$ <span id="spanValorEnvio"></span>
                                <b>Seguro:</b>$ <span id="spanSeguro"></span>
                            </div>
                            <div>
                                <b>Cotización Manual:</b><span id="spanCotizacionManual"></span>
                                <b>,   Ocurre:</b> <span id="spanOcurre"></span>
                            </div>
                            <div>
                                <b>Zona:</b><span id="spanZona"></span>
                            </div>
                            
                        </ul>
                    </div>
                    
                    <div class="pricing-plans  bg-primary">
                        Para continuar con la creación, presionar el boton continuar
                    </div>
                </div>
                <div class="modal-footer">

                    <div id="wallet_container">Elimianr previo </div>
                    <div id="mercadopago"></div>
                    <a class="btn badge-dark" data-dismiss="modal" type="button">Cerrar</a>
                    
                </div>
            </div>
            <!-- FIN class="modal-body" -->
        </div>
    </div>


@include('cotizaciones.forma.guiastore_ocultos')


</div>