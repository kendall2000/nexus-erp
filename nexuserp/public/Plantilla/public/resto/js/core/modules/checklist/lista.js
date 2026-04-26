//funciones javascript y validaciones
$(document).ready(function() {
    $(".select2").select2();
});

function atras() {
    if (document.getElementById('codigo').value != '') window.location.reload();
    else window.history.back();
}

function Limpiar() {
    swal({
        text: "\u00BFDesea Limpiar la p\u00E1gina?, si a\u00FAn no a grabado perdera los datos escritos...",
        icon: "info",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, },
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

function printTable(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        console.log(request);
        if (request.readyState != 4) return;
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
            contenedor.innerHTML = resultado.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}


function seleccionarLista(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            document.getElementById("categoria").value = data.categoria;
            document.getElementById("nombre").value = data.nombre;
            document.getElementById('foto').checked = (data.foto == 1);
            document.getElementById('firma').checked = (data.firma == 1);

            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("nombre").focus();
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };
}

function Submit() {
    myform = document.forms.f1;
    myform.submit();
}

function Grabar() {
    categoria = document.getElementById('categoria');
    nombre = document.getElementById('nombre');
    var foto = (document.getElementById('foto').checked) ? 1 : 0;
    var firma = (document.getElementById('firma').checked) ? 1 : 0;

    if (categoria.value !== "" && nombre.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar");
        http.append("categoria", categoria.value);
        http.append("nombre", nombre.value);
        http.append("foto", foto);
        http.append("firma", firma);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-save"></i> Grabar');
                        nombre.value = "";
                        foto.checked = false;
                        firma.checked = false;
                        setSelect("categoria ", "");
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    preguntas(resultado.codigo);
                });
            }
        };
    } else {
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function Modificar() {
    codigo = document.getElementById('codigo');
    categoria = document.getElementById('categoria');
    nombre = document.getElementById('nombre');
    var foto = (document.getElementById('foto').checked) ? 1 : 0;
    var firma = (document.getElementById('firma').checked) ? 1 : 0;

    if (categoria.value !== "" && nombre.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar");
        http.append("codigo", codigo.value);
        http.append("categoria", categoria.value);
        http.append("nombre", nombre.value);
        http.append("foto", foto);
        http.append("firma", firma);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
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
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function deshabilitarLista(codigo) {
    swal({
        text: "\u00BFDesea quitar a esta check list?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacion(codigo, 0);
                break;
            default:
                return;
        }
    });
}

function cambioSituacion(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
                window.location.reload();
            });
        }
    };
}


////////////////////////// PREGUNTAS ////////////////////////////////////////////

function preguntas(lista) {
    window.location.href = "FRMpreguntas.php?lista=" + lista;
}

function printTablePreguntas(codigo, lista) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_preguntas");
    http.append("codigo", codigo);
    http.append("lista", lista);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            contenedor.innerHTML = resultado.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}


function GrabarPregunta() {
    lista = document.getElementById("lista");
    pregunta = document.getElementById('pregunta');

    if (lista.value !== "" && pregunta.value !== "") {
        //botones
        btngrabar = document.getElementById("btn-grabar");
        btnmodificar = document.getElementById("btn-modificar");
        btnmodificar.className = 'btn btn-primary hidden';
        btngrabar.className = 'btn btn-primary hidden';
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_pregunta");
        http.append("lista", lista.value);
        http.append("pregunta", pregunta.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
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
        if (pregunta.value === "") {
            pregunta.classList.add("is-invalid");
        } else {
            pregunta.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


function seleccionarPregunta(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get_pregunta");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            document.getElementById("pregunta").value = data.pregunta;

            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };
}

function ModificarPregunta() {
    codigo = document.getElementById("codigo");
    lista = document.getElementById("lista");
    pregunta = document.getElementById('pregunta');

    if (codigo.value !== "" && pregunta.value !== "") {
        //botones
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar_pregunta");
        http.append("codigo", codigo.value);
        http.append("lista", lista.value);
        http.append("pregunta", pregunta.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
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
        if (pregunta.value === "") {
            pregunta.classList.add("is-invalid");
        } else {
            pregunta.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function deshabilitarPregunta(codigo) {
    swal({
        text: "\u00BFDesea quitar a esta pregunta del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacionPregunta(codigo, 0);
                break;
            default:
                return;
        }
    });
}

function cambioSituacionPregunta(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_pregunta");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
                window.location.reload();
            });
        }
    };
}
////////////////////////// HORARIOS ////////////////////////////////////////////

function horarios(lista) {
    window.location.href = "FRMhorarios.php?lista=" + lista;
}

function printTableProgramacion(codigo, lista) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_programacion");
    http.append("codigo", codigo);
    http.append("lista", lista);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado );
                contenedor.innerHTML = '...';
                //console.log( resultado.message );
                return;
            }
            //tabla
            contenedor.innerHTML = resultado.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}

