//funciones javascript y validaciones
$(document).ready(function () {
    $(".select2").select2(); 
});

function atras() {
    if (document.getElementById('codigo').value != '')
        window.location.reload();
    else
        window.history.back();
}

/////////////////////////////////////////////////////////////////////
/////////////// FORMULARIOS AJAX //////////////////////////////
/////////////////////////////////////////////////////////////////////

function printTable_activos_falla(codigo) {
    contenedor = document.getElementById("result");
    nivel = document.getElementById("nivel");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_activos_falla");
    http.append("area", codigo);
    http.append("nivel", nivel.value);

    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_activo.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log(request.responseText);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
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
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Tabla de Activos'},
                    {extend: 'pdf', title: 'Tabla de Activos'},
                    {
                        extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        }, title: 'Tabla de Activos'
                    }
                ]
            });
        }
    };
}

function get_value(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);

    secNombre = document.getElementById("secNombre");
    nivel = document.getElementById("nivel");
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get_value");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_activo.php");
    request.send(http);
    request.onreadystatechange = function () {
        // console.log( request.responseText);
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                contenedor.innerHTML = '...';
                console.log(resultado.message);
                return;
            }
            //tabla
            var data = resultado.data;
            secNombre.value = data.secNombre;
            nivel.value = data.nivel;
            contenedor.innerHTML = resultado.tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Tabla de Activos'},
                    {extend: 'pdf', title: 'Tabla de Activos'},
                    {
                        extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        }, title: 'Tabla de Activos'
                    }
                ]
            });
        }
    };
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////




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

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function printTable(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_activo.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
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
                buttons: [
                    {extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'Tabla de Activos'},
                    {extend: 'pdf', title: 'Tabla de Activos'},
                    {
                        extend: 'print',
                        customize: function (win) {
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');
                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                        }, title: 'Tabla de Activos'
                    }
                ]
            });
        }
    };
}

