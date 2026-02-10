function selectedOption(activeItem, disabledItem) {
	const options = document.querySelectorAll('.aservice-item-govco');
	options.forEach((element) => {
		element.classList.remove('selected');
		element.classList.remove('disabled');
	});
	document.getElementById(activeItem).classList.add('selected');
	document.getElementById(disabledItem).classList.add('disabled');
	document
		.getElementById('alerta-service')
		.setAttribute('style', 'display: block');
	document
		.getElementById('comentarios1-button')
		.setAttribute('style', 'display: block');
	options.forEach((element) => {
		element.setAttribute('tabindex', '-1');
	});
}

function verComentarios() {
	const options = document.querySelectorAll('.aservice-item-govco');
	options.forEach((element) => {
		element.classList.add('disabled');
	});
	document
		.getElementById('alerta-service')
		.setAttribute('style', 'display: none');
	document
		.getElementById('comentarios1-button')
		.setAttribute('style', 'display: none');
	document
		.getElementById('aservice-comentarios')
		.setAttribute('style', 'display: block');
	document
		.getElementById('comentarios2-button')
		.setAttribute('style', 'display: block');
	document.getElementById('aservice-comentarios-textarea').focus();
	document.getElementById('aservice-comentarios-textarea').value = '';
}

function contadorTextArea() {
	document.getElementById('aservice-comentarios-textarea').onkeyup = (e) => {
		if (e.target.value.length >= 10) {
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.remove('errorTextArea');
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.add('successTextArea');
			document
				.getElementById('aservice-comentarios-alert')
				.setAttribute('style', 'display: none');
			document
				.getElementById('comentarios2-button-item')
				.removeAttribute('disabled');
		} else if (e.target.value.length == 0) {
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.remove('errorTextArea');
			document
				.getElementById('aservice-comentarios-alert')
				.setAttribute('style', 'display: none');
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.remove('successTextArea');
			document
				.getElementById('comentarios2-button-item')
				.setAttribute('disabled', 'true');
		} else {
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.remove('successTextArea');
			document
				.getElementById('aservice-comentarios-textarea')
				.classList.add('errorTextArea');
			document
				.getElementById('aservice-comentarios-alert')
				.setAttribute('style', 'display: block');
			document
				.getElementById('comentarios2-button-item')
				.setAttribute('disabled', 'true');
		}
	};
}

function enviarComentarios() {
	document
		.getElementById('aservice-comentarios')
		.setAttribute('style', 'display: none');
	document
		.getElementById('comentarios2-button')
		.setAttribute('style', 'display: none');
	document
		.getElementById('alerta-service')
		.setAttribute('style', 'display: block');
	document
		.getElementById('alerta-service')
		.setAttribute('style', 'margin-bottom: 0px !important;');
}
