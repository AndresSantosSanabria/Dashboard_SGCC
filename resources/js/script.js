//  * Gov.co (https://www.gov.co) - Gobierno de Colombia
//  *  - Componente: script
//  *  - Version: 4.0.0

function methodAssign(event, method, elements) {
  for (let i of elements) {
    i.addEventListener(event, method, false);
  }
}

// ENTRADAS DE TEXTO
function asignaFuncion(e, f, elements) {
  for(let i of elements) {
    i.addEventListener(e, f, false);
  }
}

window.addEventListener("load", function() {
  /* ============================= carga de archivo ===================================================*/
  var inputsFile = document.querySelectorAll('.input-carga-de-archivo-govco');
  methodAssignFileInput("change", selectingFiles, inputsFile);

  var buttonsFile = document.querySelectorAll('.button-loader-carga-de-archivo-govco');
  methodAssign("click", clickButtonFile, buttonsFile);

  window.addEventListener('resize', windowSize);
  windowSize();  
  /* ============================= fin carga de archivo ===============================================*/

  
  /* ============================= carrusel ===============================================*/
  window.addEventListener('resize', windowSizeCarrusel);
  windowSizeCarrusel();
  /* ============================= fin carrusel ===============================================*/
  
  	/* =============================== entradas de texto ====================================*/
  	initInput();
  	/* ================================= Desplegables ======================================= */
	initDropDown();
	/* =================================== Buscador ========================================= */
	initSearchBar();
  	/* ===================================== login ==========================================*/
  	initLogin();  
	/* ============================== Menu de navegacion ==================================== */
	initMenu();
	/* ============================== Linea de avance ==================================== */
	initAdvanceLine();
});


/* ================================= Buscador =============================== */
function initSearchBar() {
  const inputSearch = document.querySelectorAll(".input-search-govco:not(.noActive)");
  console.log('initSearchBar', inputSearch);
  getElementInputSearchBar(inputSearch);
  methodAssign("keyup", activeInputSearchBar, inputSearch);
  methodAssign("keydown", keydownInputSearchBar, inputSearch);
  methodAssign("blur", blurInputSearchBar, inputSearch);
  methodAssign("focus", focusInputSearchBar, inputSearch);
  
  const buttonClean = document.querySelectorAll(".search-govco .icon-close-search-govco");
  methodAssign("click", cleanInputSearchBar, buttonClean);
  methodAssign("blur", blurcleanInputSearchBar, buttonClean);
}

function getElementInputSearchBar(elements) {
  for (let i of elements) {
    assignFunctionItemsSearchBar(i);
  }
}

function activeInputSearchBar(element) {
  const parent = element.target.parentNode;
  const existsClass = parent.classList.contains('active');
  if (element.target.value === '') {
    parent.classList.remove('active', 'exist-content');
  } else if (!existsClass) {
    parent.classList.add('active', 'exist-content');
  }
}

function assignFunctionItemsSearchBar(input) {
  const parentInput = input.parentNode;
  const parentItems = parentInput.nextElementSibling;
  const items = parentItems.querySelectorAll("ul li a");

  for (let item of items) {
    item.addEventListener("keydown", function(event) {
      keysUpDownSearchBar(event, parentItems, input);
    });

    item.addEventListener("blur", function() {
      const elementI = item.parentNode
      const elementU = elementI.parentNode
      const elementDivOptions = elementU.parentNode
      const elementDivContainerOptions = elementDivOptions.parentNode;
      const elementDivContainerSearch = elementDivContainerOptions.previousElementSibling;
      existFocusContainerSearchBar(elementDivContainerSearch);
    });

    item.addEventListener("click", function() {
      input.value = '';
      parentInput.classList.remove('active', 'exist-content');
    });
  }
}

function keydownInputSearchBar(element) {
  const parentInput = this.parentNode;
  const parentItems = parentInput.nextElementSibling;
  const parentUl = parentItems.querySelector('.options-search-govco');

  if (parentUl) {
    parentUl.onscroll = function() {
      const visibleItems = this.querySelectorAll("li a");
      if (document.activeElement == visibleItems[0]) {
        this.scrollTop = 0;
      }
    };
    keysUpDownSearchBar(element, parentItems, this);
  }
}

function keysUpDownSearchBar(e, container, input) {
  // Key up
  if (e.which == 38) {
    upSearchBar(container, input);
  }
  // Key down
  if (e.which == 40) {
    downSearchBar(container, input);
  }
}

function downSearchBar(container, input) {
  const active = document.activeElement;
  const items = container.querySelectorAll("li a");
  if (active === input) {
    items[0].focus();
  } else {
    for (let i = 0; i < items.length - 1; i++) {
      if (active === items[i]) {
        items[i + 1].focus();
      }
    }
  }
}

function upSearchBar(container, input) {
  const active = document.activeElement;
  const itemsList = container.querySelectorAll("li:not(.none) a");
  if (active === itemsList[0]) {
    input.focus();
  } else {
    for (let i = 1; i < itemsList.length; i++) {
      if (active === itemsList[i]){
        itemsList[i - 1].focus();
      }
    }
  }
}

function cleanInputSearchBar() {
  const input = this.previousElementSibling;
  const parent = this.parentNode;
  input.value = '';
  parent.classList.remove('active', 'exist-content');
  input.focus();
}

function blurInputSearchBar() {
  const parent = this.parentNode;
  existFocusContainerSearchBar(parent);
}

function existFocusContainerSearchBar(element) {
  setTimeout(() => {
    if (!element.parentNode.contains(document.activeElement)) {
      element.classList.remove('active');
    }
  }, 100);
}

function focusInputSearchBar(element) {
  activeInputSearchBar(element);
}

function blurcleanInputSearchBar() {
  const parent = this.parentNode;
  existFocusContainerSearchBar(parent);
}
/* ================================= fin Buscador =============================== */


// VOLVER ARRIBA
var volverArriba = document.querySelectorAll(".volver-arriba-govco");
volverArriba.forEach(e => {
  e.addEventListener("click", backGoToUp, false);
});

function backGoToUp() {
  document.body.scrollTop = document.documentElement.scrollTop = 0;
}

/* ================================= Entradas de texto =============================== */
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
/* ================================= fin Entradas de texto =============================== */


/* ================================= Menu de navegacion =============================== */
function initMenu() {
	initSearchBar();
  
	/////// Prevent closing from click inside dropdown
	document.querySelectorAll('.dropdown-menu').forEach(function(element){
	  element.addEventListener('click', function (e) {
		e.stopPropagation();
	  });
	});
  
	document.querySelectorAll('.navbar-menu-govco a.dir-menu-govco').forEach(function(element){
	  element.addEventListener("click", eventClickItem, false);
	});
}
  
function eventClickItem() {
	const parentNavbar = this.closest('.navbar-menu-govco');
	parentNavbar.querySelectorAll('a.active').forEach(function(element){
		element.classList.remove('active');
	});
  
	this.classList.add('active');
	const container = this.closest('.nav-item');
	const itemParent =  container.querySelector('.nav-link');
	itemParent.classList.add('active');
}
/* ================================= fin Menu de navegacion =============================== */


/* ================================= Desplegables =============================== */
function initDropDown() {
    // Lista
    initDropDownBasic();

    // Filtro de búsqueda
    initDropDownFilter();

    // Casillas de verificación
    initDropDownCheck();

    // Calendario
    initDropDownCalendar();

    // All Desplegables
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


// Lista, Filtro de búsqueda, Casillas de verificación
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
        for (let i = 0; i < items.length - 1; i++) {
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

// Lista, Casillas de verificación
function toggleSelect(elmnt) {
    if (elmnt) {
        elmnt.nextElementSibling.classList.toggle("desplegable-hide");
        elmnt.classList.toggle("desplegable-arrow-active");
    }
}

// Lista, Filtro de busqueda
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

// Lista
function initDropDownBasic() {
    const containerDesplegables = document.querySelectorAll('.desplegable-govco[data-type="basic"]');    
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

// Filtro de búsqueda
function initDropDownFilter() {
    const containerDesplegables = document.querySelectorAll('.desplegable-govco[data-type="filter"]');    
    for (const containerDesplegable of containerDesplegables) {
        createListFilter(containerDesplegable.id);
    }
}

function createListFilter(idSelectElement) {
    let options;
    const select = document.getElementById(idSelectElement);
    if (select == undefined || select == null) {
        return false;
    }
    const selectTag = select.querySelector("select");

    // Se construye estructura de desplegable con filtro de búsqueda
    const selectSelected = createButtonFilter(selectTag);    
    select.appendChild(selectSelected);

    let selectItems = document.createElement("ul");
    selectItems.setAttribute("class", "desplegable-items desplegable-hide");

    const input = createInputFilter(selectTag, selectSelected);
    const valueSelected = selectTag.options[selectTag.selectedIndex].getAttribute('value') ? selectTag.options[selectTag.selectedIndex].getAttribute('value') : '';
    if (valueSelected > 0) {
        input.value = selectTag.options[selectTag.selectedIndex].innerHTML;
    }
    selectSelected.appendChild(input);

    const iconClear = document.createElement("button");
    iconClear.setAttribute("onclick", "clearInput(this)");
    iconClear.setAttribute("class", "btn-clear-desplegable-govco");

    if (valueSelected == 0) {
        iconClear.classList.add("d-none");
    }
    selectSelected.appendChild(iconClear);

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
            enterOptionDropDownFilter(e.target);
        });
        options.addEventListener('keydown', handleKeyDownOptionDropDown);
        selectItems.appendChild(options);
    }
    select.appendChild(selectItems);

    let inputHidden = createInputHidden(selectTag, valueSelected);
    select.appendChild(inputHidden);

    select.addEventListener('keydown', handleKeyDownDropDown);
    select.removeChild(selectTag);
}

function createButtonFilter(selectTag) {
    const selectSelected = document.createElement("DIV");
    selectSelected.setAttribute("class", "desplegable-selected-option search-filter-selected-option");
    selectSelected.addEventListener("click", clickContainerDropDownInput);
    if (selectTag.getAttribute('aria-invalid') == 'true') {
        selectSelected.classList.add("error-desplegable-govco");
    }

    return selectSelected;
}

function createInputFilter(selectTag, selectSelected) {
    let input = document.createElement("input");
    input.setAttribute("type", "text");
    input.setAttribute("autocomplete", "off");
    input.setAttribute("class", "browser-default");
    input.addEventListener('keyup', handleKeyUpDropDownInput);
    input.addEventListener('keydown', handleKeyDownDropDownInput);
    input.setAttribute('placeholder', selectTag.getAttribute('placeholder') ? selectTag.getAttribute('placeholder') : 'Ingrese texto...');
    if (selectTag.disabled) {
        input.disabled = true;
        selectSelected.classList.add('disabled-desplegable-govco')
        const label = selectSelected.parentNode.previousElementSibling;
        label.classList.add('disabled-desplegable-govco');
    }
    return input;
}

function clickContainerDropDownInput(e) {
    if (this.classList.contains('desplegable-arrow-active')) {
        this.classList.remove('desplegable-arrow-active');
        const containerOptions = this.nextElementSibling;
        containerOptions.classList.add('desplegable-hide');
        const selected = containerOptions.querySelector('.same-as-selected');
        if (selected) {
            const input = this.querySelector('input');
            input.value = selected.innerHTML;
        }
    } else {
        e.stopPropagation();
        openSelect(this);
        filterFunction(this.querySelector('input'));
        this.querySelector('input').focus();
    }
}

function handleKeyUpDropDownInput() {
    filterFunction(this);
}

function handleKeyDownDropDownInput(e) {    
    if (e.which == 13) { // Enter
        openSelect(this.parentNode);
    }
}

function enterOptionDropDownFilter(element) {
    const containerOptions = element.parentNode;
    const containerSelect = containerOptions.parentNode;
    const input = containerSelect.querySelector('.desplegable-selected-option input');
    const inputHidden = containerSelect.querySelector('input[type="hidden"');
    const item = containerOptions.querySelector('li.same-as-selected');
    if (item) {
        item.classList.remove('same-as-selected');
        if (item.classList.length == 0) {
            item.removeAttribute("class");
        }
    }
    input.value = element.innerHTML;
    inputHidden.value = element.getAttribute('value');
    element.classList.add('same-as-selected');    
    const clearIcon = containerSelect.querySelector(".btn-clear-desplegable-govco");
    clearIcon.classList.remove("d-none");
    closedDropDown(containerSelect);
}