function Grabar() {
    sede = document.getElementById('sede');
    sector = document.getElementById('sector');
    area = document.getElementById('area');
    nombre = document.getElementById('nombre');
    marca = document.getElementById('marca');
    serie = document.getElementById('serie');
    modelo = document.getElementById('modelo');
    parte = document.getElementById('parte');
    proveedor = document.getElementById('proveedor');
    periodicidad = document.getElementById('periodicidad');
    capacidad = document.getElementById('capacidad');
    cantidad = document.getElementById('cantidad');
    precioNuevo = document.getElementById('precioNuevo');
    precioCompra = document.getElementById('precioCompra');
    precioActual = document.getElementById('precioActual');
    observaciones = document.getElementById('observaciones');

    if (sede.value !== "" && area.value !== "" && nombre.value !== "" && marca.value !== "" && cantidad.value !== "" && precioNuevo.value !== "" && precioCompra.value !== "" && precioCompra.value !== "" && periodicidad.value !== "") {

        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar");
        http.append("sede", sede.value);
        http.append("sector", sector.value);
        http.append("area", area.value);
        http.append("nombre", nombre.value);
        http.append("marca", marca.value);
        http.append("serie", serie.value);
        http.append("modelo", modelo.value);
        http.append("parte", parte.value);
        http.append("proveedor", proveedor.value);
        http.append("periodicidad", periodicidad.value);
        http.append("capacidad", capacidad.value);
        http.append("cantidad", cantidad.value);
        http.append("precioNuevo", precioNuevo.value);
        http.append("precioCompra", precioCompra.value);
        http.append("precioActual", precioActual.value);
        http.append("observaciones", observaciones.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_activo.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
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
                    window.location.reload();
                });
            }
        };
    } else {
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
            document.getElementById('secNombre').classList.add("is-invalid");
            document.getElementById('nivel').classList.add("is-invalid");
        } else {
            area.parentNode.classList.remove('has-error');
            document.getElementById('secNombre').classList.remove("is-invalid");
            document.getElementById('nivel').classList.remove("is-invalid");
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (marca.value === "") {
            marca.classList.add("is-invalid");
        } else {
            marca.classList.remove("is-invalid");
        }
        if (cantidad.value === "") {
            cantidad.classList.add("is-invalid");
        } else {
            cantidad.classList.remove("is-invalid");
        }
        if (precioNuevo.value === "") {
            precioNuevo.classList.add("is-invalid");
        } else {
            precioNuevo.classList.remove("is-invalid");
        }
        if (precioCompra.value === "") {
            precioCompra.classList.add("is-invalid");
        } else {
            precioCompra.classList.remove("is-invalid");
        }
        if (precioActual.value === "") {
            precioActual.classList.add("is-invalid");
        } else {
            precioActual.classList.remove("is-invalid");
        }
        if (periodicidad.value === "") {
            periodicidad.parentNode.classList.add('has-error');
        } else {
            periodicidad.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function Modificar() {
    codigo = document.getElementById('codigo');
    sede = document.getElementById('sede');
    sector = document.getElementById('sector');
    area = document.getElementById('area');
    nombre = document.getElementById('nombre');
    marca = document.getElementById('marca');
    serie = document.getElementById('serie');
    modelo = document.getElementById('modelo');
    parte = document.getElementById('parte');
    proveedor = document.getElementById('proveedor');
    periodicidad = document.getElementById('periodicidad');
    capacidad = document.getElementById('capacidad');
    cantidad = document.getElementById('cantidad');
    precioNuevo = document.getElementById('precioNuevo');
    precioCompra = document.getElementById('precioCompra');
    precioActual = document.getElementById('precioActual');
    observaciones = document.getElementById('observaciones');

    if (sede.value !== "" && area.value !== "" && nombre.value !== "" && marca.value !== "" && cantidad.value !== "" && precioNuevo.value !== "" && precioCompra.value !== "" && precioCompra.value !== "" && periodicidad.value !== "") {

        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar");
        http.append("codigo", codigo.value);
        http.append("sede", sede.value);
        http.append("sector", sector.value);
        http.append("area", area.value);
        http.append("nombre", nombre.value);
        http.append("marca", marca.value);
        http.append("serie", serie.value);
        http.append("modelo", modelo.value);
        http.append("parte", parte.value);
        http.append("proveedor", proveedor.value);
        http.append("periodicidad", periodicidad.value);
        http.append("capacidad", capacidad.value);
        http.append("cantidad", cantidad.value);
        http.append("precioNuevo", precioNuevo.value);
        http.append("precioCompra", precioCompra.value);
        http.append("precioActual", precioActual.value);
        http.append("observaciones", observaciones.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_activo.php");
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
                    window.location.reload();
                });
            }
        };
    } else {
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
            document.getElementById('secNombre').classList.add("is-invalid");
            document.getElementById('nivel').classList.add("is-invalid");
        } else {
            area.parentNode.classList.remove('has-error');
            document.getElementById('secNombre').classList.remove("is-invalid");
            document.getElementById('nivel').classList.remove("is-invalid");
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        if (marca.value === "") {
            marca.classList.add("is-invalid");
        } else {
            marca.classList.remove("is-invalid");
        }
        if (cantidad.value === "") {
            cantidad.classList.add("is-invalid");
        } else {
            cantidad.classList.remove("is-invalid");
        }
        if (precioNuevo.value === "") {
            precioNuevo.classList.add("is-invalid");
        } else {
            precioNuevo.classList.remove("is-invalid");
        }
        if (precioCompra.value === "") {
            precioCompra.classList.add("is-invalid");
        } else {
            precioCompra.classList.remove("is-invalid");
        }
        if (precioActual.value === "") {
            precioActual.classList.add("is-invalid");
        } else {
            precioActual.classList.remove("is-invalid");
        }
        if (periodicidad.value === "") {
            periodicidad.parentNode.classList.add('has-error');
        } else {
            periodicidad.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function seleccionarActivo(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_activo.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            var data = resultado.data;
            //console.log( data );
            //set
            document.getElementById("codigo").value = data.codigo;
            document.getElementById("sede").value = data.sede;
            document.getElementById("secNombre").value = data.secNombre;
            document.getElementById("sector").value = data.sector;
            document.getElementById("area").value = data.area;
            document.getElementById("nivel").value = data.nivel;
            document.getElementById("nombre").value = data.nombre;
            document.getElementById("marca").value = data.marca;
            document.getElementById("serie").value = data.serie;
            document.getElementById("modelo").value = data.modelo;
            document.getElementById("parte").value = data.parte;
            document.getElementById("proveedor").value = data.proveedor;
            document.getElementById("periodicidad").value = data.periodicidad;
            document.getElementById("capacidad").value = data.capacidad;
            document.getElementById("cantidad").value = data.cantidad;
            document.getElementById("precioNuevo").value = data.precioNuevo;
            document.getElementById("precioCompra").value = data.precioCompra;
            document.getElementById("precioActual").value = data.precioActual;
            document.getElementById("observaciones").value = data.observaciones;

            // Renderizado
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            // Botones
            document.getElementById("nombre").focus();
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
        }
    };
}