function setArea(area) {
    if (area != "") {
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "get_area");
        http.append("area", area);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log( request );
            if (request.readyState != 4) return;
            if (request.status === 200) {
                console.log(request.responseText);
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //swal("Informaci\u00F3n", resultado.message, "info");
                    return;
                }
                var data = resultado.data;
                //console.log( data );
                //set
                document.getElementById("sede").value = data.sede;
                document.getElementById("sector").value = data.sector;
                document.getElementById("secNombre").value = data.secNombre;
                document.getElementById("nivel").value = data.nivel;
                //--
                $(".select2").select2();
            }
        };
    } else {
        //set
        document.getElementById("sede").value = '';
        document.getElementById("sector").value = '';
        document.getElementById("secNombre").value = '';
        document.getElementById("nivel").value = '';
    }
}

function tipoProgramacion(tipo) {
    contenedorSemana = document.getElementById('containerSemana');
    contenedorMes = document.getElementById('containerMes');
    contenedorUnico = document.getElementById('containerUnico');
    if (tipo === 'M') {
        contenedorSemana.style.display = 'none';
        contenedorMes.style.display = 'block';
        contenedorUnico.style.display = 'none';
    } else if (tipo === 'S') {
        contenedorSemana.style.display = 'block';
        contenedorMes.style.display = 'none';
        contenedorUnico.style.display = 'none';
    } else if (tipo === 'U') {
        contenedorSemana.style.display = 'none';
        contenedorMes.style.display = 'none';
        contenedorUnico.style.display = 'block';
    }

    document.getElementById('diaL').className = 'btn btn-white';
    document.getElementById('diaM').className = 'btn btn-white';
    document.getElementById('diaW').className = 'btn btn-white';
    document.getElementById('diaJ').className = 'btn btn-white';
    document.getElementById('diaV').className = 'btn btn-white';
    document.getElementById('diaS').className = 'btn btn-white';
    document.getElementById('diaD').className = 'btn btn-white';
    for (var i = 1; i <= 31; i++) {
        document.getElementById('dia' + i).className = 'btn btn-white';
    }
}

