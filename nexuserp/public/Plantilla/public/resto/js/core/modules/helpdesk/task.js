//funciones javascript y validaciones
$(document).ready(function(){
    printTable(document.getElementById('codigo').value);
    $('.dual_select').bootstrapDualListbox({
        selectorMinimalHeight: 160,
    });
    $("#form").submit(function() {
        GrabarAsignar($('[name="duallistbox1[]"]').val());
        return false;
    });
    $(".select2").select2();			
});

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

function Submit(){
    myform = document.forms.f1;
    myform.submit();
}

function printTable(codigo){
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request","tabla");
    http.append("codigo",codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_incidente.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                //console.log( resultado );
                contenedor.innerHTML = '...';
                swal("Error", resultado.message , "error");
                return;
            }
            //tabla
            var data = resultado.tabla;
            contenedor.innerHTML = data;
            $('#tabla').DataTable({
                pageLength: 100,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Reporte de Incidente'},
                    {extend: 'pdf', title: 'Reporte de Incidente'},
                    {extend: 'print',
                        customize: function (win){
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit');
                      },
                      title: 'Reporte de Incidente'
                    }
                ]
            });
        }
    };     
}
                    
function GrabarAsignar(arrusuarios){
    codigo = document.getElementById('codigo');
    categoria = document.getElementById('categoria');
    prioridad = document.getElementById('prioridad');
    nombre = document.getElementById('nombre');
    ///
    selectscategoria = document.getElementById("select2-categoria-container");
    selectprioridad = document.getElementById("select2-prioridad-container");
    
    if(nombre.value !== "" && categoria.value !=="" && prioridad.value !==""){
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request","grabar");
        http.append("incidente", codigo.value);
        http.append("categoria", categoria.value);
        http.append("prioridad", prioridad.value);
        http.append("nombre", nombre.value);
        http.append("usuarios", arrusuarios);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_incidente.php");
        request.send(http);
        request.onreadystatechange = function(){
           //console.log( request );
           if(request.readyState != 4) return;
           if(request.status === 200){
            resultado = JSON.parse(request.responseText);
                if(resultado.status !== true){
                    console.log( resultado.sql );
                    swal("Error", resultado.message , "error").then((value) => { deloadingBtn(boton,'<i class="fa fa-save"></i> Grabar'); });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.reload();
                });
            }
        };     
    }else{
        if(categoria.value === ""){
            selectscategoria.className = "select-danger select2-selection__rendered";
        }else{
            selectscategoria.className = "select2-selection__rendered";
        }
        if(prioridad.value === ""){
            selectprioridad.className = "select-danger select2-selection__rendered";
        }else{
            selectprioridad.className = "select2-selection__rendered";
        }
        if(nombre.value === ""){
            nombre.classList.add("is-invalid");
        }else{
            nombre.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function seleccionarIncidente(incidente){
    window.location.href="FRMincidentes.php?codigo="+incidente;
}


function deshabilitarIncidente(codigo){
    swal({
        title: "\u00BFEsta Seguro?",
        text: "\u00BFDesea quitar a este Incidente del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
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
    request.open("POST", "ajax_fns_incidente.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
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

function usuariosIncidente(codigo){
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/incidentes/usuarios.php",{codigo:codigo}, function(data){
    // Ponemos la respuesta de nuestro script en el DIV recargado
    $("#Pcontainer").html(data);
    });
    abrirModal();
}