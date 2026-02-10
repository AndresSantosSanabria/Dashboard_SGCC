// all
window.addEventListener("load", function() {
  initAdvanceLine();
});

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

function methodAssign(e, f, a) {
  for (let i of a) {
    i.addEventListener(e, f, false);
  }
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


// Desplegables
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
