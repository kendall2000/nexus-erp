$(document).ready(function(){
	var $image = $(".image-crop > img");
	$($image).cropper({
		aspectRatio: 1.1,
		preview: ".img-preview",
		done: function(data) {
			console.log(data);
		}
	});
	
	var $inputImage = $("#inputImage");
	if (window.FileReader) {
		$inputImage.change(function() {
			var fileReader = new FileReader(),
			files = this.files,
			file;

			if (!files.length) {
				return;
			}
			
			file = files[0];

			if (/^image\/\w+$/.test(file.type)) {
				fileReader.readAsDataURL(file);
				fileReader.onload = function () {
					$inputImage.val("");
					$image.cropper("reset", true).cropper("replace", this.result);
				};
			} else {
				showMessage("Please choose an image file.");
			}
		});
	} else {
		$inputImage.addClass("hide");
	}
	
	$("#download").click(function() {
		codigo = document.getElementById("codigo");
                temp = document.getElementById("temp");
		var dataurl = $image.cropper("getDataURL");
		var blob = dataURLtoBlob(dataurl);
		var f1 = new FormData();
		f1.append("codigo", codigo.value);
		f1.append("imagen", blob);
                
		var request = new XMLHttpRequest();
		request.open("POST", "EXEeditfoto.php");
		request.send(f1);
		request.onreadystatechange = function(){
			if(request.readyState != 4) return;
			//alert(request.status);
			if(request.status === 200){
				resultado = JSON.parse(request.responseText); 
				console.log(resultado);
				if(resultado.status !== true){
					swal("Error en la carga", resultado.message, "error");
					return;
				}
				swal("Excelente!", resultado.message, "success").then((value) => {
					window.location.href = "FRMperfil.php";
				});
			}else{
				//alert("Error: " + request.status + " " + request.responseText);
				swal("Error en la carga", "Error en la carga de la imagen", "error");
				return;
			}
		};
	});
	
	function dataURLtoBlob(dataurl) {
		var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
		bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
		while(n--){
			u8arr[n] = bstr.charCodeAt(n);
		}
		return new Blob([u8arr], {type:mime});
	}
	
	$("#zoomIn").click(function() {
		$image.cropper("zoom", 0.1);
	});
	
	$("#zoomOut").click(function() {
		$image.cropper("zoom", -0.1);
	});
	
	$("#rotateLeft").click(function() {
		$image.cropper("rotate", 45);
	});
	
	$("#rotateRight").click(function() {
		$image.cropper("rotate", -45);
	});
	
	$("#setDrag").click(function() {
		$image.cropper("setDragMode", "crop");
	});
});	