function clearInput(e) {
    const containerInput =  e.closest(".desplegable-govco.desplegable-filter-govco")
    const input = containerInput.querySelector(".desplegable-selected-option input");
    input.value = "";
    filterFunction(input);
    const clearIcon = containerInput.querySelector(".btn-clear-desplegable-govco");
    clearIcon.classList.add("d-none");
    const containerOptions = containerInput.querySelector('.desplegable-items');
    containerOptions.querySelector('.same-as-selected').classList.remove('same-as-selected');
}

function filterFunction(input) {
    const containerInput =  input.closest(".desplegable-govco.desplegable-filter-govco")
    const inputHidden = containerInput.querySelector("input[type='hidden'");
    let text, div, i;
    text = input.value.toUpperCase();
    const myDropdown = input.parentNode.nextElementSibling;
    div = myDropdown.getElementsByTagName("li");
    for (i = 0; i < div.length; i++) {
        const txtValue = div[i].textContent || div[i].innerText;
        if (txtValue.toUpperCase().indexOf(text) > -1) {
            div[i].classList.remove('none-desplegable-govco');
        } else {
            div[i].classList.add('none-desplegable-govco');
        }
    }
    const clearIcon = input.nextElementSibling;
    if (input.value != "" && input.value != undefined && input.value != null) {        
        clearIcon.classList.remove("d-none");
    } else {
        clearIcon.classList.add("d-none");
        inputHidden.value = '';
    }
}

function openSelect(elmnt) {
    if (elmnt) {
        elmnt.nextElementSibling.classList.remove("desplegable-hide");
        elmnt.classList.add("desplegable-arrow-active");
    }
}


// Casillas de verificación
function initDropDownCheck() {
    assignDropDownCheck(document.querySelectorAll('.desplegable-govco[data-type="check"]'), 'check');
    assignDropDownCheck(document.querySelectorAll('.desplegable-govco[data-type="switch"]'), 'switch');
    assignDropDownCheck(document.querySelectorAll('.desplegable-govco[data-type="radio-button"]'), 'radio-button');
}

function assignDropDownCheck(containerDesplegables, type) {
    for (const containerDesplegable of containerDesplegables) {
        createListCheck(containerDesplegable.id, type);
    }
}

function createListCheck(idSelectElement, type) {
    let selectItems;
    const select = document.getElementById(idSelectElement);

    if (select == undefined || select == null) {
        return false;
    }

    const selectTag = select.querySelector("select");

    // Se crea estructura de selección e items de selección
    let selectSelected = createButtonCheck(selectTag);
    if (selectTag.disabled) {
        selectSelected.disabled = true;
        const label = select.previousElementSibling;
        label.classList.add('disabled-desplegable-govco');
    }
    // Se crea estructura contenedora de las opciones
    selectItems = document.createElement("ul");
    selectItems.setAttribute("class", "desplegable-items desplegable-hide");
    let optionsSelected = [];    
    let optionsSelectedText = '';
    let optionsSelectedId = [];
    for (let j = 1; j < selectTag.length; j++) {
        // Se construyen las opciones
        optionsSelected = createDropDownTypeCheck(type, selectItems, selectTag, j, idSelectElement);
        if ((optionsSelected[0] && type != 'radio-button') || (type == 'radio-button' && optionsSelectedText == '')) {
            optionsSelectedText += optionsSelectedText ? ', ' + optionsSelected[0] : optionsSelected[0];
            optionsSelectedId.push(optionsSelected[1]);
        }
    }

    if (optionsSelectedText) {
        selectSelected.innerHTML = optionsSelectedText; 
    }
    select.appendChild(selectSelected);
    select.appendChild(selectItems);
    select.addEventListener('keydown', handleKeyDownDropDown);
    let inputHidden = createInputHiddenCheck(type, optionsSelectedId, selectTag);    
    select.appendChild(inputHidden);
    select.removeChild(selectTag);
}

function createButtonCheck(selectTag) {
    let selectSelected = document.createElement("button");
    selectSelected.setAttribute("class", "desplegable-selected-option");
    selectSelected.innerHTML = selectTag.options[0].innerHTML;    
    selectSelected.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleSelect(this);
    });
    if (selectTag.getAttribute('aria-invalid') == 'true') {
        selectSelected.classList.add("error-desplegable-govco");
    }

    return selectSelected;
}