function setArea(area) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "getArea");
    http.append("area", area);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_activo.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4)
            return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            var data = resultado.data;
            //set
            document.getElementById("sector").value = data.sector;
            document.getElementById("sede").value = data.sede;
            document.getElementById("nivel").value = data.nivel;
            document.getElementById("secNombre").value = data.secNombre;
        }
    };
}

function fichaActivo(hashkey) {
    window.location.href = "FRMficha.php?hashkey=" + hashkey;
}

function fotosActivo(hashkey) {
    window.location.href = "FRMfotos.php?hashkey=" + hashkey;
}

function fallasActivo(hashkey) {
    window.location.href = "FRMhistorial_fallas.php?hashkey=" + hashkey;
}

function fallaActivo(hashkey) {
    window.location.href = "FRMreportar_falla.php?hashkey=" + hashkey;
}

function FotoJs(activo, posicion) {
    inpfile = document.getElementById("imagen");
    inpfile.click();
    document.getElementById("codigo").value = activo;
    document.getElementById("posicion").value = posicion;
}

async function subirImagen() {
    activo = document.getElementById("codigo");
    imagen = document.getElementById("imagen");
    posicion = document.getElementById("posicion");
    return new Promise((resolve, reject) => {
        /////////// POST /////////
        var http = new FormData();
        http.append("activo", activo.value);
        http.append("posicion", posicion.value);
        http.append("imagen", imagen.files[0]);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_cargar_imagen.php");
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

async function cargarFoto() {
    imagen = document.getElementById("imagen");
    activo = document.getElementById("codigo");
    posicion = document.getElementById("posicion");
    if (activo.value !== "" && imagen.value !== "") {
        extension = comprueba_extension(imagen.value, 1);
        if (extension === 1) {
            /////////// POST /////////
            var boton = document.getElementById("btn-foto" + posicion.value);
            var contenedor = document.getElementById("div-imagen" + posicion.value);
            console.log(posicion.value);
            loadingBtn(boton);
            loadingDiv(contenedor);
            let resultImg = await subirImagen();
            if (resultImg.status) {
                deloadingBtn(boton, '<i class="fa fa-camera"></i> Agregar Imagen');
                deloadingDiv(contenedor, resultImg.imagen);
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


function deleteFotoConfirm(codigo, posicion) {
    swal({
        title: "\u00BFDesea Eliminar la Foto?",
        text: "\u00BFEsta seguro(a) de eliminar esta imagen del archivo de este activo?",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: {text: "Aceptar", value: true, }
        }
    }).then((value) => {
        switch (value) {
            case true:
                deleteFoto(codigo, posicion);
                break;
            default:
                return;
        }
    });
}

function deleteFoto(codigo, posicion) {

    loadingGif(posicion);
    myform = document.forms.f1;
    var formData = new FormData(myform);
    formData.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "EXEdelete_foto.php");
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState != 4)
            return;
        //alert(request.status);
        if (request.status === 200) {
            //alert("Status: " + request.status + " | Respuesta: " + request.responseText);
            //console.log(request.responseText);
            resultado = JSON.parse(request.responseText);
            //alert(resultado.status + ", " + resultado.message + ", " + resultado.img);
            //console.log(resultado);
            if (resultado.status !== 1) {
                swal("Error en la transacci\u00F3n", resultado.message, "error");
                return;
            }
            var arrimagenes = resultado.img;
            var imagenes = '';
            arrimagenes.forEach(function (element) {
                //console.log(element.foto);
                imagenes += element.foto;
            });
            document.getElementById("div-imagen" + posicion).innerHTML = imagenes;
        } else {
            //alert("Error: " + request.status + " " + request.responseText);
            swal("Error en la carga", "Error en la carga de la imagen", "error");
            return;
        }
    };
    cerrar();
}

function loadingGif(posicion) {
    document.getElementById("div-imagen" + posicion).innerHTML = '<img src="../../CONFIG/img/loading.gif" alt="...">';
}

function loadingButton(elemento) {
    elemento.setAttribute("disabled", "disabled");
    elemento.innerHTML = '<img src="../../CONFIG/img/img-loader.gif" width="15px" alt="cargando...">';
}





//////////////////////////////////////////////////////////////////////////////


function newFalla(activo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/activos/newfalla.php", {activo: activo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
    setTimeout(function () {
        $(".select2").select2();
    }, 500);
}

function updateFalla(codigo, activo) {
    document.getElementById("Pcontainer").innerHTML = '<div class = "text-center"><img src="../../CONFIG/img/loading.gif" alt="..."><br>cargando...</div>';
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/activos/updatefalla.php", {codigo: codigo, activo: activo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    setTimeout(function () {
        $(".select2").select2();
    }, 500);
}

function solucionFalla(codigo, activo) {
    document.getElementById("Pcontainer").innerHTML = '<div class = "text-center"><img src="../../CONFIG/img/loading.gif" alt="..."><br>cargando...</div>';
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/activos/solucion.php", {codigo: codigo, activo: activo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
}

function listFallas(activo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/activos/fallas.php", {activo: activo}, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}


function GrabarFalla() {
    activo = document.getElementById('activo');
    falla = document.getElementById('falla');
    fecha = document.getElementById('fecha');
    hora = document.getElementById('hora');
    situacion = document.getElementById('situacion');

    if (activo.value !== "" && falla.value !== "" && fecha.value !== "" && hora.value !== "" && situacion.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_falla");
        http.append("activo", activo.value);
        http.append("falla", falla.value);
        http.append("fecha", fecha.value);
        http.append("hora", hora.value);
        http.append("situacion", situacion.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_activo.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
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
                    window.location.reload();
                });
            }
        };
    } else {
        if (falla.value === "") {
            falla.classList.add("is-invalid");
        } else {
            falla.classList.remove("is-invalid");
        }
        if (fecha.value === "") {
            fecha.classList.add("is-invalid");
        } else {
            fecha.classList.remove("is-invalid");
        }
        if (hora.value === "") {
            hora.classList.add("is-invalid");
        } else {
            hora.classList.remove("is-invalid");
        }
        if (situacion.value === "") {
            situacion.parentNode.classList.add('has-error');
        } else {
            situacion.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function ModificarFalla() {
    codigo = document.getElementById('codigoFalla');
    activo = document.getElementById('activo');
    falla = document.getElementById('falla');
    fecha = document.getElementById('fecha');
    hora = document.getElementById('hora');
    situacion = document.getElementById('situacion');

    if (activo.value !== "" && falla.value !== "" && fecha.value !== "" && hora.value !== "" && situacion.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar_falla");
        http.append("codigo", codigo.value);
        http.append("activo", activo.value);
        http.append("falla", falla.value);
        http.append("fecha", fecha.value);
        http.append("hora", hora.value);
        http.append("situacion", situacion.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_activo.php");
        request.send(http);
        request.onreadystatechange = function () {
            //console.log(request);
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
                    window.location.reload();
                });
            }
        };
    } else {
        if (falla.value === "") {
            falla.classList.add("is-invalid");
        } else {
            falla.classList.remove("is-invalid");
        }
        if (fecha.value === "") {
            fecha.classList.add("is-invalid");
        } else {
            fecha.classList.remove("is-invalid");
        }
        if (hora.value === "") {
            hora.classList.add("is-invalid");
        } else {
            hora.classList.remove("is-invalid");
        }
        if (situacion.value === "") {
            situacion.parentNode.classList.add('has-error');
        } else {
            situacion.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function SolucionarFalla() {
    codigo = document.getElementById('codigoFalla');
    activo = document.getElementById('activo');
    fecha = document.getElementById('fecha');
    hora = document.getElementById('hora');
    comentario = document.getElementById('comentario');
    if (activo.value !== "" && codigo.value !== "" && fecha.value !== "" && hora.value !== "") {
        swal({
            title: "\u00BFEsta Solucionado?",
            text: "\u00BFEsta seguro(a) de reportar la soluci\u00F3n de esta falla?",
            icon: "warning",
            content: "textarea",
            buttons: {
                cancel: "Cancelar",
                ok: {text: "Aceptar", value: true, }
            }
        }).then((value) => {
            switch (value) {
                case true:
                    /////////// POST /////////
                    var boton = document.getElementById("btn-grabar");
                    loadingBtn(boton);
                    var http = new FormData();
                    http.append("request", "solucionar_falla");
                    http.append("codigo", codigo.value);
                    http.append("activo", activo.value);
                    http.append("fecha", fecha.value);
                    http.append("hora", hora.value);
                    http.append("comentario", comentario.value);
                    var request = new XMLHttpRequest();
                    request.open("POST", "ajax_fns_activo.php");
                    request.send(http);
                    request.onreadystatechange = function () {
                        //console.log(request);
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
                                window.location.reload();
                            });
                        }
                    };
                    break;
                default:
                    return;
            }
        });
    } else {
        if (falla.value === "") {
            falla.classList.add("is-invalid");
        } else {
            falla.classList.remove("is-invalid");
        }
        if (fecha.value === "") {
            fecha.classList.add("is-invalid");
        } else {
            fecha.classList.remove("is-invalid");
        }
        if (hora.value === "") {
            hora.classList.add("is-invalid");
        } else {
            hora.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}
