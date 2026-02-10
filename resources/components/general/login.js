/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Login
 *  - Version: 4.0.0
 */
window.addEventListener("load", function(event) {
  initLogin();
});

function methodAssign(e, f, a) {
  for(let i of a) {
    i.addEventListener(e, f, false);
  }
}

function initLogin() {
  const radio = document.querySelectorAll('.radio-login-govco input');
  methodAssign("change", getOptionPerson, radio);
  
  const inputNumLogin = document.querySelectorAll('input[typeData="num"]');
  methodAssign("keyup", activeInputNumberLoguin, inputNumLogin);

  initDropDown();
  initInput();

  // desplegables del login
  const selects = document.querySelectorAll('.desplegable-login-govco');
  methodAssign("click", setInputs, selects);
}

function getOptionPerson() {
  const grandParent = this.parentNode.parentNode;
  const container = grandParent.parentNode;
  const value = this.value == '1' ? 'natural' : 'juridica';
  container.setAttribute('data-content',value);
  const option = container.querySelector('.container-login-opcion-govco[data-container-persona="' + value + '"] .desplegable-selected-option');
  const valueSelected = option.innerHTML;
  const button = container.querySelector('button.btn-govco');
  styleButtonLogin(valueSelected, button);
}

function setInputs() {
  const atribute = this.getAttribute('data-persona');
  const option = this.querySelector('.desplegable-selected-option');
  const valueSelected = option.innerHTML;
  const sibling = this.nextElementSibling;
  
  if (valueSelected != "Escoger") {
    sibling.classList.remove('none-login-govco');
    if (atribute == 'natural') {
      const dataConfig = setNaturalPerson(valueSelected);
      setInputLabel(dataConfig, sibling, valueSelected, atribute);
    } else if (atribute == 'juridica') {           
      const dataConfig = setJuridicaPerson(valueSelected);
      setInputLabel(dataConfig, sibling, valueSelected, atribute);
    }
  } else {
      sibling.classList.add('none-login-govco');
  }
}

function setInputLabel(data, parent, valueSelected, atribute) {
  const label = parent.querySelector('label');
  const input = parent.querySelector('input');
  const containerParent = parent.parentNode.parentNode;
  const button = containerParent.querySelector('button.btn-govco');
  
  label.innerText = data.label;
  input.placeholder = data.placeholder;
  input.setAttribute('typeData', data.typeData);
  input.setAttribute('type', data.type);

  cleanInputsLoguin(parent);  
  
  if (atribute === 'juridica') {    
    const inputnum = containerParent.querySelectorAll('input[typeData="num"]');
    methodAssign("keyup", activeInputNumberLoguin, inputnum);

    const inputnumnit = containerParent.querySelectorAll('input[typeData="nit"]');
    methodAssign("keyup", activeInputNumberLoguinNit, inputnumnit);

    const inputnumnit1 = containerParent.querySelectorAll('input[typeData="nit1"]');
    methodAssign("keyup", activeInputNumberLoguinNit1, inputnumnit1);
    if (valueSelected === 'Cédula de extranjería') {
      input.removeEventListener("keyup", activeInputNumberLoguin);
    } 

  }else if (atribute === 'natural') {    
    const inputnum = containerParent.querySelectorAll('input[typeData="num"]');
    methodAssign("keyup", activeInputNumberLoguinNatural, inputnum);
  }

  if (valueSelected === 'Correo electrónico') {
    input.removeEventListener("keyup", activeInputNumberLoguinNatural);
    input.addEventListener("keyup", activeInputCorreo);
    input.setAttribute('maxlength',20);
  }  
  if (valueSelected === 'Cédula de extranjería') {
    input.removeEventListener("keyup", activeInputNumberLoguinNatural);
    input.removeEventListener("keyup", activeInputCorreo);
  } 
  styleButtonLogin(valueSelected, button);
}

function cleanInputsLoguin(parent) {
  const inputsLoguin = parent.querySelectorAll('.entradas-de-texto-govco');
  for (const element of inputsLoguin) {
    let inputs = element.querySelectorAll('input');
    for (const input of inputs) {
      input.value = '';
      input.classList.remove('error', 'success');      
    }

    const containerAlert = element.querySelector('.alert-entradas-de-texto-govco');
    if (containerAlert) {    
      element.removeChild(containerAlert); 
    }
  }
}

function styleButtonLogin(valueSelected, button) {
  if (valueSelected != "Escoger") {
    button.disabled = false;
  } else {
    button.disabled = true;
  }
}