function createInputHiddenCheck(type, optionsSelectedId, selectTag) {
    let inputHidden = document.createElement("input");
    inputHidden.setAttribute('type', 'hidden');
    inputHidden.value = type == 'radio-button' ? optionsSelectedId[0] : JSON.stringify(optionsSelectedId);
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

function createDropDownTypeCheck(type, selectItems, selectTag, j, idSelectElement) {
    if (type == 'check') {
        return createDropDownCheck(selectItems, selectTag, j);
    } else if (type == 'switch') {
        return createDropDownSwitch(selectItems, selectTag, j);
    } else if (type == 'radio-button') {
        return createDropDownRadioButton(selectItems, selectTag, j, idSelectElement);
    }
}

function createDropDownCheck(selectItems, selectTag, j) {
    let optionsSelectedText = '';
    let optionsSelected = '';
    let li = '';
    let labelText = '';
    let input = '';
    li = document.createElement("li");
    li.setAttribute("class", "checkbox-seleccion-govco");
    li.setAttribute("tabindex", "-1");
    li.addEventListener('click', clickOptionDropDownCheck);
    li.addEventListener('keydown', handleKeyDownOptionDropDownCheck);
    selectItems.appendChild(li);
    input = document.createElement("input");
    input.setAttribute("id", selectTag.options[j].value);
    input.setAttribute("type", "checkbox");
    if (selectTag.options[j].getAttribute('selected') != null) {
        input.checked = true;
        optionsSelectedText = selectTag.options[j].innerHTML;
        optionsSelected = selectTag.options[j].value;
    }
    input.addEventListener("change", function (e) {
        changeOptionDropDownCheck(e.target);
    });
    li.appendChild(input);
    labelText = document.createElement("label");
    labelText.setAttribute("for", selectTag.options[j].value);
    labelText.innerHTML = selectTag.options[j].innerHTML;
    li.appendChild(labelText);
    return [optionsSelectedText, optionsSelected];
    
}

function createDropDownSwitch(selectItems, selectTag, j) {
    let optionsSelectedText = '';
    let optionsSelected = '';
    let li = '';
    let labelText = '';
    let input = '';
    li = document.createElement("li");
    li.setAttribute("class", "switch-seleccion-govco form-switch");
    li.setAttribute("tabindex", "-1");
    li.addEventListener('click', clickOptionDropDownCheck);
    li.addEventListener('keydown', handleKeyDownOptionDropDownCheck);
    selectItems.appendChild(li);
    input = document.createElement("input");
    input.setAttribute("id", selectTag.options[j].value);
    input.setAttribute("type", "checkbox");
    input.setAttribute("class", "form-check-input");
    if (selectTag.options[j].getAttribute('selected') != null) {
        input.checked = true;
        optionsSelectedText = selectTag.options[j].innerHTML;
        optionsSelected = selectTag.options[j].value;
    }
    input.addEventListener("change", function (e) {
        changeOptionDropDownCheck(e.target);
    });
    li.appendChild(input);
    labelText = document.createElement("label");
    labelText.setAttribute("for", selectTag.options[j].value);
    labelText.innerHTML = selectTag.options[j].innerHTML;
    li.appendChild(labelText);
    return [optionsSelectedText, optionsSelected];
}

function createDropDownRadioButton(selectItems, selectTag, j, idSelectElement) {
    let optionsSelectedText = '';
    let optionsSelected = '';
    let li = '';
    let labelText = '';
    let input = '';
    li = document.createElement("li");
    li.setAttribute("class", "radio-seleccion-govco");
    li.setAttribute("tabindex", "-1");
    li.addEventListener('click', clickOptionDropDownCheck);
    li.addEventListener('keydown', handleKeyDownOptionDropDownCheck);
    selectItems.appendChild(li);
    input = document.createElement("input");
    input.setAttribute("id", selectTag.options[j].value);
    input.setAttribute("type", "radio");
    input.setAttribute("name", idSelectElement);
    input.setAttribute("class", "form-check-input");
    if (selectTag.options[j].getAttribute('selected') != null) {
        input.checked = true;
        optionsSelectedText = selectTag.options[j].innerHTML;
        optionsSelected = selectTag.options[j].value;
    }
    input.addEventListener("change", function (e) {
        changeOptionDropDownCheck(e.target);
    });
    li.appendChild(input);
    labelText = document.createElement("label");
    labelText.setAttribute("for", selectTag.options[j].value);
    labelText.innerHTML = selectTag.options[j].innerHTML;
    li.appendChild(labelText);
    return [optionsSelectedText, optionsSelected];
    
}

function clickOptionDropDownCheck(e) {
    enterOptionDropDownCheck(e.target);
}

function handleKeyDownOptionDropDownCheck(e) {
    if (e.which == 13) { // Enter
        enterOptionDropDownCheck(e.target);
    } else if (e.which == 9) { // Tab
        const containerSelect = e.target.parentNode.parentNode;
        closedDropDown(containerSelect);
    }
}

function enterOptionDropDownCheck(e) {
    const input = e.tagName == 'LI' ? e.querySelector('input') : e;
    changeOptionDropDownCheck(input);
}

function changeOptionDropDownCheck(e) {
    e.checked = e.checked ? false : true;
    const container = e.closest('.desplegable-govco');
    const typeDropDown = container.getAttribute('data-type');
    const button = container.querySelector('.desplegable-selected-option');
    const inputHidden = container.querySelector('input[type="hidden"]');
    const inputsChecked = container.querySelectorAll('.desplegable-items li input');
    let inputCheckedText = '';
    let inputChecked = [];
    initRadioButton(typeDropDown, button, inputHidden);
    
    for (const element of inputsChecked) {
        if (element.checked) {
            const label = element.nextElementSibling;
            if (typeDropDown == 'radio-button') {
                button.innerHTML = label.innerHTML;
                inputHidden.value = element.id;

            } else {
                inputCheckedText += inputCheckedText ? ', ' + label.innerHTML : label.innerHTML;
                inputChecked.push(element.id);
            }
        }
    }
    if (typeDropDown != 'radio-button') {
        button.innerHTML = inputCheckedText ? inputCheckedText : inputHidden.placeholder;
        inputHidden.value = JSON.stringify(inputChecked);
    }
}

function initRadioButton(typeDropDown, button, inputHidden) {
    if (typeDropDown == 'radio-button') {
        button.innerHTML = inputHidden.placeholder;
        inputHidden.value = '';
    }
}


//calendario
function initDropDownCalendar () {
    let containerDesplegables = document.querySelectorAll('.desplegable-govco[data-type="calendar"]');    
    for (const containerDesplegable of containerDesplegables) {
        createElementsDesplegable(containerDesplegable);        
        const inputContainer = containerDesplegable.querySelectorAll('.desplegable-selected-option');
        const inputNode = containerDesplegable.querySelectorAll('input');
        const dialogNode = containerDesplegable.querySelectorAll('[role=dialog]');
        methodAssignSelectCalendar(inputNode, dialogNode, inputContainer);
    }
}

function createElementsDesplegable (containerDesplegable) {
    const container = document.createElement("div");
    container.className = 'desplegable-calendar-control';
    container.setAttribute('role', 'dialog');
    container.setAttribute('aria-modal', 'true');
    container.setAttribute('aria-labelledby', 'id-dialog-label');
    containerDesplegable.appendChild(container);

    const header = document.createElement("div");
    header.className = 'header';
    container.appendChild(header);

    const previousDecade = document.createElement("button");
    previousDecade.className = 'prev-decade d-none';
    previousDecade.setAttribute('aria-label', 'previous decade');
    header.appendChild(previousDecade);

    const spanPreviousDecade = document.createElement("span");
    spanPreviousDecade.className = 'fas govco-chevron-left fa-lg';
    previousDecade.appendChild(spanPreviousDecade);

    const previousYear = document.createElement("button");
    previousYear.className = 'prev-year d-none';
    previousYear.setAttribute('aria-label', 'previous year');
    header.appendChild(previousYear);

    const spanPreviousYear = document.createElement("span");
    spanPreviousYear.className = 'fas govco-chevron-left fa-lg';
    previousYear.appendChild(spanPreviousYear);

    const previousMonth = document.createElement("button");
    previousMonth.className = 'prev-month';
    previousMonth.setAttribute('aria-label', 'previous month');
    header.appendChild(previousMonth);

    const spanPreviousMonth = document.createElement("span");
    spanPreviousMonth.className = 'fas govco-chevron-left fa-lg';
    previousMonth.appendChild(spanPreviousMonth);

    const monthsYear = document.createElement("button");
    monthsYear.className = 'show-months-year';
    monthsYear.setAttribute('aria-label', 'months year');
    header.appendChild(monthsYear);

    const hMonthsYear = document.createElement("h2");
    hMonthsYear.className = 'month-year';
    hMonthsYear.setAttribute('id', 'id-dialog-label');
    hMonthsYear.setAttribute('aria-live', 'polite');
    hMonthsYear.textContent = "Month Year";
    monthsYear.appendChild(hMonthsYear);

    const nextMonth = document.createElement("button");
    nextMonth.className = 'next-month';
    nextMonth.setAttribute('aria-label', 'next month');
    header.appendChild(nextMonth);

    const spanNextMonth = document.createElement("span");
    spanNextMonth.className = 'fas govco-chevron-right fa-lg';
    nextMonth.appendChild(spanNextMonth);

    const nextYear = document.createElement("button");
    nextYear.className = 'next-year d-none';
    nextYear.setAttribute('aria-label', 'next year');
    header.appendChild(nextYear);

    const spanNextYear = document.createElement("span");
    spanNextYear.className = 'fas govco-chevron-right fa-lg';
    nextYear.appendChild(spanNextYear);

    const nextDecade = document.createElement("button");
    nextDecade.className = 'next-decade d-none';
    nextDecade.setAttribute('aria-label', 'next decade');
    header.appendChild(nextDecade);

    const spanNextDecade = document.createElement("span");
    spanNextDecade.className = 'fas govco-chevron-right fa-lg';
    nextDecade.appendChild(spanNextDecade);

    const tableDay = document.createElement("table");
    tableDay.className = 'dates';
    tableDay.setAttribute('id', 'miCalendarioGrid');
    tableDay.setAttribute('role', 'grid');
    tableDay.setAttribute('aria-labelledby', 'id-dialog-label');
    container.appendChild(tableDay);

    const theadDay = document.createElement("thead");
    tableDay.appendChild(theadDay);

    const trDay = document.createElement("tr");
    theadDay.appendChild(trDay);

    let days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    let thDay = '';
    for (const day of days) {
        thDay = document.createElement("th");
        thDay.setAttribute('scope', 'col');
        thDay.setAttribute('abbr', day);
        thDay.textContent = day[0] + day[1];
        trDay.appendChild(thDay);        
    }

    const tbodyDay = document.createElement("tbody");
    tableDay.appendChild(tbodyDay);

    const tr2Day = document.createElement("tr");
    tbodyDay.appendChild(tr2Day);

    createDaysElementDesplegable(tr2Day, 26, 30, 'disabled');
    createDaysElementDesplegable(tr2Day, 1, 1, '');

    const tr3Day = document.createElement("tr");
    tbodyDay.appendChild(tr3Day);

    createDaysElementDesplegable(tr3Day, 2, 8, '');

    const tr4Day = document.createElement("tr");
    tbodyDay.appendChild(tr4Day);

    createDaysElementDesplegable(tr4Day, 9, 15, '');

    const tr5Day = document.createElement("tr");
    tbodyDay.appendChild(tr5Day);

    createDaysElementDesplegable(tr5Day, 16, 22, '');

    const tr6Day = document.createElement("tr");
    tbodyDay.appendChild(tr6Day);

    createDaysElementDesplegable(tr6Day, 23, 29, '');

    const tr7Day = document.createElement("tr");
    tbodyDay.appendChild(tr7Day);

    createDaysElementDesplegable(tr7Day, 30, 31, '');

    createDaysElementDesplegable(tr7Day, 1, 6, 'disabled');

    const tableMonth = document.createElement("table");
    tableMonth.className = 'months d-none';
    tableMonth.setAttribute('id', 'myMonthpickerGrid');
    tableMonth.setAttribute('role', 'grid presentation');
    tableMonth.setAttribute('aria-labelledby', 'id-dialog-label');
    tableMonth.setAttribute('aria-hidden', 'true');
    container.appendChild(tableMonth);

    const tbodyMonth = document.createElement("tbody");
    tableMonth.appendChild(tbodyMonth);

    const trMonth = document.createElement("tr");
    tbodyMonth.appendChild(trMonth);

    let months = ['Ene', 'Feb', 'Mar', 'Abr'];
    createMonthsElementDesplegable(months, trMonth, 1);

    const tr2Month = document.createElement("tr");
    tbodyMonth.appendChild(tr2Month);

    months = ['May', 'Jun', 'Jul', 'Ago'];
    createMonthsElementDesplegable(months, tr2Month, 5);

    const tr3Month = document.createElement("tr");
    tbodyMonth.appendChild(tr3Month);

    months = ['Sep', 'Oct', 'Nov', 'Dic'];
    createMonthsElementDesplegable(months, tr3Month, 9);

    const tableYear = document.createElement("table");
    tableYear.className = 'years d-none';
    tableYear.setAttribute('id', 'myYearpickerGrid');
    tableYear.setAttribute('role', 'grid presentation');
    tableYear.setAttribute('aria-labelledby', 'id-dialog-label');
    tableYear.setAttribute('aria-hidden', 'true');
    container.appendChild(tableYear);

    const tbodyYear = document.createElement("tbody");
    tableYear.appendChild(tbodyYear);

    let trYear = '';
    let tdYear = '';
    let buttontdYear = '';
    for (let index = 2020; index <= 2031; index++) {
        
        if (index % 4 == 0) {
            trYear = document.createElement("tr");
            tbodyYear.appendChild(trYear);
        }
        tdYear = document.createElement("td");
        tdYear.className = 'year-cell';
        trYear.appendChild(tdYear);

        buttontdYear = document.createElement("button");
        buttontdYear.className = 'year-button';
        buttontdYear.setAttribute('tabindex', '-1');
        buttontdYear.setAttribute('data-date', index);
        buttontdYear.textContent = index;
        tdYear.appendChild(buttontdYear);
    }
}

function createMonthsElementDesplegable (months, trMonth, increment) {
    let tdMonth = '';
    let buttontdMonth = '';
    
    for (let index = 0; index < months.length; index++) {
        tdMonth = document.createElement("td");
        tdMonth.className = 'month-cell';
        trMonth.appendChild(tdMonth);

        buttontdMonth = document.createElement("button");
        buttontdMonth.className = 'month-button';
        buttontdMonth.setAttribute('tabindex', '-1');
        buttontdMonth.setAttribute('data-date', index + increment);
        buttontdMonth.textContent = months[index];
        tdMonth.appendChild(buttontdMonth);
    }
}

function createDaysElementDesplegable (trDay, ini, fin, disabled) {
    let tdDay = '';
    let buttontdDay = '';
    for (let index = ini; index <= fin; index++) {
        tdDay = document.createElement("td");
        tdDay.className = 'date-cell';
        trDay.appendChild(tdDay);

        buttontdDay = document.createElement("button");
        buttontdDay.className = 'date-button';
        buttontdDay.setAttribute('tabindex', '-1');
        if (disabled) { buttontdDay.setAttribute('disabled', ''); }
        buttontdDay.textContent = index;
        tdDay.appendChild(buttontdDay);
    }
}

function methodAssignSelectCalendar(inputNode, dialogNode, inputContainer) {
    for (let index = 0; index < inputNode.length; index++) {
        const datePicker = new DatePicker(inputNode[index], dialogNode[index], inputContainer[index]);
        datePicker.init();
    }
}

let DatePickerDay = function (domNode, datepicker, index, row, column) {

    this.index = index;
    this.row = row;
    this.column = column;
    this.day = new Date();
    this.domNode = domNode;
    this.datepicker = datepicker;

    this.keyCode = Object.freeze({
        'TAB': 9,
        'ENTER': 13,
        'ESC': 27,
        'SPACE': 32,
        'PAGEUP': 33,
        'PAGEDOWN': 34,
        'END': 35,
        'HOME': 36,
        'LEFT': 37,
        'UP': 38,
        'RIGHT': 39,
        'DOWN': 40
    });
};

DatePickerDay.prototype.init = function () {
    this.domNode.setAttribute('tabindex', '-1');
    this.domNode.addEventListener('mousedown', this.handleMouseDown.bind(this));
    this.domNode.addEventListener('keydown', this.handleKeyDown.bind(this));
    this.domNode.innerHTML = '-1';
};

DatePickerDay.prototype.isDisabled = function () {
    return this.domNode.classList.contains('disabled');
};

DatePickerDay.prototype.updateDay = function (disable, day) {
    this.activeDisabled(disable);

    this.day = new Date(day);
    this.domNode.innerHTML = this.day.getDate();
    this.domNode.setAttribute('tabindex', '-1');
    this.domNode.removeAttribute('aria-selected');
    let d = this.day.getDate().toString();
    if (this.day.getDate() < 9) {
        d = '0' + d;
    }

    let m = this.day.getMonth() + 1;
    if (this.day.getMonth() < 9) {
        m = '0' + m;
    }

    this.domNode.setAttribute('data-date', this.day.getFullYear() + '-' + m + '-' + d);
};

DatePickerDay.prototype.activeDisabled = function (disable) {
    if (disable) {
        this.domNode.classList.add('disabled');
    } else {
        this.domNode.classList.remove('disabled');
    }
};

DatePickerDay.prototype.handleKeyDown = function (event) {
    let flag = false;

    switch (event.keyCode) {

        case this.keyCode.ESC:
            this.datepicker.hide();
            break;

        case this.keyCode.TAB:            
            if (!event.shiftKey) {
                this.datepicker.hide();
            }
            break;

        case this.keyCode.ENTER:
        case this.keyCode.SPACE:
            this.datepicker.setTextboxDate();
            this.datepicker.hide();
            flag = true;
            break;

        case this.keyCode.RIGHT:
            this.datepicker.moveFocusToNextDay();
            flag = true;
            break;

        case this.keyCode.LEFT:
            this.datepicker.moveFocusToPreviousDay();
            flag = true;
            break;

        case this.keyCode.DOWN:
            this.datepicker.moveFocusToNextWeek();
            flag = true;
            break;

        case this.keyCode.UP:
            this.datepicker.moveFocusToPreviousWeek();
            flag = true;
            break;

        case this.keyCode.PAGEUP:
            if (event.shiftKey) {
                this.datepicker.moveToPreviousYear();
            }
            else {
                this.datepicker.moveToPreviousMonth();
            }
            flag = true;
            break;

        case this.keyCode.PAGEDOWN:
            if (event.shiftKey) {
                this.datepicker.moveToNextYear();
            }
            else {
                this.datepicker.moveToNextMonth();
            }
            flag = true;
            break;

        case this.keyCode.HOME:
            this.datepicker.moveFocusToFirstDayOfWeek();
            flag = true;
            break;

        case this.keyCode.END:
            this.datepicker.moveFocusToLastDayOfWeek();
            flag = true;
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePickerDay.prototype.handleMouseDown = function (event) {
    if (this.isDisabled()) {
        this.datepicker.moveFocusToDay(this.date);
    } else {
        this.datepicker.setTextboxDate(this.day);
        this.datepicker.hide();
    }

    event.stopPropagation();
    event.preventDefault();
};

let CalendarButtonInput = {};
let CalendarContainerButtonInput = {};

let DatePicker = function (inputNode, dialogNode, inputContainer) {
    this.dayLabels = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
    this.monthLabels = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    this.inputNode = inputNode;
    this.dialogNode = dialogNode;
    this.inputContainer = inputContainer;
    
    this.viewDays = this.inputNode.getAttribute('days') ? this.inputNode.getAttribute('days') : 'true';
    this.inputNode.setAttribute('placeholder', this.viewDays == 'true' ? 'dd/mm/aaaa' : 'mm/aaaa');
    this.initialDate = this.getFormatDate(this.inputNode.getAttribute('min-day'));
    this.finalDate = this.getFormatDate(this.inputNode.getAttribute('max-day'));

    this.dateInput = new CalendarButtonInput(this.inputNode, this);
    this.containerDateInput = new CalendarContainerButtonInput(this.inputContainer, this);

    this.MonthYearNode = this.dialogNode.querySelector('.month-year');
    this.MonthsYearNode = this.dialogNode.querySelector('button.show-months-year');

    this.MonthsNodes = this.dialogNode.querySelectorAll('.month-button');
    this.YearNodes = this.dialogNode.querySelectorAll('.year-button');

    this.prevYearNode = this.dialogNode.querySelector('.prev-year');
    this.prevMonthNode = this.dialogNode.querySelector('.prev-month');
    this.prevDecadeNode = this.dialogNode.querySelector('.prev-decade');
    this.nextMonthNode = this.dialogNode.querySelector('.next-month');
    this.nextYearNode = this.dialogNode.querySelector('.next-year');
    this.nextDecadeNode = this.dialogNode.querySelector('.next-decade');

    this.okButtonNode = this.dialogNode.querySelector('button[value="ok"]');
    this.cancelButtonNode = this.dialogNode.querySelector('button[value="cancel"]');

    this.tbodyNode = this.dialogNode.querySelector('table.dates tbody');

    this.dateTableNode = this.dialogNode.querySelector('table.dates');

    this.monthsTableNode = this.dialogNode.querySelector('table.months');
    this.buttonsCellsMonths = this.monthsTableNode.querySelectorAll('button');

    this.yearsTableNode = this.dialogNode.querySelector('table.years');
    this.buttonsCellsYears = this.yearsTableNode.querySelectorAll('button');

    this.lastRowNode = null;

    this.days = [];

    this.focusDay = new Date();
    this.yearSelect = this.focusDay.getFullYear();
    this.monthSelected = this.focusDay.getMonth();
    this.selectedDay = new Date(0, 0, 1);

    this.isMouseDownOnBackground = false;

    this.keyCode = Object.freeze({
        'TAB': 9,
        'ENTER': 13,
        'ESC': 27,
        'SPACE': 32,
        'PAGEUP': 33,
        'PAGEDOWN': 34,
        'END': 35,
        'HOME': 36,
        'LEFT': 37,
        'UP': 38,
        'RIGHT': 39,
        'DOWN': 40
    });
};

DatePicker.prototype.init = function () {
    let that = this;
    this.dateInput.init();
    this.containerDateInput.init();
    this.MonthsYearNode.addEventListener('click', this.handleMonthsYear.bind(this));

    for (const monthButton of this.MonthsNodes) {
        monthButton.addEventListener('keydown', this.handleKeyDownMonth.bind(this))
    }

    for (const yearButton of this.YearNodes) {
        yearButton.addEventListener('keydown', this.handleKeyDownYear.bind(this))
    }

    this.prevMonthNode.addEventListener('click', this.handlePreviousMonthButton.bind(this));
    this.nextMonthNode.addEventListener('click', this.handleNextMonthButton.bind(this));

    this.prevMonthNode.addEventListener('keydown', this.handlePreviousMonthButton.bind(this));
    this.nextMonthNode.addEventListener('keydown', this.handleNextMonthButton.bind(this));

    this.prevYearNode.addEventListener('click', this.handlePreviousYearButton.bind(this));
    this.nextYearNode.addEventListener('click', this.handleNextYearButton.bind(this));

    this.prevYearNode.addEventListener('keydown', this.handlePreviousYearButton.bind(this));
    this.nextYearNode.addEventListener('keydown', this.handleNextYearButton.bind(this));

    this.prevDecadeNode.addEventListener('click', this.handlePreviousDecadeButton.bind(this));
    this.nextDecadeNode.addEventListener('click', this.handleNextDecadeButton.bind(this));

    this.prevDecadeNode.addEventListener('keydown', this.handlePreviousDecadeButton.bind(this));
    this.nextDecadeNode.addEventListener('keydown', this.handleNextDecadeButton.bind(this));

    this.buttonsCellsMonths.forEach(function (button) {
        button.addEventListener('click', that.handleMonthButton.bind(that));
    });

    this.buttonsCellsYears.forEach(function (button) {
        button.addEventListener('click', that.handleYearButton.bind(that));
    })

    document.body.addEventListener('mousedown', this.handleBackgroundMouseDown.bind(this), true);
    document.body.addEventListener('mouseup', this.handleBackgroundMouseUp.bind(this), true);

    // Create Grid of Dates
    this.tbodyNode.innerHTML = '';
    let index = 0;
    for (let i = 0; i < 6; i++) {
        const row = this.tbodyNode.insertRow(i);
        this.lastRowNode = row;
        row.classList.add('dateRow');
        for (let j = 0; j < 7; j++) {
            const cell = document.createElement('td');
            cell.classList.add('date-cell');
            const cellButton = document.createElement('button');
            cellButton.classList.add('date-button');
            cell.appendChild(cellButton);
            row.appendChild(cell);
            const dpDay = new DatePickerDay(cellButton, this, index, i, j);
            dpDay.init();
            this.days.push(dpDay);
            index++;
        }
    }

    this.monthsTableNode.classList.add('d-none');
    this.yearsTableNode.classList.add('d-none');

    this.updateGrid();
    this.setFocusDay(false);
};

DatePicker.prototype.updateGrid = function () {
    let i, flag;

    this.MonthYearNode.innerHTML = this.monthLabels[this.monthSelected] + ' ' + this.yearSelect;
    const firstDayOfMonth = new Date(this.yearSelect, this.monthSelected, 1);
    const daysInMonth = new Date(this.yearSelect, this.monthSelected + 1, 0).getDate();
    const dayOfWeek = firstDayOfMonth.getDay() == 0 ? 6 : firstDayOfMonth.getDay()-1;

    firstDayOfMonth.setDate(firstDayOfMonth.getDate() - dayOfWeek);

    const d = new Date(firstDayOfMonth);
    for (i = 0; i < this.days.length; i++) {

        flag = d.getMonth() != this.monthSelected;
        
        this.days[i].updateDay(flag, d);
        
        if ((this.days[i].day < this.initialDate) || (this.finalDate && this.days[i].day > this.finalDate)) {
            this.days[i].activeDisabled(true);
        }
        if ((d.getFullYear() == this.selectedDay.getFullYear()) &&
            (d.getMonth() == this.selectedDay.getMonth()) &&
            (d.getDate() == this.selectedDay.getDate()) &&
            (!this.days[i].isDisabled)) {
            this.days[i].domNode.setAttribute('aria-selected', 'true');
        }
        d.setDate(d.getDate() + 1);
    }

    if ((dayOfWeek + daysInMonth) < 36) {
        this.hideLastRow();
    } else {
        this.showLastRow();
    }
};

DatePicker.prototype.updateYearGrid = function (newYear) {
    const year = newYear ? newYear : this.yearSelect;
    let initialYearFocus = this.initialDate ? this.initialDate.getFullYear() : 0;
    let finalYearFocus = this.finalDate ? this.finalDate.getFullYear() : 0;
    const yearEndCero = year - (year % 10);
    const modEnd = (yearEndCero % 100) % 4;
    let yearStart = modEnd == 0 ? yearEndCero : yearEndCero - 2;

    this.buttonsCellsYears.forEach(function (element, index) {
        element.innerHTML = yearStart + index;
        element.setAttribute('tabindex', '-1');
        element.setAttribute('data-date', yearStart + index);
        
        if (yearStart + index < initialYearFocus || (yearStart + index > finalYearFocus && finalYearFocus != 0)) {
            element.classList.add('disabled');
        } else {
            element.classList.remove('disabled');
        }
    });
    this.validateFocusYear(year);
}; 

DatePicker.prototype.validateFocusYear = function (newYear) {
    newYear =  this.initialDate || this.finalDate ? this.getYearFocus(newYear) : newYear;
    if (newYear) {
        this.yearSelect = newYear;
        this.focusDay = new Date(newYear, this.focusDay.getMonth(), this.focusDay.getDate());
        const button = this.yearsTableNode.querySelector("button[data-date='" + newYear + "']");
        button.setAttribute('tabindex', '0');
    }
}

DatePicker.prototype.getYearFocus = function (yearSelect) {
    const fd = this.focusDay;
    const year = yearSelect ? yearSelect : fd.getFullYear();
    const yearEndCero = year - (year % 10);
    const modEnd = (yearEndCero % 100) % 4;
    let yearStart = modEnd == 0 ? yearEndCero : yearEndCero - 2;
    let initialYearFocus = this.initialDate ? this.initialDate.getFullYear() : 0;
    let finalYearFocus = this.finalDate ? this.finalDate.getFullYear() : 0;

    if (year >= initialYearFocus && ((year <= finalYearFocus && finalYearFocus != 0) || finalYearFocus == 0)) {
        return year;
    } else if (yearStart + 11 >= finalYearFocus && finalYearFocus != 0 && yearStart <= initialYearFocus) {
        return finalYearFocus;
    } else if (yearStart <= initialYearFocus && yearStart + 11 >= initialYearFocus) {
        return initialYearFocus;
    } else if (year >= yearStart && year > finalYearFocus && finalYearFocus <= yearStart + 11 && year <= yearStart + 11 && yearStart < finalYearFocus) {
        return finalYearFocus;
    }
}

DatePicker.prototype.updateGridYear = function (yearSelect) {
    const fd = this.focusDay;
    const year = yearSelect ? yearSelect : fd.getFullYear();
    const yearEndCero = year - (year % 10);
    const modEnd = (yearEndCero % 100) % 4;
    let yearStart = modEnd == 0 ? yearEndCero : yearEndCero - 2;
    let yearEnd = yearStart + 11;

    this.MonthYearNode.innerHTML = yearStart + " - " + yearEnd;
};

DatePicker.prototype.updateGridTextMonth = function () {
    const fd = this.focusDay;
    this.MonthYearNode.innerHTML = fd.getFullYear();
};

DatePicker.prototype.handleMonthsYear = function () {
    if (!this.dateTableNode.classList.contains('d-none')) {
        this.showMonthsYear();
        this.updateGridTextMonth();
    } else if (!this.monthsTableNode.classList.contains('d-none')) {
        this.showYearsDecade();
        this.updateGridYear();
    }
}

DatePicker.prototype.showMonthsYear = function () {
    this.dateTableNode.classList.add('d-none');
    this.prevMonthNode.classList.add('d-none');
    this.nextMonthNode.classList.add('d-none');
    this.yearsTableNode.classList.add('d-none');
    this.monthsTableNode.classList.remove('d-none');
    this.prevYearNode.classList.remove('d-none');
    this.nextYearNode.classList.remove('d-none');
    this.prevDecadeNode.classList.add('d-none');
    this.nextDecadeNode.classList.add('d-none');
    this.viewMonths();
}

DatePicker.prototype.showYearsDecade = function () {
    this.dateTableNode.classList.add('d-none');
    this.monthsTableNode.classList.add('d-none');
    this.prevYearNode.classList.add('d-none');
    this.nextYearNode.classList.add('d-none');
    this.yearsTableNode.classList.remove('d-none');
    this.prevDecadeNode.classList.remove('d-none');
    this.nextDecadeNode.classList.remove('d-none');
}

DatePicker.prototype.showDaysMonth = function () {
    this.monthsTableNode.classList.add('d-none');
    this.yearsTableNode.classList.add('d-none');
    this.prevDecadeNode.classList.add('d-none');
    this.nextDecadeNode.classList.add('d-none');
    this.prevYearNode.classList.add('d-none');
    this.nextYearNode.classList.add('d-none');
    this.dateTableNode.classList.remove('d-none');
    this.prevMonthNode.classList.remove('d-none');
    this.nextMonthNode.classList.remove('d-none');
}

DatePicker.prototype.hideLastRow = function () {
    this.lastRowNode.style.display = 'none';
};

DatePicker.prototype.showLastRow = function () {
    this.lastRowNode.style.display = 'table-row';
};

DatePicker.prototype.setFocusDay = function (flag) {
    if (typeof flag !== 'boolean') {
        flag = true;
    }

    const fd = new Date(this.focusDay);
    let exists = 0;
    let dateAux = '';
    let contAux = 0;
    function checkDay(d) {
        contAux++;
        d.domNode.setAttribute('tabindex', '-1');

        const dS = new Date(fd);
        dS.setHours(0, 0, 0, 0);
        const dInitial = this.initialDate ? this.initialDate : dS;
        const dFinal = this.finalDate ? this.finalDate : dS;
        
        dateAux = this.getDateAux(d, dateAux);
        if ((d.day.getDate() == fd.getDate()) &&
            (d.day.getMonth() == fd.getMonth() && d.day.getMonth() == this.monthSelected) &&
            (d.day.getFullYear() == fd.getFullYear()) &&
            (dS >= dInitial && dS <= dFinal)) {
            this.editFocusDay(d, flag);
            exists = 1;
        }
    }

    this.days.forEach(checkDay.bind(this));
    if (exists == 0 && dateAux != '') {        
        dateAux.domNode.setAttribute('tabindex', '0');
    }
};

DatePicker.prototype.editFocusDay = function (d, flag) {
    d.domNode.setAttribute('tabindex', '0');
    //Control Meses
    const buttonMonth = this.monthsTableNode.querySelector("button[data-date='" + (d.day.getMonth() + 1) + "']");
    this.buttonsCellsMonths.forEach(function (e) {
        e.setAttribute('tabindex', '-1');
    }) 
    buttonMonth.setAttribute('tabindex', '0')
    //Control Años
    const buttonYear = this.yearsTableNode.querySelector("button[data-date='" + d.day.getFullYear() + "']");
    this.buttonsCellsYears.forEach(function (e) {
        e.setAttribute('tabindex', '-1');
    })
    
    if (buttonYear) {
        buttonYear.setAttribute('tabindex', '0');
    }

    if (flag) {
        d.domNode.focus();
    }
}

DatePicker.prototype.getDateAux = function (d, dateAux) {
    if (this.initialDate == '' && this.finalDate == '' && d.day.getDate() == 1 && d.day.getMonth() == this.monthSelected) {
        return d;
    } else if (d.day.getMonth() == this.monthSelected && dateAux == '') {
        if ((d.day >= this.initialDate && d.day <= this.finalDate)  ||
            (this.initialDate == '' && this.finalDate && d.day.getDate() == 1) ||
            (this.initialDate && this.finalDate == '' && d.day.getDate() == this.initialDate.getDate())) {
            return d;
        }
    }
    return dateAux;
}

DatePicker.prototype.setFocusMonth = function (flag) {

    if (typeof flag !== 'boolean') {
        flag = true;
    }
    const fd = this.focusDay;
    let buttonMonth = this.monthsTableNode.querySelector("button[data-date='" + (fd.getMonth() + 1) + "']");
    
    if (flag) {
        buttonMonth.focus();
    }
}

DatePicker.prototype.updateDay = function (day) {
    const d = this.focusDay;
    this.focusDay = day;
    if ((d.getMonth() !== day.getMonth()) ||
        (d.getFullYear() !== day.getFullYear())) {
        this.updateGrid();
        this.setFocusDay();
    }
};

DatePicker.prototype.getDaysInLastMonth = function () {
    const fd = this.focusDay;
    const lastDayOfMonth = new Date(fd.getFullYear(), fd.getMonth(), 0);
    return lastDayOfMonth.getDate();
};

DatePicker.prototype.getDaysInMonth = function () {
    const fd = this.focusDay;
    const lastDayOfMonth = new Date(fd.getFullYear(), fd.getMonth() + 1, 0);
    return lastDayOfMonth.getDate();
};

DatePicker.prototype.show = function () {
    const desplegable = this.inputNode.closest('.desplegable-selected-option');
    desplegable.classList.add('desplegable-arrow-active');
    this.dialogNode.style.display = 'block';
    this.dialogNode.style.zIndex = 1;

    this.getDateInput();
    this.monthSelected = this.focusDay.getMonth();
    this.yearSelect = this.focusDay.getFullYear();
    this.showDaysMonth();
    this.updateYearGrid();
    this.updateGridYear()
    this.updateGrid();
    this.setFocusDay();   
};

DatePicker.prototype.isOpen = function () {
    return window.getComputedStyle(this.dialogNode).display !== 'none';
};

DatePicker.prototype.hide = function () {
    const desplegable = this.inputNode.closest('.desplegable-selected-option');
    desplegable.classList.remove('desplegable-arrow-active');
    this.dialogNode.style.display = 'none';

    this.hasFocusFlag = false;
    this.dateInput.setFocus();
};

DatePicker.prototype.handleBackgroundMouseDown = function (event) {
    if (!this.inputNode.contains(event.target) &&
        !this.dialogNode.contains(event.target) &&
        !this.inputContainer.contains(event.target) &&
        !this.inputContainer.classList.contains('dont-close')) {

        this.isMouseDownOnBackground = true;

        if (this.isOpen()) {
            this.hide();
            event.stopPropagation();
            event.preventDefault();
        }
    }
};

DatePicker.prototype.handleBackgroundMouseUp = function () {
    this.isMouseDownOnBackground = false;
};

DatePicker.prototype.showMonthsYearNode = function (event) {
    let flag = false;

    switch (event.type) {
        case 'keydown':

            switch (event.keyCode) {
                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.setTextboxDate();
                    this.hide();
                    flag = true;
                    break;

                case this.keyCode.TAB:
                    if (!event.shiftKey) {
                        this.prevYearNode.focus();
                        flag = true;
                    }
                    break;

                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                default:
                    break;

            }
            break;

        case 'click':
            this.setTextboxDate();
            this.hide();
            flag = true;
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handleNextDecadeButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {
                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToNextDecade();
                    this.setFocusDay(false);
                    flag = true;
                    break;
            }

            break;

        case 'click':
            this.moveToNextDecade();
            this.setFocusDay(false);
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handlePreviousDecadeButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToPreviousDecade();
                    this.setFocusDay(false);
                    flag = true;
                    break;

                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                default:
                    break;
            }

            break;

        case 'click':
            this.moveToPreviousDecade();
            this.setFocusDay(false);
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handleNextYearButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {
                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToNextYear();
                    this.setFocusDay(false);
                    flag = true;
                    break;
            }

            break;

        case 'click':
            this.moveToNextYear();
            this.setFocusDay(false);
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handlePreviousYearButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToPreviousYear();
                    this.setFocusDay(false);
                    flag = true;
                    break;

                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                default:
                    break;
            }

            break;

        case 'click':
            this.moveToPreviousYear();
            this.setFocusDay(false);
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handleNextMonthButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {
                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToNextMonth();
                    this.setFocusDay(false);
                    flag = true;
                    break;
            }

            break;

        case 'click':
            this.moveToNextMonth();
            this.setFocusDay(false);
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handlePreviousMonthButton = function (event) {
    let flag = false;

    switch (event.type) {

        case 'keydown':

            switch (event.keyCode) {
                case this.keyCode.ESC:
                    this.hide();
                    flag = true;
                    break;

                case this.keyCode.ENTER:
                case this.keyCode.SPACE:
                    this.moveToPreviousMonth();
                    this.setFocusDay(false);
                    flag = true;
                    break;
            }

            break;

        case 'click':
            this.moveToPreviousMonth();
            this.setFocusDay(false);
            flag = true;
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.handleMonthButton = function (event) {
    if (!this.isDisabled(event.target)) {
        const buttonMonth = event.target;
        const month = buttonMonth.getAttribute('data-date');
        this.showDaysMonth();
        this.focusDay.setMonth(month - 1);
        this.focusDay.setDate(1);
        this.monthSelected = month - 1;
        this.buttonsCellsMonths.forEach(function (e) {
            e.setAttribute('tabindex', '-1');
        })
        buttonMonth.setAttribute('tabindex', '0');
        if (this.viewDays == 'true') {
            this.updateGrid();
        } else {
            this.setTextboxDate();
            this.hide();
        }
        this.setFocusDay(false);
    }
};

DatePicker.prototype.handleYearButton = function (event) {
    let buttonYear = event.target;
    if (!this.isDisabled(buttonYear)) {
        const year = buttonYear.getAttribute('data-date');
        this.yearSelect = parseInt(year);
        this.showMonthsYear();
        this.focusDay.setFullYear(year);
        this.MonthYearNode.innerHTML = year;
        this.updateYearGrid();
    }
};

DatePicker.prototype.moveToNextDecade = function () {
    this.yearSelect = this.yearSelect + 10;
    this.updateYearGrid(this.yearSelect);
    this.updateGridYear(this.yearSelect)
};

DatePicker.prototype.moveToPreviousDecade = function () {
    this.yearSelect = this.yearSelect - 10;
    this.updateYearGrid(this.yearSelect);
    this.updateGridYear(this.yearSelect)
};

DatePicker.prototype.moveToNextYear = function () {
    const fd = this.focusDay;
    this.MonthYearNode.innerHTML = fd.getFullYear() + 1;
    this.yearSelect = fd.getFullYear() + 1;
    this.viewMonths();
};

DatePicker.prototype.moveToPreviousYear = function () {
    const fd = this.focusDay;
    this.MonthYearNode.innerHTML = fd.getFullYear() - 1;
    this.yearSelect = fd.getFullYear() - 1;
    this.viewMonths();
};

DatePicker.prototype.viewMonths = function () {
    let initialMonthFocus = this.initialDate ? this.initialDate.getMonth() + 1 : 0;
    let finalMonthFocus = this.finalDate ? this.finalDate.getMonth() + 1 : 12;
    let initialYearFocus = this.initialDate ? this.initialDate.getFullYear() : 0;
    let finalYearFocus = this.finalDate ? this.finalDate.getFullYear() : 0;
    this.focusDay.setFullYear(this.yearSelect);
    for (const monthButton of this.MonthsNodes) {
        const monthCell = monthButton.getAttribute('data-date');

        if ((this.yearSelect < initialYearFocus) || 
            (this.yearSelect > finalYearFocus && finalYearFocus != 0) ||
            (this.yearSelect == initialYearFocus && monthCell < initialMonthFocus) || 
            (this.yearSelect == finalYearFocus && monthCell > finalMonthFocus)) {        
            monthButton.classList.add('disabled');
            monthButton.setAttribute('tabindex', '-1');
        } else if(this.focusDay.getMonth() + 1 == monthCell) {
            monthButton.setAttribute('tabindex', '0');
            monthButton.classList.remove('disabled');
        } else {
            monthButton.classList.remove('disabled');
        }
    }
}

DatePicker.prototype.moveToNextMonth = function () {
    if (this.monthSelected + 1 > 11) {
        this.monthSelected = 0;
        this.yearSelect = this.yearSelect + 1;
    } else {
        this.monthSelected = this.monthSelected + 1;
    }
    this.updateGrid();
};

DatePicker.prototype.moveToPreviousMonth = function () {
    if (this.monthSelected - 1 < 0) {
        this.monthSelected = 11;
        this.yearSelect = this.yearSelect - 1;
    } else {
        this.monthSelected = this.monthSelected - 1;
    }
    this.updateGrid();
};

DatePicker.prototype.moveFocusToDay = function (day) {
    if (day) {
        const d = this.focusDay;
        this.focusDay = day;
        this.monthSelected = this.focusDay.getMonth();
        this.yearSelect = this.focusDay.getFullYear();

        if ((d.getMonth() != this.focusDay.getMonth()) ||
            (d.getYear() != this.focusDay.getYear())) {
            this.updateGrid();
        }
        this.setFocusDay(false);
    }
};

DatePicker.prototype.moveFocusToNextDay = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() + 1);
    d = this.validateDay(this.focusDay, d);
    this.moveFocusToDay(d);
};

DatePicker.prototype.validateDay = function (date, dateNew) {
    const date1 = date;
    const date2 = dateNew;
    date1.setHours(0, 0, 0, 0);
    date2.setHours(0, 0, 0, 0);

    if ((date2 > date1 && (this.finalDate && this.finalDate < date2)) ||
        (date2 < date1 && (this.initialDate && this.initialDate > date2))) {
        return date;
    } else {
        return dateNew;
    }
}

DatePicker.prototype.moveFocusToNextWeek = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() + 7);
    d = this.validateDay(this.focusDay, d);
    this.moveFocusToDay(d);
};

DatePicker.prototype.moveFocusToPreviousDay = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() - 1);
    d = this.validateDay(this.focusDay, d);
    this.moveFocusToDay(d);
};

