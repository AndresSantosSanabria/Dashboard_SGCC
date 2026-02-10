/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Entradas de texto
 *  - Version: 4.0.0
 */
window.addEventListener("load", function(event) {
  initInput();
});

function methodAssign(e, f, a) {
  for (let i of a) {
    i.addEventListener(e, f, false);
  }
}

function initInput() {
  var inputDisabled = document.querySelectorAll('input[disabled]');

  for (let d of inputDisabled) {
    var containerDisabled = d.closest('.entradas-de-texto-govco');
    containerDisabled.classList.add('disabled-govco');
  }

  // contador
  var inputContador = document.querySelectorAll('input[typeData="accountant"]');
  methodAssign("keyup", activeInputContador, inputContador);

  // password
  var iconInputPassword = document.querySelectorAll('.icon-entradas-de-texto-govco');
  methodAssign("click", activeIconInputPassword, iconInputPassword);

  var inputContrasenia = document.querySelectorAll('input[typeData="password"]');
  methodAssign("keyup", activeInputContrasenia, inputContrasenia);

  // correo electronico
  var inputCorreo = document.querySelectorAll('input[typeData="mail"]');
  methodAssign("keyup", activeInputCorreo, inputCorreo);

  // teléfono
  var inputTelefono = document.querySelectorAll('input[typeData="phone"]');
  methodAssign("keyup", activeInputTelefono, inputTelefono);
}

// contador
function activeInputContador(element) {
  var parentInputContador = this.parentNode;
  var span = parentInputContador.querySelector(".number-entradas-de-texto-govco");
  span.innerHTML = this.value.length;
}

// password
function activeIconInputPassword(element) {
  var parentPassword = this.parentNode;
  var inputPassword = parentPassword.querySelector('.entradas-de-texto-govco input');
  var visiblePassword = parentPassword.querySelector('.eye-entradas-de-texto-govco');
  var hidePassword = parentPassword.querySelector('.eye-slash-entradas-de-texto-govco');

  if (inputPassword.getAttribute('disabled') === null) {
    if (inputPassword.type == 'password') {
      inputPassword.type = 'text';
      visiblePassword.classList.remove('none');
      hidePassword.classList.add('none');
    } else {
      inputPassword.type = 'password';
      hidePassword.classList.remove('none');
      visiblePassword.classList.add('none');
    }
  }
}

function activeInputContrasenia(element) {
  var expresionRegularP = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*\-_\/\.,|{}<>\[\]\(\)=?\+¿¡])(?=.{8,})/;
  var textExito = "Contraseña correcta";
  var textError = "Contraseña incorrecta, debe contener mínimo ocho (8) caracteres, un número, una letra minúscula, una letra mayúscula, un carácter especial.";

  if (expresionRegularP.test(this.value) && this.classList.contains("success") === false) {
    this.classList.remove('error');
    this.classList.add('success');
    crearMensaje(this, textExito, 'success', 'nota-contrasenia');
  } else if(expresionRegularP.test(this.value) === false && this.classList.contains("error") === false) {
    this.classList.remove('success');
    this.classList.add('error');
    crearMensaje(this, textError, 'error', 'nota-contrasenia');
  }
}

// correo electronico
function activeInputCorreo(element) {
  var expresionRegularE = /^([\da-z_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/;
  var textExito = "Correo electrónico válido";
  var textError = "Correo electrónico no válido";

  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {
    this.classList.remove('error');
    this.classList.add('success');
    crearMensaje(this, textExito, 'success', '');
  } else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
    this.classList.remove('success');
    this.classList.add('error');
    crearMensaje(this, textError, 'error', '');
  }
}

// teléfono
function activeInputTelefono(element) {
  var expresionRegularE = /^[+]\(?(\d{2})\)? [-]?(\d{3})[-]? (\d{7,10})$/;
  var textExito = "Número de teléfono válido";
  var textError = "Número de teléfono no válido";

  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {
    this.classList.remove('error');
    this.classList.add('success');
    crearMensaje(this, textExito, 'success', '');
  } else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
    this.classList.remove('success');
    this.classList.add('error');
    crearMensaje(this, textError, 'error', '');
  }
}

function crearMensaje(e, text, type, describedby) {
  var dataMensajes = {
    'success': {
      'id': 'campoSuccess-id',
      'aria-invalid': 'false',
      'class': 'success-texto-govco',
      'role': 'status',
      'aria-live': 'polite',
    },
    'error': {
      'id': 'campoWarning-id',
      'aria-invalid': 'true',
      'class': 'error-texto-govco',
      'role': 'alert',
      'aria-live': 'assertive',
    }
  };

  var parentInput = e.closest('.entradas-de-texto-govco');
  var spanOld = parentInput.querySelector('.alert-entradas-de-texto-govco');
  if (spanOld) { parentInput.removeChild(spanOld); }
  var newSpan = document.createElement('span');
  var span = parentInput.appendChild(newSpan);

  e.setAttribute('aria-describedby', describedby+' '+dataMensajes[type]['id']);
  e.setAttribute('aria-invalid', dataMensajes[type]['aria-invalid']);

  span.textContent = text;
  span.classList.add(dataMensajes[type]['class'], 'alert-entradas-de-texto-govco');
  span.id = dataMensajes[type]['id'];
  span.setAttribute('role', dataMensajes[type]['role']);
  span.setAttribute('aria-live', dataMensajes[type]['aria-live']);
}