function setNaturalPerson(valorSeleccionado) {
  let data = {
    'label': '',
    'placeholder': '',
    'type': 'text',
    'typeData': ''
  };

  if (valorSeleccionado == "Correo electrónico") {  
    data = {
      'label': 'Correo electrónico*',
      'placeholder': 'Ejemplo: correo@email.com',
      'type': 'mail',
      'typeData': 'mail'
    };
  } else if (valorSeleccionado == "Cédula de ciudadanía") {
    data = {
      'label': 'Cédula de ciudadanía*',
      'placeholder': 'Ingresa tu número de identificación',
      'type': 'text',
      'typeData': 'num'
    };
  } else if (valorSeleccionado == "Cédula de extranjería") {
    data = {
      'label': 'Cédula de extranjería*',
      'placeholder': 'Ingresa tu número de identificación',
      'type': 'text',
      'typeData': 'num'
    };
  } else if (valorSeleccionado == "Tarjeta de identidad") {
    data = {
      'label': 'Tarjeta de identidad*',
      'placeholder': 'Ingresa tu número de identificación',
      'type': 'text',
      'typeData': 'num'
    };
  } else if (valorSeleccionado == "Permiso especial de permanencia") {
    data = {
      'label': 'Permiso especial de permanencia*',
      'placeholder': 'Ingresa tu número de identificación',
      'type': 'text',
      'typeData': 'num'
    };
  }
  return data;
}

function setJuridicaPerson(valorSeleccionado) {
    let data = {
      'label': '',
      'placeholder': '',
      'type': 'text',
      'typeData': ''
    };

    if (valorSeleccionado == "Cédula de ciudadanía") {
      data = {
        'label': 'Cédula de ciudadanía*',
        'placeholder': 'Ingresa tu número de identificación',
        'type': 'text',
        'typeData': 'num'
      };
    } else if (valorSeleccionado == "Cédula de extranjería") {
      data = {
        'label': 'Cédula de extranjería*',
        'placeholder': 'Ingresa tu número de identificación',
        'type': 'text',
        'typeData': 'num'
      };
    }
    return data;
}

function activeInputNumberLoguin(element) {
  var expresionRegularE = /^[0-9,$]*$/;
  var textExito = "Número de identificación válido";
  var textError = "Número de identificación no válido";
  let countWord = this.value.length;
if(countWord == 0) {
  this.classList.remove('success');
  this.classList.remove('error');

  if(document.getElementById('campoSuccess-id'))
  {
    document.getElementById('campoSuccess-id').style.display ="none";
  }
  if( document.getElementById('campoWarning-id'))
  {
    document.getElementById('campoWarning-id').style.display ="none";
  }
}else{
  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {

      this.classList.remove('error');
      this.classList.add('success');
      crearMensaje(this, textExito, 'success', '');

  }else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
      if (countWord == 0){
          this.classList.remove('success');
          this.classList.remove('error');

          if(document.getElementById('campoSuccess-id'))
          {
            document.getElementById('campoSuccess-id').style.display ="none";
          }
          if( document.getElementById('campoWarning-id'))
          {
            document.getElementById('campoWarning-id').style.display ="none";
          }
      }
      else{
        this.classList.remove('success');
        this.classList.add('error');
        crearMensaje(this, textError, 'error', '');
      }
  }
}
}

function activeInputNumberLoguinNit(element) {
  var expresionRegularE = /^[0-9,$]*$/;
  var textExito = "Número de NIT válido";
  var textError = "Número de NIT no válido";
  let countWord = this.value.length;
if(countWord == 0) {
  this.classList.remove('success');
  this.classList.remove('error');

  if(document.getElementById('campoSuccess-idNit'))
  {
    document.getElementById('campoSuccess-idNit').style.display ="none";
  }
  if( document.getElementById('campoWarning-idNit'))
  {
    document.getElementById('campoWarning-idNit').style.display ="none";
  }
}else{
  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {

      this.classList.remove('error');
      this.classList.add('success');
      crearMensajeNit(this, textExito, 'success', '');

  }else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
      if (countWord == 0){
          this.classList.remove('success');
          this.classList.remove('error');

          if(document.getElementById('campoSuccess-idNit'))
          {
            document.getElementById('campoSuccess-idNit').style.display ="none";
          }
          if( document.getElementById('campoWarning-idNit'))
          {
            document.getElementById('campoWarning-idNit').style.display ="none";
          }
      }
      else{
        this.classList.remove('success');
        this.classList.add('error');
        crearMensajeNit(this, textError, 'error', '');
      }
  }
}
}