DatePicker.prototype.moveFocusToPreviousWeek = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() - 7);
    d = this.validateDay(this.focusDay, d);
    this.moveFocusToDay(d);
};

DatePicker.prototype.moveFocusToFirstDayOfWeek = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() - d.getDay());
    d = this.validateDay(this.focusDay, d);
    this.moveFocusToDay(d);
};

DatePicker.prototype.moveFocusToLastDayOfWeek = function () {
    let d = new Date(this.focusDay);
    d.setDate(d.getDate() + (6 - d.getDay()));
    this.moveFocusToDay(d);
};

DatePicker.prototype.setTextboxDate = function (day) {
    if (day) {
        this.dateInput.setDate(day);
    } else {
        this.dateInput.setDate(this.focusDay);
    }
};

DatePicker.prototype.getDateInput = function () {
    let parts = this.dateInput.getDate().split('/');

    if (this.viewDays == 'true' && (parts.length === 3) &&
        Number.isInteger(parseInt(parts[0])) &&
        Number.isInteger(parseInt(parts[1])) &&
        Number.isInteger(parseInt(parts[2]))) {
        this.focusDay = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
        this.selectedDay = new Date(this.focusDay);
    } else if(this.viewDays == 'false' && (parts.length === 2) &&
        Number.isInteger(parseInt(parts[0])) &&
        Number.isInteger(parseInt(parts[1]))) {
            this.focusDay = new Date(parseInt(parts[1]), parseInt(parts[0]) - 1, parseInt(1));
            this.selectedDay = new Date(this.focusDay);
    } else {
        // If not a valid date (DD/MM/YY) initialize with todays date
        this.focusDay = new Date();
        this.selectedDay = new Date(0, 0, 1);
    }
    let date1 = new Date(this.focusDay);
    if ((date1 < this.initialDate && this.initialDate) || date1 > this.finalDate && this.finalDate) {
        this.focusDay = date1 > this.finalDate && this.finalDate ? this.finalDate : this.initialDate;
        this.selectedDay = new Date(this.focusDay);
        this.inputNode.value = '';
    } else if (this.dateInput.getDate()) {
        this.dateInput.setDate(date1);
    }

    const fd = this.focusDay;
    const year = fd.getFullYear();     
    this.yearSelect = year;
    this.monthSelected = fd.getMonth();
};