function GrabarProgramacion() {
    lista = document.getElementById("lista");
    sede = document.getElementById('sede');
    sector = document.getElementById("sector");
    area = document.getElementById("area");
    hini = document.getElementById('hini');
    hfin = document.getElementById('hfin');
    observacion = document.getElementById('observacion');
    tipo = document.getElementById("tipo");
    //--
    secNombre = document.getElementById("secNombre");
    nivel = document.getElementById("nivel");
    // Devuelve 1 si esta activo de otra forma un 0
    dia1 = document.getElementById('diaL').classList.contains('active');
    dia2 = document.getElementById('diaM').classList.contains('active');
    dia3 = document.getElementById('diaW').classList.contains('active');
    dia4 = document.getElementById('diaJ').classList.contains('active');
    dia5 = document.getElementById('diaV').classList.contains('active');
    dia6 = document.getElementById('diaS').classList.contains('active');
    dia7 = document.getElementById('diaD').classList.contains('active');
    // Devuelve la fecha si esta activo
    fechaUnica = document.getElementById('fecha');
    // Busca el dia del mes activo
    for (var diaMes = 1; diaMes <= 31; diaMes++)
        if (document.getElementById('dia' + diaMes).classList.contains('active')) break;
    if (diaMes == 32) diaMes = 0;
    if ((!(tipo.value == 'S' && dia1 == 0 && dia2 == 0 && dia3 == 0 && dia4 == 0 && dia5 == 0 && dia6 == 0 && dia7 == 0)) &&
        (!(tipo.value == 'M' && diaMes == 0)) && (hini.value != "" && hfin.value != "" && area != "" && nivel != "" && sector != "") &&
        (!(tipo.value == 'U' && fechaUnica.value == ""))) {
        //botones
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "grabar_programacion");
        http.append("lista", lista.value);
        http.append("sede", sede.value);
        http.append("sector", sector.value);
        http.append("area", area.value);
        http.append("observacion", observacion.value);
        http.append("hini", hini.value);
        http.append("hfin", hfin.value);
        http.append("tipo", tipo.value);
        http.append("dia1", dia1);
        http.append("dia2", dia2);
        http.append("dia3", dia3);
        http.append("dia4", dia4);
        http.append("dia5", dia5);
        http.append("dia6", dia6);
        http.append("dia7", dia7);
        http.append("diaMes", diaMes);
        if (tipo.value != 'U') {
            http.append('fechaUnica', 0);
        } else {
            http.append('fechaUnica', fechaUnica.value);
        }
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                //resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    console.log(resultado.sql);
                    swal("Error", resultado.message, "error").then((value) => {
                        console.log(value);
                        deloadingBtn(boton, '<i class="fas fa-save"></i> Grabar');
                    });
                    return;
                }
                //alert(fecha);
                swal("Excelente!", "Programacion creada satisfactoriamente!!!", "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        if (hini.value === "") {
            hini.classList.add("is-invalid");
        } else {
            hini.classList.remove("is-invalid");
        }
        if (hfin.value === "") {
            hfin.classList.add("is-invalid");
        } else {
            hfin.classList.remove("is-invalid");
        }
        if (nivel.value === "") {
            nivel.classList.add("is-invalid");
        } else {
            nivel.classList.remove("is-invalid");
        }
        if (sector.value === "") {
            secNombre.classList.add("is-invalid");
        } else {
            secNombre.classList.remove("is-invalid");
        }
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
        } else {
            area.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


function ModificarProgramacion() {
    codigo = document.getElementById("codigo");
    lista = document.getElementById("lista");
    sede = document.getElementById('sede');
    sector = document.getElementById("sector");
    area = document.getElementById("area");
    hini = document.getElementById('hini');
    hfin = document.getElementById('hfin');
    tipo = document.getElementById("tipo");
    observacion = document.getElementById('observacion');
    //--
    secNombre = document.getElementById("secNombre");
    nivel = document.getElementById("nivel");

    // Devuelve 1 si esta activo de otra forma un 0
    dia1 = document.getElementById('diaL').classList.contains('active');
    dia2 = document.getElementById('diaM').classList.contains('active');
    dia3 = document.getElementById('diaW').classList.contains('active');
    dia4 = document.getElementById('diaJ').classList.contains('active');
    dia5 = document.getElementById('diaV').classList.contains('active');
    dia6 = document.getElementById('diaS').classList.contains('active');
    dia7 = document.getElementById('diaD').classList.contains('active');
    //fecha unica
    fechaUnica = document.getElementById('fecha');
    // Busca el dia del mes activo
    for (var diaMes = 1; diaMes <= 31; diaMes++)
        if (document.getElementById('dia' + diaMes).classList.contains('active')) break;
    if (diaMes == 32) diaMes = 0;

    if ((!(tipo.value == 'S' && dia1 == 0 && dia2 == 0 && dia3 == 0 && dia4 == 0 && dia5 == 0 && dia6 == 0 && dia7 == 0)) &&
        (!(tipo.value == 'M' && diaMes == 0)) && (hini.value != "" && hfin.value != "" && area != "" && nivel != "" && sector != "") &&
        (!(tipo.value == 'U' && fechaUnica.value == ""))) {
        //botones
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        /////////// POST /////////
        var http = new FormData();
        http.append("request", "modificar_programacion");
        http.append("codigo", codigo.value);
        http.append("lista", lista.value);
        http.append("sede", sede.value);
        http.append("sector", sector.value);
        http.append("area", area.value);
        http.append("observacion", observacion.value);
        http.append("hini", hini.value);
        http.append("hfin", hfin.value);
        http.append("tipo", tipo.value);
        http.append("dia1", dia1);
        http.append("dia2", dia2);
        http.append("dia3", dia3);
        http.append("dia4", dia4);
        http.append("dia5", dia5);
        http.append("dia6", dia6);
        http.append("dia7", dia7);
        http.append("diaMes", diaMes);
        if (tipo.value != 'U') {
            http.append('fechaUnica', 0);
        } else {
            http.append('fechaUnica', fechaUnica.value);
        }

        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            console.log(request);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    //console.log( resultado.sql );
                    swal("Error", resultado.message, "error").then((value) => {
                        console.log(value);
                        deloadingBtn(boton, '<i class="fas fa-save"></i> Grabar');
                    });
                    return;
                }
                swal("Excelente!", "Programacion modificada satisfactoriamente!!!", "success").then((value) => {
                    window.location.reload();
                });
            }
        };
    } else {
        if (hini.value === "") {
            hini.classList.add("is-invalid");
        } else {
            hini.classList.remove("is-invalid");
        }
        if (hfin.value === "") {
            hfin.classList.add("is-invalid");
        } else {
            hfin.classList.remove("is-invalid");
        }
        if (nivel.value === "") {
            nivel.classList.add("is-invalid");
        } else {
            nivel.classList.remove("is-invalid");
        }
        if (sector.value === "") {
            secNombre.classList.add("is-invalid");
        } else {
            secNombre.classList.remove("is-invalid");
        }
        if (area.value === "") {
            area.parentNode.classList.add('has-error');
        } else {
            area.parentNode.classList.remove('has-error');
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function seleccionarProgramacion(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get_programacion");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            console.log(request.responseText);
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
            document.getElementById("sector").value = data.sector;
            document.getElementById("secNombre").value = data.sector_nombre;
            document.getElementById("nivel").value = data.nivel;
            document.getElementById("area").value = data.area;
            document.getElementById("tipo").value = data.tipo;
            document.getElementById("hini").value = data.hini;
            document.getElementById("hfin").value = data.hfin;
            document.getElementById("observacion").value = data.observacion;
            document.getElementById("fecha").value = data.fechaUnica;
            // Devuelve 1 si esta activo de otra forma un 0
            tipoProgramacion(data.tipo);
            if (data.dia1 == '1') document.getElementById('diaL').classList.add('active');
            if (data.dia2 == '1') document.getElementById('diaM').classList.add('active');
            if (data.dia3 == '1') document.getElementById('diaW').classList.add('active');
            if (data.dia4 == '1') document.getElementById('diaJ').classList.add('active');
            if (data.dia5 == '1') document.getElementById('diaV').classList.add('active');
            if (data.dia6 == '1') document.getElementById('diaS').classList.add('active');
            if (data.dia7 == '1') document.getElementById('diaD').classList.add('active');
            if (data.diaMes != '0') document.getElementById('dia' + data.diaMes).classList.add('active');

            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };
}

function deshabilitarProgramacion(codigo) {
    swal({
        text: "\u00BFDesea quitar a esta programacion?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacionProgramacion(codigo, 0);
                break;
            default:
                return;
        }
    });
}