function activeInputNumberLoguinNit1(element) {
  var expresionRegularE = /^[0-9,$]*$/;
  var textExito = "Num de NIT válido";
  var textError = "Num de NIT no válido";
  let countWord = this.value.length;
if(countWord == 0) {
  this.classList.remove('success');
  this.classList.remove('error');

  if(document.getElementById('campoSuccess-idNit1'))
  {
    document.getElementById('campoSuccess-idNit1').style.display ="none";
  }
  if( document.getElementById('campoWarning-idNit1'))
  {
    document.getElementById('campoWarning-idNit1').style.display ="none";
  }
}else{
  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {

      this.classList.remove('error');
      this.classList.add('success');
      crearMensajeNit1(this, textExito, 'success', '');

  }else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
      if (countWord == 0){
          this.classList.remove('success');
          this.classList.remove('error');

          if(document.getElementById('campoSuccess-idNit1'))
          {
            document.getElementById('campoSuccess-idNit1').style.display ="none";
          }
          if( document.getElementById('campoWarning-idNit1'))
          {
            document.getElementById('campoWarning-idNit1').style.display ="none";
          }
      }
      else{
        this.classList.remove('success');
        this.classList.add('error');
        crearMensajeNit1(this, textError, 'error', '');
      }
  }
}
}

function activeInputNumberLoguinNatural(element) {
  var expresionRegularE = /^[0-9,$]*$/;
  var textExito = "Número de identificación válido";
  var textError = "Número de identificación no válido";
  let countWord = this.value.length;
if(countWord == 0) {
  this.classList.remove('success');
  this.classList.remove('error');

  if(document.getElementById('campoSuccess-idN'))
  {
    document.getElementById('campoSuccess-idN').style.display ="none";
  }
  if( document.getElementById('campoWarning-idN'))
  {
    document.getElementById('campoWarning-idN').style.display ="none";
  }
}else{
  if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {

      this.classList.remove('error');
      this.classList.add('success');
      crearMensajeNatural(this, textExito, 'success', '');

  }else if(expresionRegularE.test(this.value) === false ) {
      if (countWord == 0){
          this.classList.remove('success');
          this.classList.remove('error');

          if(document.getElementById('campoSuccess-idN'))
          {
            document.getElementById('campoSuccess-idN').style.display ="none";
          }
          if( document.getElementById('campoWarning-idN'))
          {
            document.getElementById('campoWarning-idN').style.display ="none";
          }
      }
      else{
        this.classList.remove('success');
        this.classList.add('error');
        crearMensajeNatural(this, textError, 'error', '');
      }
  }
}
}

/* -------------------------------- entradas de texto --------------------------------------- */

function initInput() {
  var inputDisabled = document.querySelectorAll('input[disabled]');

  for (let d of inputDisabled) {
    var containerDisabled = d.closest('.entradas-de-texto-govco');
    containerDisabled.classList.add('disabled-govco');
  }

  var inputCorreo = document.querySelectorAll('input[typeData="mail"]');
  methodAssign("keyup", activeInputCorreo, inputCorreo);
}