DatePicker.prototype.getDateForButtonLabel = function (year, month, day) {
    if (typeof year !== 'number' || typeof month !== 'number' || typeof day !== 'number') {
        this.selectedDay = this.focusDay;
    } else {
        this.selectedDay = new Date(year, month, day);
    }

    let label = this.dayLabels[this.selectedDay.getDay()];
    label += ' ' + (this.selectedDay.getDate());
    label += ' ' + this.monthLabels[this.selectedDay.getMonth()];
    label += ', ' + this.selectedDay.getFullYear();
    return label;
};

DatePicker.prototype.moveFocusToNextMonth = function (num) {
    const d = new Date(this.focusDay.getFullYear(), this.focusDay.getMonth()+num, this.focusDay.getDate());
    this.moveFocusToMonth(d);
};

DatePicker.prototype.moveFocusToPreviousMonth = function (num) {
    const d = new Date(this.focusDay.getFullYear(), this.focusDay.getMonth()-num, this.focusDay.getDate());
    this.moveFocusToMonth(d);
};

DatePicker.prototype.moveFocusToMonth = function (day) {
    const d = this.focusDay;

    if ((d.getMonth() != day.getMonth()) ||
        (d.getYear() != day.getYear())) {
        this.updateGridMonth(day);
    }
    this.setFocusDay(false);
};