function cambioSituacionProgramacion(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_programacion");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
                window.location.reload();
            });
        }
    };
}



function printTableChkPreguntas(codigo) {
    contenedor = document.getElementById("result");
    category = document.getElementById("categoria");
    // divsector = document.getElementById('divsector');
    // if(divsector.value !== "" &&)
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_chk_preguntas");
    // http.append("divsector", divsector.value);
    http.append("codigo", codigo);
    http.append("categoria", category.value);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            contenedor.innerHTML = resultado.tabla;
            // divsector.innerHTML = divsector.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}





function copy_checklist(codigo) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                return;
            }
            var data = resultado.data;
            document.getElementById("codigo").value = data.codigo;
            document.getElementById("categoria").value = data.categoria;
            document.getElementById("nombre").value = data.nombre;
            document.getElementById('foto').checked = (data.foto == 1);
            document.getElementById('firma').checked = (data.firma == 1);
            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("nombre").focus();
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-copy").className = "btn btn-primary btn-sm";

            //--
        }
    };
}


function copiar() {
    codigo = document.getElementById("codigo");
    categoria = document.getElementById('categoria');
    nombre = document.getElementById('nombre');
    var foto = (document.getElementById('foto').checked) ? 1 : 0;
    var firma = (document.getElementById('firma').checked) ? 1 : 0;

    if (categoria.value !== "" && nombre.value !== "") {
        /////////// POST /////////
        var boton = document.getElementById("btn-copy");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "copiar_checklist");
        http.append("codigo", codigo.value);
        http.append("categoria", categoria.value);
        http.append("nombre", nombre.value);
        http.append("foto", foto);
        http.append("firma", firma);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            console.log(request.responseText);
            if (request.readyState != 4) return;
            if (request.status === 200) {
                resultado = JSON.parse(request.responseText);
                if (resultado.status !== true) {
                    swal("Error", resultado.message, "error").then((value) => {
                        deloadingBtn(boton, '<i class="fa fa-copy"></i> Copiar');
                        //nombre.value = "";
                        foto.checked = false;
                        firma.checked = false;
                        setSelect("categoria ", "");
                    });
                    return;
                }
                //console.log( resultado );
                swal("Excelente!", resultado.message, "success").then((value) => {
                    preguntas(resultado.codigo);
                });
            }
        };
    } else {
        if (categoria.value === "") {
            categoria.parentNode.classList.add('has-error');
        } else {
            categoria.parentNode.classList.remove('has-error');
        }
        if (nombre.value === "") {
            nombre.classList.add("is-invalid");
        } else {
            nombre.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}
//--OA DATA Campos para llenar en la ejecución tipo 1 = F/V,  tipo 2 = texto,  tipo 3 = numérico,

function data(lista) {
    window.location.href = "FRMdata.php?lista=" + lista;
}

function printTablePreguntas(codigo, lista) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "tabla_preguntas");
    http.append("codigo", codigo);
    http.append("lista", lista);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            contenedor.innerHTML = resultado.tabla;
            $('.dataTables-example').DataTable({
                pageLength: 50,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [

                ]
            });
        }
    };
}