function activeInputCorreo(element) {
  var expresionRegularE = /^([da-z_.-]+)@([da-z.-]+).([a-z.]{2,6})$/;
  var textExito = "Correo electrónico válido";
  var textError = "Correo electrónico no válido";
  let countWord = this.value.length;
  if(countWord == 0) {
    this.classList.remove('success');
    this.classList.remove('error');

    if(document.getElementById('campoSuccess-id'))
    {
      document.getElementById('campoSuccess-id').style.display ="none";
    }
    if( document.getElementById('campoWarning-id'))
    {
      document.getElementById('campoWarning-id').style.display ="none";
    }
  }else{
    if (expresionRegularE.test(this.value) && this.classList.contains("success") === false) {

        this.classList.remove('error');
        this.classList.add('success');
        crearMensaje(this, textExito, 'success', '');

    }else if(expresionRegularE.test(this.value) === false && this.classList.contains("error") === false) {
        if (countWord == 0){
            this.classList.remove('success');
            this.classList.remove('error');

            if(document.getElementById('campoSuccess-id'))
            {
              document.getElementById('campoSuccess-id').style.display ="none";
            }
            if( document.getElementById('campoWarning-id'))
            {
              document.getElementById('campoWarning-id').style.display ="none";
            }
        }
        else{
          this.classList.remove('success');
          this.classList.add('error');
          crearMensaje(this, textError, 'error', '');
        }
    }
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

function crearMensajeNatural(e, text, type, describedby) {
  var dataMensajes = {
    'success': {
      'id': 'campoSuccess-idN',
      'aria-invalid': 'false',
      'class': 'success-texto-govco',
      'role': 'status',
      'aria-live': 'polite',
    },
    'error': {
      'id': 'campoWarning-idN',
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

function crearMensajeNit(e, text, type, describedby) {
  var dataMensajes = {
    'success': {
      'id': 'campoSuccess-idNit',
      'aria-invalid': 'false',
      'class': 'success-texto-govco',
      'role': 'status',
      'aria-live': 'polite',
    },
    'error': {
      'id': 'campoWarning-idNit',
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

function crearMensajeNit1(e, text, type, describedby) {
  var dataMensajes = {
    'success': {
      'id': 'campoSuccess-idNit1',
      'aria-invalid': 'false',
      'class': 'success-texto-govco',
      'role': 'status',
      'aria-live': 'polite',
    },
    'error': {
      'id': 'campoWarning-idNit1',
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

/* -------------------------------- fin entradas de texto --------------------------------------- */
  
/* -------------------------------- desplegables --------------------------------------- */
function initDropDown() {
  initDropDownBasic();
  document.body.addEventListener('mouseup', mouseUpDropDown.bind(this), true);
}

function mouseUpDropDown(e) {
  if (!e.target.closest('.checkbox-seleccion-govco')) {
      if (!e.target.closest('.desplegable-govco')) {
          closeAllSelect();
      }
  }
}

function closeAllSelect() {
  const selectsSelecteds = document.querySelectorAll(".desplegable-govco:not(.desplegable-calendar-govco) .desplegable-selected-option:not(.dont-close)");
  if (selectsSelecteds) {
      selectsSelecteds.forEach(function (e) {
          if (e.classList.contains("desplegable-arrow-active")) {
              e.classList.remove("desplegable-arrow-active");
              e.nextElementSibling.classList.add("desplegable-hide");

              if (e.classList.contains('search-filter-selected-option')) {
                  const selected = e.nextElementSibling.querySelector('.same-as-selected');
                  if (selected) {
                      const input = e.querySelector('input');
                      input.value = selected.innerHTML;
                  }
              }
          }
      })
  }
}

function handleKeyDownDropDown(e) {
  let select = this.querySelector('.desplegable-selected-option');
  
  if (select.classList.contains('desplegable-arrow-active')) {
      if (select.tagName == 'DIV') {
          select = select.querySelector('input');
      }
      const containerItems = this.querySelector(".desplegable-items");
      
      containerItems.onscroll = function() {
          const visibleItems = this.querySelectorAll("li");
          if (document.activeElement == visibleItems[0]) {
              this.scrollTop = 0;
          }
      };
      if (e.which == 38) { // Key up
          upDropDown(this, select);
      } else if (e.which == 40) { // Key down
          downDropDown(this, select);
      } else if (e.which == 9) { // Tab
          let container = e.target.parentNode;
          if (select.tagName == 'INPUT') {
              container = container.parentNode;
          }
          closedDropDown(container);
      }
  }
}

function closedDropDown(containerSelect) {
  const containerOptions = containerSelect.querySelector('.desplegable-items');
  const selectActive = containerSelect.querySelector('.desplegable-selected-option.desplegable-arrow-active');
  if (selectActive) {
      selectActive.classList.remove('desplegable-arrow-active');
      containerOptions.classList.add('desplegable-hide');
  }

  if (containerSelect.classList.contains('desplegable-filter-govco')) {
      const selected = containerSelect.querySelector('.same-as-selected');
      if (selected) {
          const input = containerSelect.querySelector('.desplegable-selected-option input');
          input.value = selected.innerHTML;
      }
  }
}

function upDropDown(container, select) {
  const active = document.activeElement;
  const itemsList = container.querySelectorAll(".desplegable-items li:not(.none-desplegable-govco)");
  if (active === itemsList[0]) {
      select.focus();
  } else {
      for (let i = 1; i < itemsList.length; i++) {
          if (active === itemsList[i]) {
              itemsList[i - 1].focus();
          }
      }
  }
}

function downDropDown(container, select) {
  let active = document.activeElement;
  const items = container.querySelectorAll(".desplegable-items li:not(.none-desplegable-govco)");
  const itemSelected = container.querySelector(".desplegable-items li.same-as-selected");

  if (itemSelected && active === select) {
      itemSelected.focus();
  } else if (active === select) {
      items[0].focus();
  } else {
      for (var i = 0; i < items.length - 1; i++) {
          if (active === items[i]) {
              items[i + 1].focus();
          }
      }
  }
}

function handleKeyDownOptionDropDown(e) {
  if (e.which == 13) { // Enter
      enterOptionDropDown(e.target);
  } else if (e.which == 9) { // Tab
      const containerSelect = e.target.parentNode.parentNode;
      closedDropDown(containerSelect);
  }
}

function enterOptionDropDown(element) {
  const containerOptions = element.parentNode;
  const containerSelect = containerOptions.parentNode;
  let select = containerSelect.querySelector('.desplegable-selected-option');
  let inputHidden = containerSelect.querySelector('input[type="hidden"');
  const item = containerOptions.querySelector('li.same-as-selected');
  if (item) {
      item.classList.remove('same-as-selected');
      if (item.classList.length == 0) {
          item.removeAttribute("class");
      }
  }
  if (select.tagName == 'DIV') {
      const clearIcon = select.querySelector(".btn-clear-desplegable-govco");
      clearIcon.classList.remove("d-none");

      select = select.querySelector('input');
      select.value = element.innerHTML;        
  } else {
      select.innerHTML = element.innerHTML;
  }
  inputHidden.value = element.getAttribute('value');
  element.classList.add('same-as-selected');
  closedDropDown(containerSelect);
}

function toggleSelect(elmnt) {
  if (elmnt) {
      elmnt.nextElementSibling.classList.toggle("desplegable-hide");
      elmnt.classList.toggle("desplegable-arrow-active");
  }
}

function createInputHidden(selectTag, valueSelected) {
  let inputHidden = document.createElement("input");
  inputHidden.setAttribute('type', 'hidden');
  inputHidden.value = valueSelected;
  if (selectTag.attributes.length > 0) {
      for (const attribute of selectTag.attributes) {
          if (attribute.name == 'class') {
              inputHidden.classList.add(attribute.value);
          } else {
              inputHidden.setAttribute(attribute.name, attribute.value);
          }
      }
  }
  return inputHidden;
}

function initDropDownBasic() {
  var containerDesplegables = document.querySelectorAll('.desplegable-govco[data-type="basic"]');    
  for (const containerDesplegable of containerDesplegables) {
      createList(containerDesplegable.id);
  }
}

function createList(idSelectElement) {
  let options;
  const select = document.getElementById(idSelectElement);
  if (select == undefined || select == null) {
      return false;
  }
  const selectTag = select.querySelector("select");
  // Se crea estructura de listado seleccionable
  let selectSelected = createButtonList(selectTag);
  if (selectTag.disabled) {
      selectSelected.disabled = true;
      const label = select.previousElementSibling;
      label.classList.add('disabled-desplegable-govco');
  }
  select.appendChild(selectSelected);

  let selectItems = document.createElement("ul");
  selectItems.setAttribute("class", "desplegable-items desplegable-hide");
  const valueSelected = selectTag.options[selectTag.selectedIndex].getAttribute('value') ? selectTag.options[selectTag.selectedIndex].getAttribute('value') : '';

  for (let j = 1; j < selectTag.length; j++) {
      // Se construyen opciones de selección
      options = document.createElement("li");
      options.innerHTML = selectTag.options[j].innerHTML;
      options.setAttribute("value", selectTag.options[j].getAttribute('value'));   
      options.setAttribute("tabindex", "-1");
      
      if (valueSelected > 0 && selectTag.options[j].getAttribute('value') == valueSelected) {
          options.classList.add('same-as-selected');
      }

      options.addEventListener("click", function (e) {
          enterOptionDropDown(e.target);
      });
      options.addEventListener('keydown', handleKeyDownOptionDropDown);
      selectItems.appendChild(options);
  }
  select.appendChild(selectItems);
  select.addEventListener('keydown', handleKeyDownDropDown);
  
  let inputHidden = createInputHidden(selectTag, valueSelected);
  select.appendChild(inputHidden);
  
  select.removeChild(selectTag);
}

function createButtonList(selectTag) {
  let selectSelected = document.createElement("button");
  selectSelected.setAttribute("class", "desplegable-selected-option");
  selectSelected.innerHTML = selectTag.options[selectTag.selectedIndex].innerHTML;
  if (selectTag.getAttribute('aria-invalid') == 'true') {
      selectSelected.classList.add('error-desplegable-govco');
  }
  selectSelected.addEventListener("click", function (e) {
      e.stopPropagation();
      toggleSelect(this);
  });    

  return selectSelected;
}
/* -------------------------------- fin desplegables --------------------------------------- */