
function showLoader(message = 'Loading...') {
  const overlay = document.getElementById('globalLoader');
  const text = document.getElementById('loaderText');
  if (overlay) {
    text.textContent = message;
    overlay.classList.add('active');
  }
}


function hideLoader() {
  const overlay = document.getElementById('globalLoader');
  if (overlay) overlay.classList.remove('active');
}


window.addEventListener('beforeunload', () => {
  showLoader('Processing...');
});


window.addEventListener('load', () => {
  setTimeout(hideLoader, 400);
});
