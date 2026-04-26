function identificar_riesgo(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/riesgo.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}
function identificar_indicador(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/indicador.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}
function identificar_auditoria_externa(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/externa.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}
function identificar_auditoria_interna(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/interna.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}
function identificar_queja(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/queja.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargadodddddddddddd
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}


function identificar_requisito(codigo){
	cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/mejora/requisito.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargadodddddddddddd
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
		$(".select2").select2();
	}, 250);
}

function update(elemento, campo) {
	codigo = document.getElementById("codigo");
	var http = new FormData();
	http.append("request", "update");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_mejora.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			// console.log(resultado.message);fffffffff
		}
	};
}


function update_interna_detalle(elemento, campo) {
	codigo = document.getElementById("auditoria");
	var http = new FormData();
	http.append("request", "update_interna_detalle");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_mejora.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			// console.log(resultado.message);
		}
	};
}

function update_externa_detalle(elemento, campo) {
	codigo = document.getElementById("codigo");
	var http = new FormData();
	http.append("request", "update_externa_detalle");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_mejora.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			// console.log(resultado.message);
		}
	};
}

function update_tipo_evaluacion(elemento, campo) {
	codigo = document.getElementById("codigo_tipo_evaluacion");
	var http = new FormData();
	http.append("request", "update_tipo_evaluacion");
	http.append("codigo", codigo.value);
	http.append("campo", campo);
	http.append("valor", elemento.value);
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_mejora.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log(request.readyState);
		if (request.readyState != 4) return;
		if (request.status === 200) {
			console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			//console.log(resultado);
			if (resultado.status !== true) {
				console.log(resultado.message);
				return;
			}
			// console.log(resultado.message);
		}
	};
}

function detalle_indicador(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/indicadores/detalle.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}