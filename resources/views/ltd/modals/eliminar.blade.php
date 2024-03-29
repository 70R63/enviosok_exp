<a href="" class="remove-list text-danger tx-20 remove-button" data-toggle="modal" data-target="#modal{{ $row->id }}" >
	<i class="fa fa-trash" alt="Eliminar"></i>
</a>
							
<div class="modal fade" id="modal{{ $row->id }}" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
	    <div class="modal-content">
	    	<div class="modal-header">
	            <h5 class="modal-title" id="exampleModalLabel">Eliminar Registro</h5>
	            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
	            <span aria-hidden="true">×</span>
	            </button>
	         </div>

	    	<div class="modal-body">
	        	<p class="bigger-50 bolder center grey">
					<i class="ace-icon fa fa-hand-o-right blue bigger-120"></i>
					Seguro que quieres eliminar el ID {{ $row->id }}  con NOMBRE '{{ $row->nombre }}' ?  	
				</p>
	      	</div>
		     <div class="modal-footer">
		      	<button class="btn btn-primary" type="button" data-dismiss="modal">Cancelar</button>
		      	
		      	{!! Form::open([ 'route' => ['ltds.destroy', $row ], 'metdod' => 'PUT' ]) !!}
		      		@csrf
		      		{{method_field('DELETE')}}
					<a class="btn badge-dark" onclick="$(this).closest('form').submit();">Eliminar</a>
					
				{!! Form::close() !!}

		    </div> <!-- modal-footer -->
	    </div> <!-- modal-content -->
  	</div> <!-- modal-dialog -->
</div> <!--modal fad -->