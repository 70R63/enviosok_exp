<!-- Modal -->
{!! Form::open([ 'route' => 'cotizaciones.create', 'method' => 'GET' , 'class'=>'parsley-style-1', 'id'=>'generalForm' ]) !!}
<div class="modal" id="myModal">
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
                            $<span id="spanPrecio"></span> MXP
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
                    <button type="submit" class="btn btn-primary ml-3" >Continuar</button>
                    <a class="btn badge-dark" data-dismiss="modal" type="button">Cerrar</a>
                </div>
            </div>
            <!-- FIN class="modal-body" -->
        </div>
    </div>
</div>


@include('cotizaciones.forma.guiastore_ocultos')
{!! Form::close() !!} 