// public/js/login.js
const form = document.getElementById('loginForm');
form.addEventListener('submit', function(e) {
  const user = form.usuario.value.trim();
  const pass = form.contrasena.value.trim();
  if (!user || !pass) {
    e.preventDefault();
    alert('Por favor ingresa usuario y contraseña.');
  }
});