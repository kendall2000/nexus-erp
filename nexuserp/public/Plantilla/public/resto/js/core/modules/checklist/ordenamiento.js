window.addEventListener('load',()=>{
      
      adjustList();
      $(function () {
        $("#preguntas").sortable({update: function () {
                mensaje = document.getElementById('mensaje');
                var http = new FormData();
                var order = [];
                $('#preguntas').children('li').each(function(idx, elm) {
                  order.push(elm.id.split('_')[1])
                }); 
                http.append("request", "update_ordenamiento");
                http.append("orden", order);
                var request = new XMLHttpRequest();
                request.open("POST", "ordenamiento.php");
                request.send(http);
                request.onreadystatechange = function () {
                if (request.readyState != 4) return;
                if (request.status === 200) {
                  //console.log(request.responseText);
                  resultado = JSON.parse(request.responseText);
                  if (resultado.status !== true) {
                    //contenedor.innerHTML = '...';
                    return;
                  }
                  mensaje = document.getElementById('mensaje');
                  var result = resultado.message;
                  mensaje.innerHTML = result;
                  $("#mensaje").slideDown('slow');
                  adjustList();
                  RetirarMensaje();
                }
              };
            }
        });
      });


      
      function RetirarMensaje(){
         setTimeout( function (){
           $("#mensaje").slideUp('slow', function(){});
         }, 1900);
      }

      function adjustList(){
        pregunta = document.getElementsByClassName('pregunta');
        no_pregunta = document.getElementsByClassName('no_pregunta');
        size = pregunta.length;
        var heights = [];
        for(var i = 0; i < size; i++){
          heights.push(pregunta[i].offsetHeight);
        }
        //console.log(heights);
        for(var j = 0; j < size; j++){
          no_pregunta[j].style.height = heights[j] + 'px' ;
        }
      }
});




