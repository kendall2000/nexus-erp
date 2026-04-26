function initMap() {
    var input = document.getElementById('clientAddress');
    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.addListener('place_changed', function () {
        var place = autocomplete.getPlace();
        document.getElementById('location-snap').
            innerHTML = place.formatted_address;
        document.getElementById('lat-span').
            innerHTML = place.geometry.location.lat();
        document.getElementById('lon-span').
            innerHTML = place.geometry.location.lng();
    });
}

function mostrarLugar() {
    let item = document.getElementById('direccionEditar')
    if (item) {
        window.open('https://google.cl/maps/place/' + item.value, '_blank');
    }
    return false; //No ejecutar el evento.
}

function iniciarMap(){
    var coord = {lat:-34.5956145 ,lng: -58.4431949};
    var map = new google.maps.Map(document.getElementById('map'),{
      zoom: 10,
      center: coord
    });
    var marker = new google.maps.Marker({
      position: coord,
      map: map
    });
}

let autocomplete;

function initAutocomplete() {
  autocomplete = new google.maps.places.Autocomplete(
    document.getElementById('autocomplete'), {
      types: ['establishment'],
      componentRestrictions: {
        'country': ['GUA']
      },
      fields: ['place_id', 'geometry', 'name']
    });
}