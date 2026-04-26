$(document).ready(function(){
    $(".select2").select2();
        
    $('.input-group.date').datepicker({
        format: 'dd/mm/yyyy',
        keyboardNavigation: false,
        forceParse: false,
        calendarWeeks: true,
        autoclose: true
    });
    
    $('.timepicker').datetimepicker({
        //          format: 'H:mm',    // use this format if you want the 24hours timepicker
        format: 'H:mm', //use this format if you want the 12hours timpiecker with AM/PM toggle
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-chevron-up",
            down: "fa fa-chevron-down",
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-screenshot',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        }
    });
    
    progressBar();
    graficasCumplimiento();
    graficasLectura();
});


function Submit(){
    progressBar();
    graficasCumplimiento();
    graficasLectura();
};

function detalle(codigo) {
    cerrar();
    //Realiza una peticion de contenido a la contenido.php
    $.post("../promts/indicadores/detalle.php", { codigo: codigo }, function (data) {
        // Ponemos la respuesta de nuestro script en el DIV recargado
        $("#Pcontainer").html(data);
    });
    abrirModal();
}

function progressBar(){
    /////////// POST /////////
    var http = new FormData();
    http.append("request","barra_usuario");
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            //console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                console.log( "Error: ", resultado.message, ';', request.responseText );
                console.log( request.responseText );
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

function graficasCumplimiento(){
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
    http.append("request","cumplimiento_usuario");
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            // console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                pieContainer.innerHTML = '...';
                stocked2Container.innerHTML = '...';
                console.log( "Error: ", resultado.message, ';', request.responseText );
                console.log( request.responseText );
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
            c3.generate({
                bindto: '#stocked1',
                data:{
                    columns: [
                        dataResultado.ejecutado,
                        dataResultado.pendiente,
                        dataResultado.vencido
                    ],
                    colors:{
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
                        categories: dataResultado.proceso
                    }
                }
            });

            ////////////////// GRAFICA 2 ///////////////////////
            c3.generate({
                bindto: '#stocked2',
                data:{
                    columns: [
                        dataResultado.ejecutado2,
                        dataResultado.pendiente2,
                        dataResultado.vencido2
                    ],
                    colors:{
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
                data:{
                    columns: [
                        dataResultado.ejecutado3,
                        dataResultado.pendiente3,
                        dataResultado.vencido3
                    ],
                    colors:{
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
                data:{
                    columns: [
                        dataResultado.ejecutado,
                        dataResultado.pendiente,
                        dataResultado.vencido
                    ],
                    colors:{
                        Ejecutado: '#1D9619',
                        Pendiente: '#fbc658',
                        Vencido: '#A80000'
                    },
                    type : 'pie'
                }
            });
        }
    }; 
}

function graficasLectura(){
    //--
    pieContainer2 = document.getElementById("pieContainer2");
    stocked3Container = document.getElementById("stocked3Container");
    stocked4Container = document.getElementById("stocked4Container");
    stocked5Container = document.getElementById("stocked5Container");
    loadingCogs(pieContainer2);
    loadingCogs(stocked3Container);
    loadingCogs(stocked4Container);
    loadingCogs(stocked5Container);
    /////////// POST /////////
    var http = new FormData();
    http.append("request","lecturas_usuario");
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_indicador.php");
    request.send(http);
    request.onreadystatechange = function(){
        //console.log( request );
        if(request.readyState != 4) return;
        if(request.status === 200){
            // console.log( request.responseText );
            resultado = JSON.parse(request.responseText);
            if(resultado.status !== true){
                pieContainer2.innerHTML = '...';
                stocked3Container.innerHTML = '...';
                console.log( "Error: ", resultado.message, ';', request.responseText );
                console.log( request.responseText );
                return;
            }
                                    
            //data
            let dataResultado = resultado.data;
            //console.log( resultado.parametros );
            // console.log( dataResultado );
            pieContainer2.innerHTML = '';
            stocked3Container.innerHTML = '';
            stocked4Container.innerHTML = '';
            stocked5Container.innerHTML = '';
            var stockedPie2 = document.createElement("div");
            stockedPie2.setAttribute("id", "pie2");
            pieContainer2.appendChild(stockedPie2);
            var stocked3 = document.createElement("div");
            stocked3.setAttribute("id", "stocked3");
            stocked3Container.appendChild(stocked3);
            var stocked4 = document.createElement("div");
            stocked4.setAttribute("id", "stocked4");
            stocked4Container.appendChild(stocked4);
            var stocked5 = document.createElement("div");
            stocked5.setAttribute("id", "stocked5");
            stocked5Container.appendChild(stocked5);
            
            //console.log( dataResultado.categorias );
            ////////////////// GRAFICA 3 ///////////////////////
            c3.generate({
                bindto: '#stocked3',
                data:{
                    columns: [
                        dataResultado.bajo2,
                        dataResultado.media2,
                        dataResultado.alto2
                    ],
                    colors:{
                        "Bajo el Minimo": '#808080',
                        "En Rango": '#1D9619',
                        "Sobre el Maximo": '#A80000'
                    },
                    type: 'bar',
                    groups: [
                        ["Bajo el Minimo", "En Rango", "Sobre el Maximo"]
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
            ////////////////// GRAFICA 4 ///////////////////////
            c3.generate({
                bindto: '#stocked4',
                data:{
                    columns: [
                        dataResultado.bajo,
                        dataResultado.media,
                        dataResultado.alto
                    ],
                    colors:{
                        "Bajo el Minimo": '#808080',
                        "En Rango": '#1D9619',
                        "Sobre el Maximo": '#A80000'
                    },
                    type: 'bar',
                    groups: [
                        ["Bajo el Minimo", "En Rango", "Sobre el Maximo"]
                    ]
                },
                axis: {
                    rotated: true,
                    x: {
                        type: 'category',
                        categories: dataResultado.proceso
                    }
                }
            });
            ////////////////// GRAFICA 5 ///////////////////////
            c3.generate({
                bindto: '#stocked5',
                data:{
                    columns: [
                        dataResultado.bajo3,
                        dataResultado.media3,
                        dataResultado.alto3
                    ],
                    colors:{
                        "Bajo el Minimo": '#808080',
                        "En Rango": '#1D9619',
                        "Sobre el Maximo": '#A80000'
                    },
                    type: 'bar',
                    groups: [
                        ["Bajo el Minimo", "En Rango", "Sobre el Maximo"]
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
            ////////////////// GRAFICA PIE 2 ///////////////////////
            c3.generate({
                bindto: '#pie2',
                data:{
                    columns: [
                        dataResultado.bajo,
                        dataResultado.media,
                        dataResultado.alto
                    ],
                    colors:{
                        "Bajo el Minimo": '#808080',
                        "En Rango": '#1D9619',
                        "Sobre el Maximo": '#A80000'
                    },
                    type : 'pie'
                }
            });
        }
    }; 
}