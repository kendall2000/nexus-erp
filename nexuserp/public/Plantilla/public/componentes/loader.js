// loader.js
window.showLoader = function () {
  document.body.classList.add('is-loading');
};

window.hideLoader = function () {
  document.body.classList.remove('is-loading');
};
