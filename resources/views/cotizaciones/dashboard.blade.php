@extends('dashboard')
@section('content')

@include('cotizaciones.dashboard.header')
<!--Row-->
<div class="row row-sm">
    <div class="col-lg-12">
        <div class="card custom-card mg-b-20">
            <div class="card-body">
                <div class="card-header border-bottom-0 pt-0 pl-0 pr-0 d-flex">
                    <div>
                        <label class="main-content-label mb-2">Creacion</label> 
                        <span class="d-block tx-12 mb-3 text-muted">
                            Seccion para la creacion de una guia con diferentes metodos automatica, semi manual y manual 
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Row end -->
 {!! Form::open([ 'route' => 'api.cotizaciones.index', 'method' => 'GET' , 'class'=>'parsley-style-1', 'id'=>'cotizacionesForm' ]) !!}
<!--Row-->
<div class="row row-sm">
    <div class="col-lg-12 col-xl-5  col-md-12">
        <div class="card custom-card ">
            <div class="card-body">
                <div class="col-sm-5 ">
                    <div>
                        <span class="tx-18 mb-3">PAQUETE</span> 
                    </div>
                </div>
                 @include('cotizaciones.forma.paquete')

            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-7">
        <div class="card custom-card">
            <div class="card-body">
                <div class="row mt-2">
                    <div class="col-sm-2 my-auto border-right text-centerd-flex no-block align-items-center">
                         @include('cotizaciones.forma.check_manual')
                    </div>

                    
                    <div class="col-sm-5 my-auto text-sm-center">
                        @include('cotizaciones.forma.origen')
                        
                    </div>
                    <div class="col-sm-5 my-auto text-sm-center">
                        @include('cotizaciones.forma.destino')
                        
                    </div>
                    <div class="col-sm-10 my-auto text-sm-center clienteCombo" style="display:none" >
                        @include('cotizaciones.forma.clienteCombo')
                        
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
<!-- Row end -->

<div class="col-lg-12">
    <div class="form-group row justify-content-around">     
        <div>
            <a id="cotizar" class="btn btn-primary" >Cotizar</a>    
            <a id="limpiar" class="btn badge-dark" >Limpiar</a>
            
        </div>   
    </div>  
</div>
{!! Form::close() !!}  
<!--Row-->
<div class="row row-sm">
    <div class="col-lg-12 col-xl-12  col-md-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="card-header border-bottom-0 pt-0 pl-0 pr-0 d-flex">
                    
                </div>
                @include('cotizaciones.dashboard.tabla')    
            </div>
        </div>
    </div>
</div>
@include('cotizaciones.modals.resumen_cotizacion')
@include('cotizaciones.modals.resumen_cotizacion_mercadoPago')
@endsection
