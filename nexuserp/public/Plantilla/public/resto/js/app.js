const splash = document.querySelector('.splash');

document.addEventListener('DOMContentLoaded', (e) => {
  setTimeout(() => {
    splash.classList.add('display-none');
  }, 2000)
})



const header = document.querySeletor('.header');

window.onscroll = function () {
  var top = window.scrollY;
  console.log(top);
  if (top >= 50) {
    header.classList.add('active')
  } else {
    header.classList.remove('active')
  }

}

function show_popup_login(){
	let popup = document.querySelector(".popup-login");
	if(popup.classList.contains('active')){
		popup.classList.remove('active');
	}else{
		popup.classList.add('active');
	}
}
