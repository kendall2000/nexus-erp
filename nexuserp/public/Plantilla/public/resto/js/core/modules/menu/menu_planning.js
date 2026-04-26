//funciones javascript y validaciones
$(document).ready(function () {
	graficasCumplimiento();
});

function Submit() {
	graficasCumplimiento();
};

function graficasCumplimiento() {
	cumplimientoProceso();
	cumplimientoSistema();
	cumplimientoTipo();
	cumplimientoGeneral();
}

function cumplimientoGeneral() {
	//--
	pieContainer = document.getElementById("pieContainer");
	loadingCogs(pieContainer);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_general");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				pieContainer.innerHTML = '...';
				pieContainer.innerHTML = '...';
				// console.log("Error: ", resultado.message, ';', request.responseText);
				// console.log(request.responseText);
				return;
			}
			//data
			pieContainer.innerHTML = '';
			var stockedPie = document.createElement("div");
			stockedPie.setAttribute("id", "pie");
			pieContainer.appendChild(stockedPie);
			
			////////////////// GRAFICA PIE ///////////////////////
			c3.generate({
				bindto: '#pie',
				data:{
					columns: [
						['Cumplido', resultado.cumplimiento],
						['Pendiente', (100 - resultado.cumplimiento)],
					],
					colors:{
						Cumplido: '#1D9619',
						Pendiente: '#fbc658'
					},
					type : 'pie'
				}
			});
		}
	};
}
function cumplimientoProceso() {
	//--
	// pieContainer = document.getElementById("pieContainer");
	stocked0Container = document.getElementById("stocked0Container");
	// loadingCogs(pieContainer);
	loadingCogs(stocked0Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_proceso");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked0Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked0Container.innerHTML = resultado.data;
		}
	};
}
function cumplimientoSistema() {
	//--
	stocked2Container = document.getElementById("stocked2Container");
	loadingCogs(stocked2Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_sistema");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked2Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked2Container.innerHTML = resultado.data;
		}
	};
}
function cumplimientoTipo() {
	//--
	stocked1Container = document.getElementById("stocked1Container");
	loadingCogs(stocked1Container);
	/////////// POST /////////
	var http = new FormData();
	http.append("request", "cumplimiento_tipo");
	var request = new XMLHttpRequest();
	request.open("POST", "ajax_fns_planning.php");
	request.send(http);
	request.onreadystatechange = function () {
		//console.log( request );
		if (request.readyState != 4) return;
		if (request.status === 200) {
			// console.log(request.responseText);
			resultado = JSON.parse(request.responseText);
			if (resultado.status !== true) {
				// pieContainer.innerHTML = '...';
				stocked1Container.innerHTML = '...';
				console.log("Error: ", resultado.message, ';', request.responseText);
				console.log(request.responseText);
				return;
			}
			stocked1Container.innerHTML = resultado.data;
		}
	};
}
