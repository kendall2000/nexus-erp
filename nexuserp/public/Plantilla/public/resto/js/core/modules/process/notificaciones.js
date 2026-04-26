function alertaAprobar(){
    fichasSinAprobar = document.getElementById("total_fichas_sin_aprobar");
    if (fichasSinAprobar.value) {
        swal({
                title: "Fichas pendientes de aprobar",
                text: "Tienes " + fichasSinAprobar.value + " fichas de proceso pendientes de aprobar. Deseas verificarlas? ",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((value) => {
                if (value) {
                    window.open("CPPROCESS/FRMaprobaciones.php");
                    cerrar();	
                } else {
                    cerrar();
                }
            });
    }
}
function alertaActualizar(){
    fichasActualizar = document.getElementById("total_fichas_actualizacion");
    if (fichasActualizar.value) {
        swal({
                title: "Fichas pendientes de actualizar",
                text: "Tienes " + fichasActualizar.value + " fichas de proceso pendientes de Actualizar. Deseas verificarlas? ",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((value) => {
                if (value) {
                    window.open("CPPROCESS/FRMmis_fichas.php");
                    cerrar();	
                } else {
                    cerrar();
                }
            });
    }
}