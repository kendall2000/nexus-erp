/*Ejecuta el Modal Cargando*/
/*Manuel Sosa Julio 2019*/

/////////// Modal Peque�o ////////////
function abrir(){
//	$('#myModal').modal('show');
	$('#myModal').modal({
		keyboard: false,
		backdrop: false
	});
	
	document.getElementById('ModalDialog').className = "modal-dialog modal-sm";
	document.getElementById('lblparrafo').style.display="bolck";
	document.getElementById('Pcontainer').style.display="none";
	
	return;
}


function cerrar(){
	msj = '<img src="../../CONFIG/img/img-loader.gif"/><br>';
	msj+= '<label align ="center">Transacci\u00F3n en Proceso...</label>';
	//msj+= '<div class="modal-footer"><button type="button" class="btn btn-primary" onclick="cerrar();" >Aceptar</button></div>';
	document.getElementById('lblparrafo').innerHTML = msj;
	document.getElementById('Pcontainer').innerHTML = '';
	document.getElementById('Pcontainer').style.display="block";
	document.getElementById('lblparrafo').style.display="block";
	
	$('#myModal').modal('hide');
	return;
}



/////////// Modal Mediano ////////////
function abrirModal(){
	//$('#myModal').modal('show');

	$('#myModal').modal({
		keyboard: false,
		backdrop: false
	});
	
	document.getElementById('ModalDialog').className = "modal-dialog modal-lg";
	document.getElementById('Pcontainer').style.display="block";
	document.getElementById('lblparrafo').style.display="none";
	
	return;
}

function cerrarModal(){
	msj = '<img src="../../CONFIG/img/img-loader.gif"/><br>';
	msj+= '<label align ="center">Transacci\u00F3n en Proceso...</label>';
	msj+= '<div class="modal-footer"><button type="button" class="btn btn-primary" onclick="cerrar();" >Aceptar</button></div>';
	document.getElementById('lblparrafo').innerHTML = msj;
	document.getElementById('Pcontainer').innerHTML = '';
	document.getElementById('Pcontainer').style.display="block";
	document.getElementById('lblparrafo').style.display="block";
	
	$('#myModal').modal('hide');
	return;
}
