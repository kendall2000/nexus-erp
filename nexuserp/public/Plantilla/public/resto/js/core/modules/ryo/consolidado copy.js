$(document).ready(function () {
    progressBar();
    graficasCumplimiento();
    setTimeout(function () {
        $('.dataTables-example').DataTable({
            pageLength: 25,
            responsive: true,
            dom: '<"html5buttons"B>lTfgitp',
            buttons: [
    
            ]
        });
    }, 500);
});

function Submit() {
    progressBar();
    graficasCumplimiento();
};

function detalle(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/ryo/detalle.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function progressBar() {
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "barra");
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_dashboard.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                console.log("Error: ", resultado.message, ';', request.responseText);
                console.log(request.responseText);
                return;
            }
            //data
            let data = resultado.data;
            //console.log( resultado.parametros );
            //console.log( data );
            ////////////////// PROGRESS BAR ///////////////////////
            document.getElementById("progressEjecutado").style.width = data.porcentEjecutado + "%";
            document.getElementById("progressPendiente").style.width = data.porcentPendiente + "%";
            document.getElementById("progressVencido").style.width = data.porcentVencido + "%";
            //--
            document.getElementById("spanEjecutado").innerHTML = data.porcentEjecutado + "%";
            document.getElementById("spanPendiente").innerHTML = data.porcentPendiente + "%";
            document.getElementById("spanVencido").innerHTML = data.porcentVencido + "%";
            //--
            document.getElementById("spanEjecutado").setAttribute("title", data.porcentEjecutado + "% Ejecutado hoy al momento");
            document.getElementById("spanPendiente").setAttribute("title", data.porcentPendiente + "% Pendiente hoy al momento");
            document.getElementById("spanVencido").setAttribute("title", data.porcentVencido + "% Vencido hoy al momento");
        }
    };
}

function graficasCumplimiento() {
    //--
    pieContainer = document.getElementById("pieContainer");
    stocked0Container = document.getElementById("stocked0Container");
    stocked1Container = document.getElementById("stocked1Container");
    stocked2Container = document.getElementById("stocked2Container");
    loadingCogs(pieContainer);
    loadingCogs(stocked0Container);
    loadingCogs(stocked1Container);
    loadingCogs(stocked2Container);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "cumplimiento");
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_dashboard.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            // console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                pieContainer.innerHTML = '...';
                stocked2Container.innerHTML = '...';
                console.log("Error: ", resultado.message, ';', request.responseText);
                console.log(request.responseText);
                return;
            }

            //data
            let dataResultado = resultado.data;
            //console.log( resultado.parametros );
            // console.log( dataResultado );
            pieContainer.innerHTML = '';
            stocked0Container.innerHTML = '';
            stocked1Container.innerHTML = '';
            stocked2Container.innerHTML = '';
            var stockedPie = document.createElement("div");
            stockedPie.setAttribute("id", "pie");
            pieContainer.appendChild(stockedPie);
            var stocked0 = document.createElement("div");
            stocked0.setAttribute("id", "stocked0");
            stocked0Container.appendChild(stocked0);
            var stocked1 = document.createElement("div");
            stocked1.setAttribute("id", "stocked1");
            stocked1Container.appendChild(stocked1);
            var stocked2 = document.createElement("div");
            stocked2.setAttribute("id", "stocked2");
            stocked2Container.appendChild(stocked2);
            //console.log( dataResultado.categorias );
            ////////////////// GRAFICA 1 ///////////////////////
            stocked1Container.innerHTML = dataResultado.tabla;

            ////////////////// GRAFICA 2 ///////////////////////
            c3.generate({
                bindto: '#stocked2',
                data: {
                    columns: [
                        dataResultado.ejecutado2,
                        dataResultado.pendiente2,
                        dataResultado.vencido2
                    ],
                    colors: {
                        Ejecutado: '#1D9619',
                        Pendiente: '#fbc658',
                        Vencido: '#A80000'
                    },
                    type: 'bar',
                    groups: [
                        ['Ejecutado', 'Pendiente', 'Vencido']
                    ]
                },
                axis: {
                    rotated: true,
                    x: {
                        type: 'category',
                        categories: dataResultado.sistema
                    }
                }
            });
            ////////////////// GRAFICA 0 ///////////////////////
            c3.generate({
                bindto: '#stocked0',
                data: {
                    columns: [
                        dataResultado.ejecutado3,
                        dataResultado.pendiente3,
                        dataResultado.vencido3
                    ],
                    colors: {
                        Ejecutado: '#1D9619',
                        Pendiente: '#fbc658',
                        Vencido: '#A80000'
                    },
                    type: 'bar',
                    groups: [
                        ['Ejecutado', 'Pendiente', 'Vencido']
                    ]
                },
                axis: {
                    rotated: true,
                    x: {
                        type: 'category',
                        categories: dataResultado.tipo
                    }
                }
            });
            ////////////////// GRAFICA PIE ///////////////////////
            c3.generate({
                bindto: '#pie',
                data: {
                    columns: [
                        dataResultado.ejecutado,
                        dataResultado.pendiente,
                        dataResultado.vencido
                    ],
                    colors: {
                        Ejecutado: '#1D9619',
                        Pendiente: '#fbc658',
                        Vencido: '#A80000'
                    },
                    type: 'pie'
                }
            });
        }
    };
}
