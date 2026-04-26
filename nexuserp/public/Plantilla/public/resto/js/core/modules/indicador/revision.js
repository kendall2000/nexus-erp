//funciones javascript y validaciones
$(document).ready(function () {
    $(".select2").select2({ width: '100%'});
});

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function Limpiar() {
    swal({
        text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
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

function printTable(codigo, indicador, departamento, clasificacion, categoria) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla");
    http.append("codigo", codigo);
    http.append("indicador", indicador);
    http.append("departamento", departamento);
    http.append("clasificacion", clasificacion);
    http.append("categoria", categoria);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado );
                contenedor.innerHTML = '...';
                console.log(resultado.message);
                return;
            }
            //tabla
            var data = resultado.tabla;
            contenedor.innerHTML = data;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [{
                        extend: 'copy'
                    },
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'excel',
                        title: 'Tabla de Ejecucion'
                    },
                    {
                        extend: 'pdf',
                        title: 'Tabla de Ejecucion'
                    },
                    {
                        extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        },
                        title: 'Tabla de Ejecucion'
                    }
                ]
            });
        }
    };
}

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function cerrarRevision() {
    swal({
        text: "\u00BFDesea finalizar la toma de datos?, verifique bien sus datos y evidencias...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambiarSituacion(2);
                break;
            default:
                return;
        }
    });
}

function cambiarSituacion(situacion) {
    codigo = document.getElementById('revision');
    lectura = document.getElementById('lectura');
    evidencia = document.getElementById('evidencia').value;

    if (lectura.value !== "" && codigo.value !== "" && evidencia) {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "situacion");
        http.append("codigo", codigo.value);
        http.append("situacion", situacion);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_ejecucion.php");
        request.send(http);
        request.onreadystatechange = function () {
            console.log(request);
            if (request.readyState != 4)
                return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    window.location.href = "FRManotacion.php";
                });
            }
        };
    } else {
        if (lectura.value === "") {
            swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
            lectura.classList.add("is-invalid");
        } else {
            lectura.classList.remove("is-invalid");
        }
        if (!evidencia) {
            swal("Ohoo!", "Debe subir al menos una evidencia...", "error");
        }
    }
}

function modificar(elemento, campo) {
    codigo = document.getElementById("revision");
    var http = new FormData();
    http.append("request", "modificar");
    http.append("codigo", codigo.value);
    http.append("campo", campo);
    http.append("valor", elemento.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.readyState);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //console.log(resultado);
            if (resultado.status !== true) {
                console.log(resultado.message);
                return;
            }
            console.log(resultado.message);
        }
    };
}
/////////////////////////////////////// Archivos //////////////////////////////
function openInput(id) {
    inpfile = document.getElementById(id);
    inpfile.click();
}

function loadingGif(posicion) {
    document.getElementById("archivo" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function FotoJs() {
    posicion = document.getElementById("posicion");
    posicion.value = '1';
    inpfile = document.getElementById("foto");
    inpfile.click();
}


async function subirImagen() {
    revision = document.getElementById("revision");
    foto = document.getElementById("foto");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();
        http.append("revision", revision.value);
        http.append("posicion", posicion.value);
        http.append("foto", foto.files[0]);

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_cargar_archivo.php");
        request.onload = () => {
            if (request.status >= 200 && request.status < 300) {
                console.log(request.response);
                resolve(JSON.parse(request.response));
            } else {
                reject(request.statusText);
            }
        };
        request.onerror = () => reject(request.statusText);
        request.send(http);
    });
}

async function cargarFoto() {
    foto = document.getElementById("foto");
    revision = document.getElementById("revision");
    evidencia = document.getElementById("evidencia");
    if (revision.value !== "" && foto.value !== "") {
        extension = comprueba_extension(foto.value, 1);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-foto");
            var contenedor = document.getElementById("div-imagen");
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirImagen();
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-camera"></i> Agregar Imagen');
                deloadingDiv(contenedor, resultImg.imagen);
                evidencia.value = true;
            } else {
                swal("Error", resultImg.message, "error").then((value) => {
                    console.log(value);
                    window.location.reload();
                });
                return;
            }
        } else {
            swal("Alto!", "Este archivo no es extencion .jpg \u00F3 .png", "error");
        }
    } else {
        swal("Error", "Uno de los parametros est\u00E1 vacio, por favor refreseque e intente de nuevo...", "error");
    }
}


function DocumentoJs() {
    posicion = document.getElementById("posicion");
    posicion.value = '2';
    inpfile = document.getElementById("documento");
    inpfile.click();
}
async function subirDocumento() {
    revision = document.getElementById("revision");
    documento = document.getElementById("documento");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();
        http.append("revision", revision.value);
        http.append("posicion", posicion.value);
        http.append("documento", documento.files[0]);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_carga_documento.php");
        request.onload = () => {
            if (request.status >= 200 && request.status < 300) {
                //console.log(request.response);
                resolve(JSON.parse(request.response));
            } else {
                reject(request.statusText);
            }
        };
        request.onerror = () => reject(request.statusText);
        request.send(http);
    });
}

async function cargarDocumento() {
    documento = document.getElementById("documento");
    revision = document.getElementById("revision");
    evidencia = document.getElementById("evidencia");
    if (revision.value !== "" && documento.value !== "") {
        extension = comprueba_extension(documento.value, 2);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-documento");
            var contenedor = document.getElementById("div-documento");
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirDocumento();
            //console.log(resultImg);
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-file-text"></i> Agregar Documento');
                deloadingDiv(contenedor, resultImg.imagen);
                evidencia.value = true;
            } else {
                swal("Error", resultImg.message, "error").then((value) => {
                    console.log(value);
                    window.location.reload();
                });
                return;
            }
        } else {
            swal("Alto!", "Este archivo no es extencion .PDF...", "error");
        }
    } else {
        swal("Error", "Uno de los parametros est\u00E1 vacio, por favor refreseque e intente de nuevo...", "error");
    }
}
////////////////////////////// Prompts ///////////////////////////////
function verRevision(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/indicadores/revision.php", {codigo: codigo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}


function printTableAnotaciones() {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    proceso = document.getElementById("proceso");
    sistema = document.getElementById("sistema");
    desde = document.getElementById("desde");
    hasta = document.getElementById("hasta");

    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_anotaciones");
    http.append("proceso", proceso.value);
    http.append("sistema", sistema.value);
    http.append("desde", desde.value);
    http.append("hasta", hasta.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_ejecucion.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado );
                contenedor.innerHTML = '...';
                console.log(resultado.message);
                return;
            }
            //tabla
            var data = resultado.tabla;
            contenedor.innerHTML = data;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [{
                        extend: 'copy'
                    },
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'excel',
                        title: 'Tabla de Ejecucion'
                    },
                    {
                        extend: 'pdf',
                        title: 'Tabla de Ejecucion'
                    },
                    {
                        extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        },
                        title: 'Tabla de Ejecucion'
                    }
                ]
            });
        }
    };
}