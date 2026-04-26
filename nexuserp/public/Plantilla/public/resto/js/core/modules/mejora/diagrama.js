function diagrama(plan) {
    contenedor = document.getElementById("cy");
    loadingCogs(contenedor);
    /////////// POST /////////
    var http = new FormData();
    http.append("request", "diagrama");
    http.append("plan", plan);
    var request = new XMLHttpRequest();
    request.open("POST", "ajax_fns_analisis.php");
    request.send(http);
    request.onreadystatechange = function () {
        //console.log( request );
        if (request.readyState != 4) return;
        if (request.status === 200) {
            resultado = JSON.parse(request.responseText);
            if (resultado.status !== true) {
                contenedor.innerHTML = '...';
                console.log("Error: ", resultado.message, ';', request.responseText);
                console.log(request.responseText);
                return;
            }
            //data
            let data = resultado.data;
            // console.log("data", data);
            // return;
            elementos = [];
            count = 1, x = 50;
            // Rama Principal
            let main = {
                "group": 'nodes',
                "data": {
                    "id": "n" + count,
                },
                position: { // the model position of the node (optional on init, mandatory after)
                    "x": x,
                    "y": 50
                }
            }
            x += 20;
            elementos.push(main);
            pointer = count;
            count++;
            for (element of data) {
                // console.log("element", element);
                if (element["pertenece"] == 0) {
                    // Rama Principal
                    main = {
                        "group": 'nodes',
                        "data": {
                            "id": "n" + count,
                        }, position: { // the model position of the node (optional on init, mandatory after)
                            "x": x,
                            "y": 50
                        }
                    }
                    x += 20;
                    elementos.push(main);
                    line = {
                        "group": "edges",
                        "data": {
                            "source": "n" + pointer,
                            "target": "n" + count,
                            "label": ""
                        }
                    }
                    elementos.push(line);
                    pointer = count;


                    let item = {
                        "group": 'nodes',
                        "data": {
                            "id": element["codigo"],
                        }
                    }
                    elementos.push(item);

                    for (element2 of data) {
                        if (element2["pertenece"] == element["codigo"]) {
                            let main = {
                                "group": 'nodes',
                                "data": {
                                    "id": "n" + (count + 1),
                                }
                            }
                            elementos.push(main);
                            line = {
                                "group": "edges",
                                "data": {
                                    "source": "n" + count,
                                    "target": "n" + (count + 1),
                                    "label": ""
                                }
                            }
                            elementos.push(line);

                            let item = {
                                "group": 'nodes',
                                "data": {
                                    "id": element2["codigo"],
                                }
                            }
                            elementos.push(item);
                            line = {
                                "group": "edges",
                                "data": {
                                    "source": "n" + (count + 1),
                                    "label": element2["descripcion"],
                                    "target": element2["codigo"]
                                }
                            }
                            elementos.push(line);
                            count++;
                        }
                    }

                    line = {
                        "group": "edges",
                        "data": {
                            "source": "n" + count,
                            "label": element["descripcion"],
                            "target": element["codigo"]
                        }
                    }
                    elementos.push(line);
                    count++;
                }
            }
            // Rama Principal
            main = {
                "group": 'nodes',
                "data": {
                    "id": "n" + count,
                }, position: { // the model position of the node (optional on init, mandatory after)
                    "x": x,
                    "y": 50
                }
            }
            x += 20;
            elementos.push(main);
            line = {
                "group": "edges",
                "data": {
                    "source": "n" + pointer,
                    "target": "n" + count,
                    "label": ""
                }
            }
            elementos.push(line);


            //console.log( resultado.parametros );
            // console.log(elementos);
            contenedor.innerHTML = '';
            let cy = cytoscape({

                container: document.getElementById('cy'), // container to render in

                elements: elementos,

                style: [ // the stylesheet for the graph
                    {
                        selector: 'node',
                        style: {
                            'background-color': '#white',
                            'label': 'data(id)',
                        }
                    },

                    {
                        selector: 'edge',
                        style: {
                            'width': 1,
                            'line-color': '#369',
                            'target-arrow-color': '#369',
                            'target-arrow-shape': 'triangle',
                            'font-size': '14px',
                            'color': '#777'
                        }
                    }
                ],

                style: cytoscape.stylesheet()
                    .selector('edge')
                    .css({
                        'width': 3,
                        'line-color': 'black',
                        'target-arrow-color': 'black',
                        'target-arrow-shape': 'triangle',
                        'label': 'data(label)',
                        'font-size': '25px',
                        'color': 'gray',
                    })
                    .selector('node')
                    .css({
                        'text-valign': 'center',
                        'color': 'black',
                        'text-outline-width': 2,
                        'text-outline-color': 'black',
                        'background-color': 'black',
                        'width': 10,
                        'height': 10
                    })
                    .selector(':selected')
                    .css({
                        'background-color': 'black',
                        'line-color': 'black',
                        'target-arrow-color': 'black',
                        'source-arrow-color': 'black',
                        'text-outline-color': 'black'
                    }),

            });
        }
    };
}
