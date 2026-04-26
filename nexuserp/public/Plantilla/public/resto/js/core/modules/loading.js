function loadingBtn(elemento){
	elemento.innerHTML = '<i class="fas fa-spinner fa-pulse fa-2x"></i>';
	elemento.setAttribute("disabled","disabled");
}	

function deloadingBtn(elemento,txthtml){
	elemento.innerHTML = txthtml;
	elemento.removeAttribute("disabled");
}


function loadingDiv(elemento){
	elemento.innerHTML = '<img class="img-thumbnail" src="../../CONFIG/img/loading.gif" width="100%" alt="">';
}

function deloadingDiv(elemento,txthtml){
	elemento.innerHTML = txthtml;
}

function loadingCogs(elemento){
	elemento.innerHTML = '<div class="text-center"><i class="fas fa-cog fa-pulse fa-2x"></i></div>';
}

function deloadingCogs(elemento,txthtml){
	elemento.innerHTML = txthtml;
}

