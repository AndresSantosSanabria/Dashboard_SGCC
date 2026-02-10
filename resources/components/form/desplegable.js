/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Desplegables
 *  - Version: 4.0.0
 */
// all
window.addEventListener("load", function() {
    initDropDown();
});

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

var DatePickerDay = function (domNode, datepicker, index, row, column) {

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

var CalendarButtonInput = {};
var CalendarContainerButtonInput = {};

var DatePicker = function (inputNode, dialogNode, inputContainer) {
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