DatePicker.prototype.updateGridMonth = function (day) {
    const fd = day;
    let monthSelected = fd.getMonth()+1;
    let elementActive = false;
    let buttonMonth = this.monthsTableNode.querySelector("button[data-date='" + monthSelected + "']");  
    let refreshMonths = this.focusDay.getFullYear() != fd.getFullYear();
      
    if ((!this.isDisabled(buttonMonth)) || this.focusDay.getFullYear() != fd.getFullYear()) {
        this.yearSelect = fd.getFullYear();
        this.focusDay = day;
        elementActive = true;

        if (refreshMonths) {
            this.viewMonths();
        }
    }

    this.buttonsCellsMonths.forEach(function (e) {
        const month = e.getAttribute('data-date');
        if (elementActive) {
            e.setAttribute('tabindex', monthSelected == month ? '0' : '-1');
        }
    });
    this.updateGridTextMonth();
};

DatePicker.prototype.isDisabled = function (e) {
    return e.classList.contains('disabled');
};

DatePicker.prototype.selectedMonth = function () {
    for (const monthButton of this.MonthsNodes) {
        if (monthButton.getAttribute('tabindex') == '0') {
            const month = monthButton.getAttribute('data-date');
            this.monthSelected = month - 1;
            this.focusDay.setMonth(month - 1);
            this.focusDay.setDate(1);
        }
    }
}

