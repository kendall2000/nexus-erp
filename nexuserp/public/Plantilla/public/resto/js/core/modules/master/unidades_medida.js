//funciones javascript y validaciones
$(document).ready(function() {
    printTable('');
    $(".select2").select2();
});

function comprobar_vacios(desc, abrev, clase) {
	
    if(desc.value === ""){
        desc.classList.add("is-invalid");
    }else{
        desc.classList.remove("is-invalid");
    }
    if(abrev.value === ""){
        abrev.classList.add("is-invalid");
    }else{
        abrev.classList.remove("is-invalid");
    }
    if(clase.value === ""){
        clase.parentNode.classList.add('has-error');
    }else{
        clase.parentNode.classList.remove('has-error');
    }
    swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
}

function Limpiar(){
    swal({
        text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true,},
        }
    }).then((value) => {
        switch (value) {
            case true:
                window.location.reload();
                break;
            default:
              return;
        }
    });
}

function printTable(codigo){
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request","tabla");
    http.append("codigo",codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_umedidas.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                contenedor.innerHTML = '...';
                console.log( resultado.message );
                return;
            }
            //tabla
            var data = resultado.tabla;
            contenedor.innerHTML = data;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Tabla de Categor\u00EDas'},
                    {extend: 'pdf', title: 'Tabla de Categor\u00EDas'},
                    {extend: 'print',
                        customize: function (win){
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        }, title: 'Tabla de Categor\u00EDas'
                    }
                ]
            });
            
        }
    };     
}

function seleccionarUmedida(codigo){
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request","get");
    http.append("codigo",codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_umedidas.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                swal("Error", resultado.message , "error");
                return;
            }
            var data = resultado.data;
            //console.log( data );
            //set
            document.getElementById("codigo").value = data.codigo;
            document.getElementById("desc").value = data.desc;
            document.getElementById("abrev").value = data.abrev;
            document.getElementById("clase").value = data.clase;
            //document.getElementById("btn-color").style.backgroundColor = data.color;
            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("desc").focus(); 
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };     
}
                    
function Grabar(){
    desc = document.getElementById('desc');
    abrev = document.getElementById('abrev');
    clase = document.getElementById('clase');
    
    if(desc.value !== "" && abrev.value !== ""){
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request","grabar");
        http.append("desc", desc.value);
        http.append("abrev", abrev.value);
        http.append("clase", clase.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_umedidas.php");
        request.send(http);
        request.onreadystatechange = function(){
           //console.log( request );
           if(request.readyState != 4) return;
           if(request.status === 200){
                //console.log( request.responseText );
                resultado = JSON.parse(request.responseText);
                if(resultado.status !== true){
                    swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };     
    }else comprobar_vacios(desc, abrev, clase);
}

function Modificar(){
    codigo = document.getElementById('codigo');
    desc = document.getElementById('desc');
    abrev = document.getElementById('abrev');
    clase = document.getElementById('clase');
    
    if(desc.value !== "" && abrev.value !== ""){
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request","modificar");
        http.append("codigo", codigo.value);
        http.append("desc", desc.value);
        http.append("abrev", abrev.value);
        http.append("clase", clase.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_umedidas.php");
        request.send(http);
        request.onreadystatechange = function(){
           //console.log( request );
           if(request.readyState != 4) return;
           if(request.status === 200){
            resultado = JSON.parse(request.responseText);
                if(resultado.status !== true){
                    swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };     
    }else comprobar_vacios(desc, abrev, clase);
}

function deshabilitarUmedida(codigo){
    swal({
        text: "\u00BFDesea quitar a esta unidad de medida del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacion(codigo,0);
                break;
            default:
              return;
        }
    });
}


function cambioSituacion(codigo,situacion){
    /////////// POST /////////
    var http = new FormData();
    http.append("request","situacion");
    http.append("codigo",codigo);
    http.append("situacion",situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_umedidas.php");
    request.send(http);
    request.onreadystatechange = function(){
        console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                //console.log( resultado.sql );
                swal("Error", resultado.message , "error");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value)=>{ window.location.reload(); });
        }
    };     
}