function GrabarData() {
    lista = document.getElementById("lista");
    pregunta = document.getElementById('pregunta');

    if (lista.value !== "" && pregunta.value !== "") {
        //botones
        btngrabar = document.getElementById("btn-grabar");
        btnmodificar = document.getElementById("btn-modificar");
        btnmodificar.className = 'btn btn-primary hidden';
        btngrabar.className = 'btn btn-primary hidden';
        /////////// POST /////////
        var boton = document.getElementById("btn-grabar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "grabar_pregunta");
        http.append("lista", lista.value);
        http.append("pregunta", pregunta.value);
        http.append("tipo", 2);
        var request = new XMLHttpRequest();
        request.open("POST", "../ajax_fns_data.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
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
        if (pregunta.value === "") {
            pregunta.classList.add("is-invalid");
        } else {
            pregunta.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}


function seleccionarData(codigo) {
    contenedor = document.getElementById("result");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "get_pregunta");
    http.append("codigo", codigo);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
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
            document.getElementById("pregunta").value = data.pregunta;

            //tabla
            var tabla = resultado.tabla;
            contenedor.innerHTML = tabla;
            $('#tabla').DataTable({
                pageLength: 50,
                responsive: true
            });
            $(".select2").select2();
            //botones
            document.getElementById("btn-grabar").className = "btn btn-primary btn-sm hidden";
            document.getElementById("btn-modificar").className = "btn btn-primary btn-sm";
            //--
        }
    };
}

function ModificarData() {
    codigo = document.getElementById("codigo");
    lista = document.getElementById("lista");
    pregunta = document.getElementById('pregunta');

    if (codigo.value !== "" && pregunta.value !== "") {
        //botones
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        /////////// POST /////////
        var boton = document.getElementById("btn-modificar");
        loadingBtn(boton);
        var http = new FormData();
        http.append("request", "modificar_pregunta");
        http.append("codigo", codigo.value);
        http.append("lista", lista.value);
        http.append("pregunta", pregunta.value);
        var request = new XMLHttpRequest();
        request.open("POST", "ajax_fns_lista.php");
        request.send(http);
        request.onreadystatechange = function() {
            //console.log(request);
            if (request.readyState != 4) return;
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
        if (pregunta.value === "") {
            pregunta.classList.add("is-invalid");
        } else {
            pregunta.classList.remove("is-invalid");
        }
        swal("Ohoo!", "Debe llenar los Campos Obligatorios...", "error");
    }
}

function deshabilitarData(codigo) {
    swal({
        text: "\u00BFDesea quitar a esta pregunta del listado?, no prodr\u00E1 ser usada despu\u00E9s...",
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            ok: { text: "Aceptar", value: true, },
        }
    }).then((value) => {
        switch (value) {
            case true:
                cambioSituacionPregunta(codigo, 0);
                break;
            default:
                return;
        }
    });
}

function cambioSituacionData(codigo, situacion) {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "situacion_pregunta");
    http.append("codigo", codigo);
    http.append("situacion", situacion);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_lista.php");
    request.send(http);
    request.onreadystatechange = function() {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                //console.log( resultado.sql );
                //swal("Informaci\u00F3n", resultado.message, "info");
                return;
            }
            swal("Excelente!", "Registro eliminado satisfactorio!!!", "success").then((value) => {
                window.location.reload();
            });
        }
    };
}

//--OA