DatePicker.prototype.handleKeyDownMonth = function(event) {
    let flag = false;
    let self = this;

    switch (event.keyCode) {

        case this.keyCode.ENTER:
        case this.keyCode.SPACE:            
            if (this.viewDays == 'true') {
                this.showDaysMonth();
                this.selectedMonth();
                this.updateGrid();
                this.setFocusDay(true);
            } else {
                this.setTextboxDate();
                this.hide();
            }
            flag = true;
            break;

        case this.keyCode.TAB:
            if (!event.shiftKey) {
                self.hide();
            }
            break;

        case this.keyCode.ESC:
            self.hide();
            flag = true;
            break;

        case this.keyCode.RIGHT:
            this.moveFocusToNextMonth(1);
            flag = true;
            break;

        case this.keyCode.LEFT:
            this.moveFocusToPreviousMonth(1);
            flag = true;
            break;

        case this.keyCode.DOWN:
            this.moveFocusToNextMonth(4);
            flag = true;
            break;

        case this.keyCode.UP:
            this.moveFocusToPreviousMonth(4);
            flag = true;
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.moveFocusToNextYear = function (num) {
    const newYear = this.yearSelect + num;
    this.moveFocusToYear(newYear);
};

DatePicker.prototype.moveFocusToPreviousYear = function (num) {
    const newYear = this.yearSelect - num;
    this.moveFocusToYear(newYear);
};

DatePicker.prototype.moveFocusToYear = function (newYear) {
    if (this.yearSelect != newYear) {
        this.updateGridYearSelected(newYear);
    }
};

DatePicker.prototype.updateGridYearSelected = function (newYear) {
    let initialYearFocus = this.initialDate ? this.initialDate.getFullYear() : 0;
    let finalYearFocus = this.finalDate ? this.finalDate.getFullYear() : 0;

    if (initialYearFocus <= newYear && ((finalYearFocus >= newYear && finalYearFocus != 0) || finalYearFocus == 0)) {
        let yearStart = this.buttonsCellsYears[0].getAttribute('data-date');
        let yearEnd = this.buttonsCellsYears[this.buttonsCellsYears.length-1].getAttribute('data-date');
        this.yearSelect = newYear;
    
        if (parseInt(yearEnd) < parseInt(newYear) || parseInt(yearStart) > parseInt(newYear)) {
            this.updateYearGrid();
            this.updateGridYear();
        }

        this.buttonsCellsYears.forEach(function (e) {
            const year = e.getAttribute('data-date');
            e.setAttribute('tabindex', parseInt(newYear) == parseInt(year) ? '0' : '-1');

            if (parseInt(newYear) == parseInt(year)) {
                e.focus();
            }
        });
    }
};

DatePicker.prototype.handleKeyDownYear = function(event) {
    let flag = false;

    switch (event.keyCode) {

        case this.keyCode.ENTER:
        case this.keyCode.SPACE:
            this.showMonthsYear();
            this.updateGridTextMonth();
            this.setFocusMonth(true);
            flag = true;
            break;

        case this.keyCode.TAB:
            if (!event.shiftKey) {
                this.hide();
            }
            break;

        case this.keyCode.ESC:
            this.hide();
            flag = true;
            break;

        case this.keyCode.RIGHT:
            this.moveFocusToNextYear(1);
            flag = true;
            break;

        case this.keyCode.LEFT:
            this.moveFocusToPreviousYear(1);
            flag = true;
            break;

        case this.keyCode.DOWN:
            this.moveFocusToNextYear(4);
            flag = true;
            break;

        case this.keyCode.UP:
            this.moveFocusToPreviousYear(4);
            flag = true;
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

DatePicker.prototype.getFormatDate = function (date) {
    let dateNew = '';

    if (date) {
        let parts = date.split('-');

        if ((parts.length === 3) &&
            Number.isInteger(parseInt(parts[0])) &&
            Number.isInteger(parseInt(parts[1])) &&
            Number.isInteger(parseInt(parts[2]))) {
            dateNew = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        }
    }
    return dateNew;
};

DatePicker = DatePicker || {};

CalendarContainerButtonInput = function (inputContainer, datepicker) {
    this.datepicker = datepicker;
    this.inputContainer = inputContainer;
}

CalendarContainerButtonInput.prototype.init = function () {
    this.inputContainer.addEventListener('click', this.handleClick.bind(this));
};

CalendarContainerButtonInput.prototype.handleClick = function () {
    this.monthSelected = '';
    this.yearSelect = '';
    if (!this.datepicker.isOpen()) {
        this.datepicker.show();
        if (this.datepicker.viewDays == "true") {
            this.datepicker.setFocusDay();
        } else{            
            this.datepicker.showMonthsYear();
            this.datepicker.updateGridTextMonth();
            this.datepicker.setFocusMonth(false);
        }
    } else {
        this.datepicker.hide();
    }

    this.datepicker.inputNode.focus();

    event.stopPropagation();
    event.preventDefault();
};

CalendarButtonInput = function (inputNode, datepicker) {
    this.inputNode = inputNode;
    this.imageNode = false;

    this.datepicker = datepicker;

    this.defaultLabel = 'Choose Date';

    this.keyCode = Object.freeze({
        'ENTER': 13,
        'SPACE': 32,
        'TAB': 9
    });
};

CalendarButtonInput.prototype.init = function () {
    this.inputNode.addEventListener('keydown', this.handleKeyDown.bind(this));
    this.inputNode.addEventListener('focus', this.handleFocus.bind(this));
};

CalendarButtonInput.prototype.handleKeyDown = function (event) {
    let flag = false;

    switch (event.keyCode) {
        case this.keyCode.SPACE:
        case this.keyCode.ENTER:
            this.datepicker.show();
            if (this.datepicker.viewDays == 'true') {
                this.datepicker.setFocusDay();
            } else {
                this.datepicker.showMonthsYear();
                this.datepicker.updateGridTextMonth();
                this.datepicker.setFocusMonth(false);
            }
            flag = true;
            break;
        case this.keyCode.TAB:
            if (event.shiftKey) {
                this.datepicker.hide();
            }
            break;

        default:
            break;
    }

    if (flag) {
        event.stopPropagation();
        event.preventDefault();
    }
};

CalendarButtonInput.prototype.setLabel = function (str) {
    if (typeof str === 'string' && str.length) { str = ', ' + str; }
    this.inputNode.setAttribute('aria-label', this.defaultLabel + str);
};

CalendarButtonInput.prototype.setFocus = function () {
    this.inputNode.focus();
};

CalendarButtonInput.prototype.setDate = function (day) {
    this.inputNode.value = (this.datepicker.viewDays == "true" ? formatNumber(day.getDate()) + '/' : '') + formatNumber((day.getMonth() + 1)) + '/' + day.getFullYear();
};

function formatNumber(number) {
    return number <= 9 ? '0' + number : number;
}

CalendarButtonInput.prototype.getDate = function () {
    return this.inputNode.value;
};

CalendarButtonInput.prototype.getDateLabel = function () {
    let label = '';
    const parts = this.inputNode.value.split('/');

    if ((parts.length === 3) &&
        Number.isInteger(parseInt(parts[0])) &&
        Number.isInteger(parseInt(parts[1])) &&
        Number.isInteger(parseInt(parts[2]))) {
        const day = parseInt(parts[0]) - 1;
        const month = parseInt(parts[1]);
        const year = parseInt(parts[2]);

        label = this.datepicker.getDateForButtonLabel(year, month, day);
    }
    return label;
};

CalendarButtonInput.prototype.handleFocus = function () {
    const dateLabel = this.getDateLabel();

    if (dateLabel) {
        this.setLabel('selected date is ' + dateLabel);
    } else {
        this.setLabel('');
    }
};
/* ================================= fin Desplegables =============================== */

// INDICADOR DE CARGA
function showSpinner(show) {
  const spinner = document.querySelector('.spinner-modal-govco');
  const ariaHidden = !show;
  const display = show ? "flex" : "none";
  spinner.style.display = display;
  spinner.setAttribute("aria-hidden", ariaHidden);

  if(show) {
    const backdrop = document.createElement('div');
    backdrop.classList.add('modal-backdrop', 'fade', 'show', 'backdrop-govco');
    document.body.appendChild(backdrop);
  } else {
    const element = document.querySelector('.backdrop-govco');
    document.body.removeChild(element);
  }
}

// CARGA DE ARCHIVO
/**
 * Metodo establecido por el usuario en el atributo data-action del input file
   function uploadFile(files) {
    // files es un arreglo con el o los archivos seleccionados por el usuario
    return new Promise(function(resolve, reject) {
      // Aquí agregar la lógica para procesar los archivos

      if(true) {
        // filesLoadedSuccesfully es un arreglo con o los archivos que se procesaron correctamente
        const filesLoadedSuccesfully = files;
        resolve(filesLoadedSuccesfully);
      } else {
        reject('Ocurrió un error al cargar los archivos.');
      }
    });
  }
*/

/**
 * Metodo establecido por el usuario en el atributo data-action-delete del input file
   function deleteFile(file) {
    // file contiene el archivo seleccionado por el usuario para eliminar
    return new Promise(function(resolve, reject) {
      // Aquí agregar la lógica para procesar el archivo

      if(true) {
        resolve(true);
      } else {
        reject('Ocurrió un error al procesar el archivo.');
      }
    });
  }
*/

/**
 * Método que el usuario debe llamar para la configuracion inicial, enviando los parametros correspondientes
 * setValidationParameters(inputId, allowedExtensions, sizeInBytes, maxQuantityOfFiles)
 * setValidationParameters('inputFile', ['jpg', 'pdf', 'png'], 500000, 4);
 */
var attachmentList = {};
var selectedFiles = {};
var configFileInputs = {};
var fileUploadMessageTypes = {
  extensionNotAllowed: 'Formato de archivo no permitido.',
  sizeNotAllowed: 'El archivo excede el tamaño máximo permitido.',
  extensionsNotAllowed: 'Uno de los archivos tiene un formato no admitido.',
  sizesNotAllowed: 'Uno de los archivos excede el tamaño máximo permitido.',
  noFile: 'Seleccione un documento para adjuntar.',
  maximumQuantityExceeded: 'Supera la cantidad de archivos permitida.'
};

function methodAssignFileInput(e, f, a) {
  for(let i of a) {
    i.addEventListener(e, f, false);
    attachmentList[i.id] = [];
    selectedFiles[i.id] = [];
    configFileInputs[i.id] = {
      'validExtensions': [],
      'validSize': 0,
      'maximumQuantity': 1
    };
  }
}

function windowSize() {
  let containerInputsFile = document.querySelectorAll('.container-carga-de-archivo-govco');
  for(let e of containerInputsFile) {
    if (e.offsetWidth < 340) {
      e.classList.add('responsive-govco','small-fileupload-govco');
    } else if (e.offsetWidth < 652) {
      e.classList.add('responsive-govco');
      e.classList.remove('small-fileupload-govco');
    } else {
      e.classList.remove('responsive-govco', 'small-fileupload-govco');
    }
  }
}

function selectingFiles()  {
  const idInput = this.id;
  const parent = this.parentNode;
  const parentCarga = parent.parentNode;
  const button = parentCarga.querySelector('.button-loader-carga-de-archivo-govco');
  let fileName = '';
  const maximumQuantity = configFileInputs[idInput]['maximumQuantity'];
  selectedFiles[idInput] = [];
  errorInputFile(this, false, '');

  if ((attachmentList[idInput].length + this.files.length) > maximumQuantity) {
    errorInputFile(this, true, fileUploadMessageTypes.maximumQuantityExceeded);
    if (attachmentList[idInput].length >= maximumQuantity) {
      this.disabled = true;
      this.classList.remove("active");
    }
  } else if (this.files.length > 0) {
    let validations = validateFiles(this.files, idInput);

    if (validations.invalidExtensions === 0 && validations.invalidSize === 0) {
      if (selectedFiles[idInput] && selectedFiles[idInput].length > 1) {
        fileName = selectedFiles[idInput].length + ' archivos seleccionados';
      } else {
        fileName = selectedFiles[idInput][0].name;
      }
      assignName(parent, fileName);  
      enableDisableFileUploadButton(button, false);
    } else {
      selectedFiles[idInput] = [];
      const textError = validationErrortext(validations.invalidExtensions, validations.invalidSize);
      errorInputFile(this, true, textError);
    }
  } else {
    errorInputFile(this, true, fileUploadMessageTypes.noFile);
    cleanInputfile(this, parent);
    enableDisableFileUploadButton(button, true);
  }
}

function validationErrortext(extensions, size) {
  let textoError = '';
  if (extensions > 0) {
    if (extensions > 1) {
      textoError = fileUploadMessageTypes.extensionNotAllowed;
    } else {
      textoError = fileUploadMessageTypes.extensionsNotAllowed;
    }
  }
  
  if (size > 0) {
    if (size > 1) {
      textoError += ' ' + fileUploadMessageTypes.sizeNotAllowed;
    } else {
      textoError += ' ' + fileUploadMessageTypes.sizesNotAllowed;    
    }
  }

  return textoError;
}

function validateFiles(files, idInput) {
  let invalidExtensions = 0;
  let invalidSize = 0;
  let listValidExtensions = configFileInputs[idInput]['validExtensions'];
  let validSize = configFileInputs[idInput]['validSize'];

  if (listValidExtensions.length > 0 || validSize > 0) {
    [...files].forEach(function(element) {
      const extensionFile = element.name.split('.').pop().toLowerCase();
      if (listValidExtensions && Array.isArray(listValidExtensions) && listValidExtensions.includes(extensionFile)) {
        if (validSize >= element.size) {
          selectedFiles[idInput].push(element);
        } else {
          invalidSize++;
        }
      } else {
        invalidExtensions++;
      }
    });
  } else {
    selectedFiles[idInput].push(...files);
  }

  return {
    'invalidExtensions': invalidExtensions,
    'invalidSize': invalidSize
  }
}

function setValidationParameters(idElement, extensions = [], size = 0, quantity = 1) {
  /**
    * extensions es un arreglo de strings con las extensiones validas 
      extensions = ['jpg', 'pdf', 'png'];
    * size es el tamaño maximo de archivo en bytes 
    * quantity es la cantidad maxima de archivos subidos
  */

  const validExtensions = extensions.map(e => e.toString().toLowerCase());
  const validSize = !isNaN(size) ? parseInt(size) : 0;
  const maximumQuantity = !isNaN(quantity) ? parseInt(quantity) : 0;

  configFileInputs[idElement] = {
    'validExtensions': validExtensions,
    'validSize': validSize,
    'maximumQuantity': maximumQuantity
  };
}

function assignName(container, fileName) {
  const containerNameFile = container.querySelector('.file-name-carga-de-archivo-govco');
  containerNameFile.innerHTML = fileName ? fileName : 'Sin archivo seleccionado';
}

function enableDisableFileUploadButton(element, value) {
  element.disabled = value;
}

async function clickButtonFile() {
  const parentButton = this.parentNode;
  const parent = parentButton.parentNode;
  const inputFile = parent.querySelector('.input-carga-de-archivo-govco');
  const idInput = inputFile.id;
  const containerLoader = parent.querySelector('.load-carga-de-archivo-govco');
  activateContainerLoader(containerLoader, true);
  enableDisableFileUploadButton(this, true);
  
  if (inputFile.value) {
    const containerParent = parent.nextElementSibling;
    const container = containerParent.querySelector('.attached-files-carga-de-archivo-govco');

    const filesResponse = await validateFunction(inputFile, containerLoader, parent, 'add', selectedFiles[idInput]);
    if(filesResponse.length > 0) {
      createAttachedFiles(container, filesResponse, idInput);
      
      containerParent.style.display = attachmentList[idInput].length > 0 ? 'block' : 'none';

      if (attachmentList[idInput].length >= configFileInputs[idInput]['maximumQuantity']) {
        inputFile.disabled = true;
        inputFile.classList.remove("active");
      }
    }
    cleanInputfile(inputFile, parent);
  } else {
    errorInputFile(inputFile, true, fileUploadMessageTypes.noFile);
    activateContainerLoader(containerLoader, false);
  }
}

async function validateFunction(inputFile, containerLoader, parent, action, listFiles) {
  const nameDataAction = action == 'add' ? 'data-action' : 'data-action-delete';
  const nameMethod = inputFile.getAttribute(nameDataAction);
  let method;
  let answer = {};

  if(!nameMethod) {
    console.error(nameDataAction + ' method is not defined');
  }

  try {
    method = new Function('return ' + nameMethod)();
  } catch (error) {
    console.error(nameDataAction + ' method is not defined');
  }

  if(method) {
    try {
      answer = await method(listFiles);
      activateContainerLoader(containerLoader, false);  
      if(action == 'delete') return true;
    } catch (error) {
      errorInputFile(inputFile, true, error);
      activateContainerLoader(containerLoader, false);
      cleanInputfile(inputFile, parent);
      if(action == 'delete') return false;
    }
  } else {
    answer = listFiles;
    activateContainerLoader(containerLoader, false);  
    if(action == 'delete') return true;
  }
  return answer;
}

function errorInputFile(input, active, message) {
  const parentInput = input.parentNode;
  const parentAllInput = parentInput.parentNode;
  const containerParent = parentAllInput.nextElementSibling;
  const containerAlert = containerParent.querySelector('.alert-carga-de-archivo-govco');
  const idInput = input.id;
  input.setAttribute('data-error', active ? "1" : "0");
  activateContainer(containerAlert, active);
  containerAlert.innerHTML = message;

  if (attachmentList[idInput].length <= 0) {
    containerParent.style.display = active ? 'block' : 'none';
  }
}

function activateContainer(container, active) {
  if (active) {
    container.classList.remove('visually-hidden');
  } else {
    container.classList.add('visually-hidden');
  }
}

function activateContainerLoader(container, active) {
  if (active) {
    container.style.visibility = "visible";
  } else {
    container.style.visibility = "hidden";
  }
}

function cleanInputfile(inputFile, container) {
  inputFile.value = '';
  assignName(container, '');
}

function createAttachedFiles(container, files, idInput) {
  files.forEach(function (value) {
    const size = formatterSizeUnit(value.size);
    createElement(container, value.name, size);
    attachmentList[idInput].push(value);
  });

  return true;
}

function createElement(container, name, type) {
  const newDivAttached = document.createElement('div');
  newDivAttached.classList.add('attached-file-carga-de-archivo-govco');
  newDivAttached.setAttribute("tabindex", "0");

  const newDivContainerIcon = document.createElement('div');
  newDivContainerIcon.classList.add('icon-text-carga-de-archivo-govco');

  const newDivIconFile = document.createElement('div');
  newDivIconFile.classList.add('file-alt-carga-de-archivo-govco');

  const newDiv = document.createElement('div');
  newDiv.classList.add('container-text-name-carga-de-archivo-govco');
  const span1 = document.createElement('span');
  span1.classList.add('text-name-carga-de-archivo-govco');
  const textSpan1 = document.createTextNode(name);
  span1.appendChild(textSpan1); //añade texto al div creado.
  const span2 = document.createElement('span');
  const textSpan2 = document.createTextNode(type);
  span2.appendChild(textSpan2); //añade texto al div creado.

  const newButtonIconTrash = document.createElement('button');
  newButtonIconTrash.classList.add('trash-alt-1-carga-de-archivo-govco');

  newDivContainerIcon.appendChild(newDivIconFile);
  newDiv.appendChild(span1);
  newDiv.appendChild(span2);
  newDivContainerIcon.appendChild(newDiv);
  newDivAttached.appendChild(newDivContainerIcon);
  newDivAttached.appendChild(newButtonIconTrash);
  container.appendChild(newDivAttached);
  
  newButtonIconTrash.addEventListener("click", removeAttachment, false);
}

function formatterSizeUnit(size) {
  if (size) {
    var result = parseInt(size);
    if (result < 1024) {
      return result + "bytes";
    } else if (result < 1024 * 1024) {
      return parseInt(result / 1024) + "KB";
    } else if (result < 1024 * 1024 * 1024) {
      return parseInt(result / (1024 * 1024)) + "MB";
    } else {
      return parseInt(result / (1024 * 1024 * 1024)) + "GB";
    }
  }
}

async function removeAttachment() {
  const container = this.parentNode;
  const containerAttachments = container.parentNode;
  const containerDetail = containerAttachments.parentNode;
  const containerAll = containerDetail.parentNode;
  const inputFile = containerAll.querySelector('.input-carga-de-archivo-govco');
  const idInput = inputFile.id;
  const containerLoader = containerAll.querySelector('.load-carga-de-archivo-govco');
  const containerName = container.querySelector('.text-name-carga-de-archivo-govco');
  const name = containerName.innerHTML;
  const element = attachmentList[idInput].find( file => file.name === name );
  const index = attachmentList[idInput].indexOf( element );

  activateContainerLoader(containerLoader, true);

  if (index >= 0) {
    const responseRemove = await validateFunction(inputFile, containerLoader, containerAll, 'delete', element);
  
    if (responseRemove === true) {
      let parent = container.parentNode
      attachmentList[idInput].splice( index, 1 );
      parent.removeChild(container);
      if (attachmentList[idInput].length < configFileInputs[idInput]['maximumQuantity']) {
        inputFile.disabled = false;
        inputFile.classList.add("active");
      }        
      containerDetail.style.display = attachmentList[idInput].length > 0 ? 'block' : 'none';
    }
  } else {
    activateContainerLoader(containerLoader, false);
  }
}


/* ==================================== carrusel ==================================== */
var carousels = document.querySelectorAll('.carrusel-govco');
var configurationCarousel = {};
for (const carousel of carousels) {
  configurationCarousel[carousel.id] = new bootstrap.Carousel(carousel, {
    pause: false
  });
  
  var controlsCarrusel = carousel.querySelectorAll('.control-start-pause .controls');
  methodAssign("click", startStopCarousel, controlsCarrusel);
  
  carousel.addEventListener('slid.bs.carousel', function () {
    this.querySelectorAll('.carousel-item:not(.active) a').forEach(x => x.tabIndex = -1);
    this.querySelectorAll('.carousel-item.active a').forEach(x => x.tabIndex = 0);
  });
}

function windowSizeCarrusel() {
  let containerInputsFile = document.querySelectorAll('.carrusel-govco');
  for (let e of containerInputsFile) {
    if (e.offsetWidth < 652) {
      e.classList.add('responsive-carrusel-govco');
    } else {
      e.classList.remove('responsive-carrusel-govco');
    }
  }
}

function startStopCarousel() {
  const container = this.parentNode.parentNode;
  this.classList.remove("active");

  if (this.classList.contains("start")) {
    this.nextElementSibling.classList.add("active");
    configurationCarousel[container.id].cycle();
  } else {
    this.previousElementSibling.classList.add("active");
    configurationCarousel[container.id].pause();
  }
}
/* ==================================== fin carrusel ==================================== */

/* ============================= login ===================================================*/


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

  }else if(expresionRegularE.test(this.value) === false) {
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

/* ============================= fin login ===================================================*/


/* =================================== linea de avance ============================================*/
function initAdvanceLine() {
	initDropDown();
  
	// Línea de avance de interacción horizontal
	const headerHorizontalAdvanceLine = document.querySelectorAll('.ih-linea-avance-govco .header-linea-avance-govco');
	const indicatorHorizontalAdvanceLine = document.querySelectorAll('.ih-linea-avance-govco .indicator-linea-avance-govco');
	methodAssign("click", clickHorizontalAdvanceLine, indicatorHorizontalAdvanceLine);
	assignInitialClassMovilHAdvanceLine(headerHorizontalAdvanceLine);
  
	// Línea de avance de interacción vertical
	const verticalAdvanceLine = document.querySelectorAll('.iv-linea-avance-govco .indicator-linea-avance-govco');
	methodAssign("click", clickVerticalAdvanceLine, verticalAdvanceLine);
}
  
function getActiveItemAdvanceLine(headers) {
	let x = 0;
	headers.forEach((element, index) => {
	  if (element.classList.contains("active-linea-avance-govco")){
		x = index;
	  }
	});
	return x;
}
  
function updateProgressAdvanceLine(headers, indexActive, elementParent, wh) {
    // porcentajes establecidos
    const porcent = [15, 50, 80, 100];
    const elementActive = headers[indexActive];
    const elementIndicator = elementActive.querySelector('.indicator-linea-avance-govco');
    const attributePorcentage = elementIndicator.getAttribute('percentage');
    const percentage = attributePorcentage ? attributePorcentage : porcent[indexActive];
    const elementProgress = elementParent.querySelector('.progress-bar');
    elementProgress.style[wh] = percentage + "%";
    elementProgress.setAttribute('aria-valuenow', percentage);
}
  
// Línea de avance horizontal
function nextItemAdvanceLineHorizontal(e) {
	const bodyActive = e.target.closest('.body-linea-avance-govco');
	const idParent = bodyActive.getAttribute('data-la-parent');
	const elementParent = document.querySelector(idParent);
	const headers = elementParent.querySelectorAll('.header-linea-avance-govco');
	const bodys = elementParent.querySelectorAll('.body-linea-avance-govco');
  
	const indexActive = getActiveItemAdvanceLine(headers);
	headers[indexActive].classList.remove('active-linea-avance-govco');
	headers[indexActive].classList.add('inactive-linea-avance-govco');
	headers[indexActive + 1].classList.add('active-linea-avance-govco');
	bodys[indexActive].classList.remove('active-linea-avance-govco');
	bodys[indexActive + 1].classList.add('active-linea-avance-govco');
  
    updateProgressAdvanceLine(headers, indexActive + 1, elementParent, 'width');
}
  
// Línea de avance vertical
function nextItemAdvanceLineVertical(e) {
	const bodyActive = e.target.closest('.body-linea-avance-govco');
	const idParent = bodyActive.getAttribute('data-la-parent');
	const elementParent = document.querySelector(idParent);
	const items = elementParent.querySelectorAll('.item-linea-avance-govco');
  
	const indexActive = getActiveItemAdvanceLine(items);
	items[indexActive].closest('.item-linea-avance-govco').classList.remove('active-linea-avance-govco');
	items[indexActive].closest('.item-linea-avance-govco').classList.add('inactive-linea-avance-govco');
	items[indexActive + 1].closest('.item-linea-avance-govco').classList.add('active-linea-avance-govco');
  
    updateProgressAdvanceLine(items, indexActive + 1, elementParent, 'height');
}
  
// Línea de avance de interacción horizontal
function nextItemAdvanceLineIHorizonal(e) {
	const bodyActive = e.target.closest('.body-linea-avance-govco');
	const idParent = bodyActive.getAttribute('data-la-parent');
	const elementParent = document.querySelector(idParent);
	const headers = elementParent.querySelectorAll('.header-linea-avance-govco');
	const bodys = elementParent.querySelectorAll('.body-linea-avance-govco');
  
	const indexActive = getActiveItemAdvanceLine(headers);
	headers[indexActive].classList.remove('active-linea-avance-govco');
	headers[indexActive].classList.add('checked-linea-avance-govco');
	headers[indexActive + 1].classList.add('active-linea-avance-govco');
	headers[indexActive + 1].classList.remove('checked-linea-avance-govco');
	bodys[indexActive].classList.remove('active-linea-avance-govco');
	bodys[indexActive + 1].classList.add('active-linea-avance-govco');
  
    updateProgressAdvanceLine(headers, indexActive + 1, elementParent, 'width');
	assignClassMovilHAdvanceLine(headers, indexActive);
}
  
function clickHorizontalAdvanceLine(e) {
	const element = e.target;
	const elementParent = element.parentNode;
	if (elementParent.classList.contains('checked-linea-avance-govco')) {
	  const idBody = element.getAttribute('data-la-target');
	  const container = e.target.closest('.ih-linea-avance-govco');
	  const headers = container.querySelectorAll('.header-linea-avance-govco');
	  const headerActive = container.querySelector('.header-linea-avance-govco.active-linea-avance-govco');
	  const bodyActive = container.querySelector('.body-linea-avance-govco.active-linea-avance-govco');
	  const indexActiveOld = getActiveItemAdvanceLine(headers);
	  headerActive.classList.remove('active-linea-avance-govco');
	  headerActive.classList.add('checked-linea-avance-govco');
	  bodyActive.classList.remove('active-linea-avance-govco');
  
	  elementParent.classList.remove('checked-linea-avance-govco');
	  elementParent.classList.add('active-linea-avance-govco');
	  container.querySelector(idBody).classList.add('active-linea-avance-govco');
  
	  const indexActive = getActiveItemAdvanceLine(headers);
      updateProgressAdvanceLine(headers, indexActive, container, 'width');
	  assignClickMovilHAdvanceLine(headers, indexActive, indexActiveOld);
	}
}
  
function assignInitialClassMovilHAdvanceLine(elements) {
	elements[0].classList.add('movil-linea-avance-govco');
	elements[1].classList.add('movil-linea-avance-govco');
	elements[2].classList.add('movil-linea-avance-govco');
}
  
function assignClassMovilHAdvanceLine(headers, indexActive) {
	if (indexActive > 0 && (indexActive + 2) < headers.length) {
	  cleanClassMovilHAdvanceLine(headers);
	  headers[indexActive].classList.add('movil-linea-avance-govco');
	  headers[indexActive + 1].classList.add('movil-linea-avance-govco');
	  headers[indexActive + 2].classList.add('movil-linea-avance-govco');
	}
}
  
function assignClickMovilHAdvanceLine(headers, indexActive, indexActiveOld) {
	if (indexActive > 0 && (indexActive + 1) < headers.length) {
	  cleanClassMovilHAdvanceLine(headers);
	  console.log(indexActive, indexActiveOld)
	  headers[indexActive].classList.add('movil-linea-avance-govco');
	  headers[indexActiveOld].classList.add('movil-linea-avance-govco');
	  const indexNew = indexActiveOld > indexActive ? indexActive - 1 : indexActive + 1;
	  headers[indexNew].classList.add('movil-linea-avance-govco');
	}
}
  
function cleanClassMovilHAdvanceLine(headers) {
	headers.forEach(element => element.classList.remove('movil-linea-avance-govco'));
}
  
// Línea de avance de interacción vertical
function nextItemAdvanceLineIVertical(e) {
	const bodyActive = e.target.closest('.body-linea-avance-govco');
	const idParent = bodyActive.getAttribute('data-la-parent');
	const elementParent = document.querySelector(idParent);
	const items = elementParent.querySelectorAll('.item-linea-avance-govco');
  
	const indexActive = getActiveItemAdvanceLine(items);
	items[indexActive].closest('.item-linea-avance-govco').classList.remove('active-linea-avance-govco');
	items[indexActive].closest('.item-linea-avance-govco').classList.add('checked-linea-avance-govco');
	items[indexActive + 1].closest('.item-linea-avance-govco').classList.add('active-linea-avance-govco');
	items[indexActive + 1].closest('.item-linea-avance-govco').classList.remove('checked-linea-avance-govco');
  
    updateProgressAdvanceLine(items, indexActive + 1, elementParent, 'height');
}
  
function clickVerticalAdvanceLine(e) {
	const element = e.target;
	const elementParent = element.closest('.item-linea-avance-govco');
	if (elementParent.classList.contains('checked-linea-avance-govco')) {
	  const container = e.target.closest('.iv-linea-avance-govco');
	  const items = container.querySelectorAll('.item-linea-avance-govco');
	  const itemActive = container.querySelector('.item-linea-avance-govco.active-linea-avance-govco');
  
	  itemActive.classList.remove('active-linea-avance-govco');
	  itemActive.classList.add('checked-linea-avance-govco');
  
	  elementParent.classList.remove('checked-linea-avance-govco');
	  elementParent.classList.add('active-linea-avance-govco');
  
	  const indexActive = getActiveItemAdvanceLine(items);
      updateProgressAdvanceLine(items, indexActive, container, 'height');
	}
}
/* ================================= fin linea de avance ==========================================*/


/* ============================ Cabecera =============================== */
function initHeader() {
	initMenu();
}
/* ============================ fin Cabecera =============================== */
