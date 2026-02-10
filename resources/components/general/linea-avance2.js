/* - - - - - - - - - - - EJEMPLO HORIZONTAL - - - - - - - - - - */

function LineaAvenceHorizontal() {
	var PrimerItem = document.getElementById('indicadorlinea1');
	var SegundoItem = document.getElementById('indicadorlinea2');
	var TercerItem = document.getElementById('indicadorlinea3');
	var CuartaItem = document.getElementById('indicadorlinea4');
	var PrimerIndicador = document.getElementById('numeroindicador1');
	var SegundoIndicador = document.getElementById('numeroindicador2');
	var TercerIndicador = document.getElementById('numeroindicador3');
	var CuartaIndicador = document.getElementById('numeroindicador4');
	var PrimerLetra = document.getElementById('letra1');
	var SegundoLetra = document.getElementById('letra2');
	var TercerLetra = document.getElementById('letra3');
	var CuartaLetra = document.getElementById('letra4');
	var lineavance = document.getElementById('lineavance').style.width;

	if (window.matchMedia('(min-width: 990px)').matches) {
		if (lineavance == '15%') {
			document.getElementById('letra2').style.display = 'block';
			document.getElementById('lineavance').style.width = '49%';
			if (
				PrimerItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				PrimerItem.classList.remove('indicador-linea-avance-activo-govco');
				PrimerItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (PrimerLetra.classList.contains('letra-indicador-activo')) {
				PrimerLetra.classList.remove('letra-indicador-activo');
				PrimerLetra.classList.toggle('letra-indicador-terminado');
			}
			if (
				SegundoItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				SegundoItem.classList.remove('indicador-linea-avance-inactivo-govco');
				SegundoItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (SegundoIndicador.classList.contains('num-indicador-inactivo-govco')) {
				SegundoIndicador.classList.remove('num-indicador-inactivo-govco');
				SegundoIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (SegundoLetra.classList.contains('letra-indicador-inactivo')) {
				SegundoLetra.classList.remove('letra-indicador-inactivo');
				SegundoLetra.classList.toggle('letra-indicador-activo');
			}
		}
		if (lineavance == '49%') {
			document.getElementById('lineavance').style.width = '80%';
			if (
				SegundoItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				SegundoItem.classList.remove('indicador-linea-avance-activo-govco');
				SegundoItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (SegundoLetra.classList.contains('letra-indicador-activo')) {
				SegundoLetra.classList.remove('letra-indicador-activo');
				SegundoLetra.classList.toggle('letra-indicador-terminado');
			}
			if (
				TercerItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				TercerItem.classList.remove('indicador-linea-avance-inactivo-govco');
				TercerItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (TercerIndicador.classList.contains('num-indicador-inactivo-govco')) {
				TercerIndicador.classList.remove('num-indicador-inactivo-govco');
				TercerIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (TercerLetra.classList.contains('letra-indicador-inactivo')) {
				TercerLetra.classList.remove('letra-indicador-inactivo');
				TercerLetra.classList.toggle('letra-indicador-activo');
			}
			document.getElementById('letra3').style.display = 'block';
		}
		if (lineavance == '80%') {
			document.getElementById('lineavance').style.width = '100%';
			if (
				TercerItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				TercerItem.classList.remove('indicador-linea-avance-activo-govco');
				TercerItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (TercerLetra.classList.contains('letra-indicador-activo')) {
				TercerLetra.classList.remove('letra-indicador-activo');
				TercerLetra.classList.toggle('letra-indicador-terminado');
			}
			if (
				CuartaItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				CuartaItem.classList.remove('indicador-linea-avance-inactivo-govco');
				CuartaItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (CuartaIndicador.classList.contains('num-indicador-inactivo-govco')) {
				CuartaIndicador.classList.remove('num-indicador-inactivo-govco');
				CuartaIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (CuartaLetra.classList.contains('letra-indicador-inactivo')) {
				CuartaLetra.classList.remove('letra-indicador-inactivo');
				CuartaLetra.classList.toggle('letra-indicador-activo');
			}
			document.getElementById('botonLinea').innerText = 'Finalizar';
			document.getElementById('letra4').style.display = 'block';
		}
	}
	/* --------------------------------------dispositivo movil --------*/
	if (window.matchMedia('(max-width: 990px)').matches) {
		if (lineavance == '15%') {
			document.getElementById('lineavance').style.width = '49%';
			if (
				PrimerItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				PrimerItem.classList.remove('indicador-linea-avance-activo-govco');
				PrimerItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (PrimerLetra.classList.contains('letra-indicador-activo')) {
				PrimerLetra.classList.remove('letra-indicador-activo');
				PrimerLetra.classList.toggle('letra-indicador-inactivo');
			}
			if (
				SegundoItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				SegundoItem.classList.remove('indicador-linea-avance-inactivo-govco');
				SegundoItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (SegundoIndicador.classList.contains('num-indicador-inactivo-govco')) {
				SegundoIndicador.classList.remove('num-indicador-inactivo-govco');
				SegundoIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (SegundoLetra.classList.contains('letra-indicador-inactivo')) {
				SegundoLetra.classList.remove('letra-indicador-inactivo');
				SegundoLetra.classList.toggle('letra-indicador-activo');
			}
		}
		if (lineavance == '49%') {
			document.getElementById('lineavance').style.width = '80%';
			if (
				SegundoItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				SegundoItem.classList.remove('indicador-linea-avance-activo-govco');
				SegundoItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (SegundoLetra.classList.contains('letra-indicador-activo')) {
				SegundoLetra.classList.remove('letra-indicador-activo');
				SegundoLetra.classList.toggle('letra-indicador-inactivo');
			}
			if (
				TercerItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				TercerItem.classList.remove('indicador-linea-avance-inactivo-govco');
				TercerItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (TercerIndicador.classList.contains('num-indicador-inactivo-govco')) {
				TercerIndicador.classList.remove('num-indicador-inactivo-govco');
				TercerIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (TercerLetra.classList.contains('letra-indicador-inactivo')) {
				TercerLetra.classList.remove('letra-indicador-inactivo');
				TercerLetra.classList.toggle('letra-indicador-activo');
			}
		}
		if (lineavance == '80%') {
			document.getElementById('lineavance').style.width = '100%';
			if (
				TercerItem.classList.contains('indicador-linea-avance-activo-govco')
			) {
				TercerItem.classList.remove('indicador-linea-avance-activo-govco');
				TercerItem.classList.toggle('indicador-linea-avance-terminado-govco');
			}
			if (TercerLetra.classList.contains('letra-indicador-activo')) {
				TercerLetra.classList.remove('letra-indicador-activo');
				TercerLetra.classList.toggle('letra-indicador-inactivo');
			}
			if (
				CuartaItem.classList.contains('indicador-linea-avance-inactivo-govco')
			) {
				CuartaItem.classList.remove('indicador-linea-avance-inactivo-govco');
				CuartaItem.classList.toggle('indicador-linea-avance-activo-govco');
			}
			if (CuartaIndicador.classList.contains('num-indicador-inactivo-govco')) {
				CuartaIndicador.classList.remove('num-indicador-inactivo-govco');
				CuartaIndicador.classList.toggle('num-indicador-activo-govco');
			}
			if (CuartaLetra.classList.contains('letra-indicador-inactivo')) {
				CuartaLetra.classList.remove('letra-indicador-inactivo');
				CuartaLetra.classList.toggle('letra-indicador-activo');
			}
			document.getElementById('botonLinea').innerText = 'Finalizar';
		}
	}
}

/* - - - - - - - - - - - - EJEMPLO VERTICAL - - - - - - - - */

function LineaAvenceVertical() {
	var PrimerItemv = document.getElementById('indicadorlinea1v');
	var SegundoItemv = document.getElementById('indicadorlinea2v');
	var TercerItemv = document.getElementById('indicadorlinea3v');
	var CuartaItemv = document.getElementById('indicadorlinea4v');
	var PrimerIndicadorv = document.getElementById('numeroindicador1v');
	var SegundoIndicadorv = document.getElementById('numeroindicador2v');
	var TercerIndicadorv = document.getElementById('numeroindicador3v');
	var CuartaIndicadorv = document.getElementById('numeroindicador4v');
	var PrimerLetrav = document.getElementById('letra1v');
	var SegundoLetrav = document.getElementById('letra2v');
	var TercerLetrav = document.getElementById('letra3v');
	var CuartaLetrav = document.getElementById('letra4v');
	var lineavancev = document.getElementById('lineavancev').style.height;

	if (window.matchMedia('(min-width: 990px)').matches) {
		if (lineavancev == '13%') {
			document.getElementById('lineavancev').style.height = '47%';
			document.getElementById('letra2v').style.display = 'block';
			document.getElementById('contlineaver2').style.display = 'block';
			document.getElementById('contlineaver1').style.display = 'none';
			if (
				PrimerItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				PrimerItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				PrimerItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (PrimerLetrav.classList.contains('letra-indicador-activo-vertical')) {
				PrimerLetrav.classList.remove('letra-indicador-activo-vertical');
				PrimerLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				SegundoItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				SegundoItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				SegundoItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				SegundoIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				SegundoIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				SegundoIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				SegundoLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				SegundoLetrav.classList.remove('letra-indicador-inactivo-vertical');
				SegundoLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
		if (lineavancev == '47%') {
			document.getElementById('lineavancev').style.height = '70%';
			document.getElementById('contlineaver3').style.display = 'block';
			document.getElementById('contlineaver2').style.display = 'none';

			if (
				SegundoItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				SegundoItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				SegundoItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (SegundoLetrav.classList.contains('letra-indicador-activo-vertical')) {
				SegundoLetrav.classList.remove('letra-indicador-activo-vertical');
				SegundoLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				TercerItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				TercerItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				TercerItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				TercerIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				TercerIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				TercerIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				TercerLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				TercerLetrav.classList.remove('letra-indicador-inactivo-vertical');
				TercerLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
		if (lineavancev == '70%') {
			document.getElementById('lineavancev').style.height = '100%';
			document.getElementById('contlineaver4').style.display = 'block';
			document.getElementById('contlineaver3').style.display = 'none';
			if (
				TercerItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				TercerItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				TercerItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (TercerLetrav.classList.contains('letra-indicador-activo-vertical')) {
				TercerLetrav.classList.remove('letra-indicador-activo-vertical');
				TercerLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				CuartaItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				CuartaItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				CuartaItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				CuartaIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				CuartaIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				CuartaIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				CuartaLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				CuartaLetrav.classList.remove('letra-indicador-inactivo-vertical');
				CuartaLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
	}
	/* --------------------------------------dispositivo movil --------*/
	if (window.matchMedia('(max-width: 990px)').matches) {
		if (lineavancev == '13%') {
			document.getElementById('lineavancev').style.height = '45%';
			document.getElementById('letra2v').style.display = 'block';
			document.getElementById('contlineaver2').style.display = 'block';
			document.getElementById('contlineaver1').style.display = 'none';
			if (
				PrimerItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				PrimerItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				PrimerItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (PrimerLetrav.classList.contains('letra-indicador-activo-vertical')) {
				PrimerLetrav.classList.remove('letra-indicador-activo-vertical');
				PrimerLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				SegundoItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				SegundoItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				SegundoItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				SegundoIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				SegundoIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				SegundoIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				SegundoLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				SegundoLetrav.classList.remove('letra-indicador-inactivo-vertical');
				SegundoLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
		if (lineavancev == '45%') {
			document.getElementById('lineavancev').style.height = '65%';
			document.getElementById('contlineaver3').style.display = 'block';
			document.getElementById('contlineaver2').style.display = 'none';

			if (
				SegundoItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				SegundoItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				SegundoItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (SegundoLetrav.classList.contains('letra-indicador-activo-vertical')) {
				SegundoLetrav.classList.remove('letra-indicador-activo-vertical');
				SegundoLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				TercerItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				TercerItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				TercerItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				TercerIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				TercerIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				TercerIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				TercerLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				TercerLetrav.classList.remove('letra-indicador-inactivo-vertical');
				TercerLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
		if (lineavancev == '65%') {
			document.getElementById('lineavancev').style.height = '100%';
			document.getElementById('contlineaver4').style.display = 'block';
			document.getElementById('contlineaver3').style.display = 'none';
			if (
				TercerItemv.classList.contains(
					'indicador-linea-avance-activo-vertical-govco'
				)
			) {
				TercerItemv.classList.remove(
					'indicador-linea-avance-activo-vertical-govco'
				);
				TercerItemv.classList.toggle(
					'indicador-linea-avance-terminado-vertical-govco'
				);
			}
			if (TercerLetrav.classList.contains('letra-indicador-activo-vertical')) {
				TercerLetrav.classList.remove('letra-indicador-activo-vertical');
				TercerLetrav.classList.toggle('letra-indicador-terminado-vertical');
			}
			if (
				CuartaItemv.classList.contains(
					'indicador-linea-avance-inactivo-vertical-govco'
				)
			) {
				CuartaItemv.classList.remove(
					'indicador-linea-avance-inactivo-vertical-govco'
				);
				CuartaItemv.classList.toggle(
					'indicador-linea-avance-activo-vertical-govco'
				);
			}
			if (
				CuartaIndicadorv.classList.contains(
					'num-indicador-inactivo-vertical-govco'
				)
			) {
				CuartaIndicadorv.classList.remove(
					'num-indicador-inactivo-vertical-govco'
				);
				CuartaIndicadorv.classList.toggle(
					'num-indicador-activo-vertical-govco'
				);
			}
			if (
				CuartaLetrav.classList.contains('letra-indicador-inactivo-vertical')
			) {
				CuartaLetrav.classList.remove('letra-indicador-inactivo-vertical');
				CuartaLetrav.classList.toggle('letra-indicador-activo-vertical');
			}
		}
	}
}

/* - - - - - - - - - - - - -  EJEMPLO INTERACCION HORIZONTAL - - - - - - - - - - - - - - */

function LineainteraccionHorizontal1() {
	var PrimerIteminthrz = document.getElementById('indicadorlineainthrz1');
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var CuartaIteminthrz = document.getElementById('indicadorlineainthrz4');
	var PrimerIndicadorinthrz = document.getElementById('numeroindicadorinthrz1');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var CuartaIndicadorinthrz = document.getElementById('numeroindicadorinthrz4');
	var PrimerLetrainthrz = document.getElementById('letrainthrz1');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');
	var CuartaLetrainthrz = document.getElementById('letrainthrz4');

	document.getElementById('letrainthrz2').style.display = 'block';
	document.getElementById('contactohorizontaluno').style.display = 'block';
	document.getElementById('contactohorizontaldos').style.display = 'none';
	document.getElementById('contactohorizontaltres').style.display = 'none';
	document.getElementById('contactohorizontalcuatro').style.display = 'none';
	if (
		PrimerIteminthrz.classList.contains(
			'indicador-linea-avance-terminado-interaccion-govco'
		)
	) {
		PrimerIteminthrz.classList.remove(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		PrimerIteminthrz.classList.toggle(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz1').innerText = '1';

		if (
			PrimerLetrainthrz.classList.contains(
				'letra-indicador-terminado-interaccion'
			)
		) {
			PrimerLetrainthrz.classList.remove(
				'letra-indicador-terminado-interaccion'
			);
			PrimerLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
		if (
			PrimerIndicadorinthrz.classList.contains(
				'num-indicador-terminado-interaccion-govco'
			)
		) {
			PrimerIndicadorinthrz.classList.remove(
				'num-indicador-terminado-interaccion-govco'
			);
			PrimerIndicadorinthrz.classList.toggle(
				'num-indicador-activo-interaccion-govco'
			);
		}
	}

	if (
		SegundoIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		SegundoIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		SegundoIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz2').innerText = '';
		if (
			SegundoIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			SegundoIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			SegundoIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}
		if (
			SegundoLetrainthrz.classList.contains(
				'letra-indicador-activo-interaccion'
			)
		) {
			SegundoLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			SegundoLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}

	if (
		TercerIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		TercerIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		TercerIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz3').innerText = '';
		if (
			TercerIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			TercerIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			TercerIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}
		if (
			TercerLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			TercerLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			TercerLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}

	if (
		CuartaIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		CuartaIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		CuartaIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz4').innerText = '';
		if (
			CuartaIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			CuartaIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			CuartaIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}
		if (
			CuartaLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			CuartaLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			CuartaLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}
}

function ContinuarDos() {
	var PrimerIteminthrz = document.getElementById('indicadorlineainthrz1');
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var PrimerIndicadorinthrz = document.getElementById('numeroindicadorinthrz1');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var PrimerLetrainthrz = document.getElementById('letrainthrz1');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var lineavanceinthrz2 = document.getElementById('lineavanceinthrz2');

	if (
		SegundoIteminthrz.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-govco'
		)
	) {
		document.getElementById('contactohorizontaldos').style.display = 'block';
		document.getElementById('contactohorizontaluno').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'none';
		document.getElementById('contactohorizontalcuatro').style.display = 'none';

		if (
			PrimerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			PrimerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			PrimerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);
			document.getElementById('numeroindicadorinthrz1').innerText = '';
			document.getElementById('lineavanceinthrz1').style.width = '100%';

			if (
				PrimerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				PrimerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				PrimerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
			if (
				PrimerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				PrimerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				PrimerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}
		}

		if (
			SegundoIteminthrz.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-govco'
			)
		) {
			SegundoIteminthrz.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-govco'
			);
			SegundoIteminthrz.classList.toggle(
				'indicador-linea-avance-activo-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz2').innerText = '2';
			if (
				lineavanceinthrz2.classList.contains(
					'progress-bar-activa-interaccion-inactivada-govco'
				)
			) {
				lineavanceinthrz2.classList.remove(
					'progress-bar-activa-interaccion-inactivada-govco'
				);
				lineavanceinthrz2.classList.toggle(
					'progress-bar-activa-interaccion-govco'
				);
				document.getElementById('lineavanceinthrz2').style.width = '30%';
			}
			if (
				SegundoIndicadorinthrz.classList.contains(
					'num-indicador-inactivo-interaccion-govco'
				)
			) {
				SegundoIndicadorinthrz.classList.remove(
					'num-indicador-inactivo-interaccion-govco'
				);
				SegundoIndicadorinthrz.classList.toggle(
					'num-indicador-activo-interaccion-govco'
				);
			}
			if (
				SegundoLetrainthrz.classList.contains(
					'letra-indicador-inactivo-interaccion'
				)
			) {
				SegundoLetrainthrz.classList.remove(
					'letra-indicador-inactivo-interaccion'
				);
				SegundoLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}
	}
}

function LineainteraccionHorizontal2() {
	var PrimerIteminthrz = document.getElementById('indicadorlineainthrz1');
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var CuartaIteminthrz = document.getElementById('indicadorlineainthrz4');
	var PrimerIndicadorinthrz = document.getElementById('numeroindicadorinthrz1');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var CuartaIndicadorinthrz = document.getElementById('numeroindicadorinthrz4');
	var PrimerLetrainthrz = document.getElementById('letrainthrz1');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');
	var CuartaLetrainthrz = document.getElementById('letrainthrz4');

	if (
		SegundoIteminthrz.classList.contains(
			'indicador-linea-avance-terminado-interaccion-govco'
		)
	) {
		SegundoIteminthrz.classList.remove(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		SegundoIteminthrz.classList.toggle(
			'indicador-linea-avance-activo-interaccion-govco'
		);

		document.getElementById('numeroindicadorinthrz2').innerText = '2';
		if (
			SegundoIndicadorinthrz.classList.contains(
				'num-indicador-terminado-interaccion-govco'
			)
		) {
			SegundoIndicadorinthrz.classList.remove(
				'num-indicador-terminado-interaccion-govco'
			);
			SegundoIndicadorinthrz.classList.toggle(
				'num-indicador-activo-interaccion-govco'
			);
		}
		if (
			SegundoLetrainthrz.classList.contains(
				'letra-indicador-terminado-interaccion'
			)
		) {
			SegundoLetrainthrz.classList.remove(
				'letra-indicador-terminado-interaccion'
			);
			SegundoLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}

		document.getElementById('contactohorizontaldos').style.display = 'block';
		document.getElementById('contactohorizontaluno').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'none';
		document.getElementById('contactohorizontalcuatro').style.display = 'none';

		if (
			PrimerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			PrimerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			PrimerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);
			document.getElementById('numeroindicadorinthrz1').innerText = '';

			if (
				PrimerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				PrimerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				PrimerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
			if (
				PrimerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				PrimerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				PrimerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}
		}
	}

	if (
		TercerIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		TercerIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		TercerIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);

		document.getElementById('numeroindicadorinthrz2').innerText = '2';
		if (
			TercerIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			TercerIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			TercerIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}

		if (
			TercerLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			TercerLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			TercerLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}

	if (
		TercerIteminthrz.classList.contains(
			'indicador-linea-avance-terminado-interaccion-govco'
		)
	) {
		document.getElementById('numeroindicadorinthrz2').innerText = '2';
		document.getElementById('numeroindicadorinthrz3').innerText = '';
	}

	if (
		CuartaIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		CuartaIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		CuartaIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz4').innerText = '';
		if (
			CuartaIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			CuartaIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			CuartaIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}
		if (
			CuartaLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			CuartaLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			CuartaLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}

	if (window.matchMedia('(max-width: 990px)').matches) {
		if (
			SegundoIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			document.getElementById('indicadorcontainerinthrzlinea1').style.display =
				'block';
			document.getElementById('indicadorcontainerinthrzlinea3').style.display =
				'none';
			document.getElementById('indicadorcontainerinthrz1').style.display =
				'block';
			document.getElementById('letrainthrz1').style.display = 'block';
			document.getElementById('letracontainerinthrz1').style.display = 'block';
			document.getElementById('indicadorcontainerinthrz4').style.display =
				'none';
			document.getElementById('letracontainerinthrz4').style.display = 'none';
			document.getElementById('letrainthrz4').style.display = 'none';
			document.getElementById('espacioTitulo1').style.display = 'block';
			document.getElementById('espacioTitulo3').style.display = 'none';
		}
	}
}

function ContinuarTres() {
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');
	var lineavanceinthrz3 = document.getElementById('lineavanceinthrz3');

	if (
		TercerIteminthrz.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-govco'
		)
	) {
		document.getElementById('contactohorizontalcuatro').style.display = 'none';
		document.getElementById('contactohorizontaldos').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'block';
		document.getElementById('contactohorizontaluno').style.display = 'none';
		document.getElementById('numeroindicadorinthrz2').innerText = '';

		if (
			TercerIteminthrz.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-govco'
			)
		) {
			TercerIteminthrz.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-govco'
			);
			TercerIteminthrz.classList.toggle(
				'indicador-linea-avance-activo-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz3').innerText = '3';
			if (
				lineavanceinthrz3.classList.contains(
					'progress-bar-activa-interaccion-inactivada-govco'
				)
			) {
				lineavanceinthrz3.classList.remove(
					'progress-bar-activa-interaccion-inactivada-govco'
				);
				lineavanceinthrz3.classList.toggle(
					'progress-bar-activa-interaccion-govco'
				);
				document.getElementById('lineavanceinthrz3').style.width = '30%';
				document.getElementById('lineavanceinthrz2').style.width = '100%';
			}
			if (
				TercerIndicadorinthrz.classList.contains(
					'num-indicador-inactivo-interaccion-govco'
				)
			) {
				TercerIndicadorinthrz.classList.remove(
					'num-indicador-inactivo-interaccion-govco'
				);
				TercerIndicadorinthrz.classList.toggle(
					'num-indicador-activo-interaccion-govco'
				);
			}
			if (
				TercerLetrainthrz.classList.contains(
					'letra-indicador-inactivo-interaccion'
				)
			) {
				TercerLetrainthrz.classList.remove(
					'letra-indicador-inactivo-interaccion'
				);
				TercerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}

		if (
			SegundoIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			SegundoIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			SegundoIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz2').innerText = '';
			if (
				SegundoIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				SegundoIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				SegundoIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}
			if (
				SegundoLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				SegundoLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				SegundoLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}
	}

	if (window.matchMedia('(max-width: 990px)').matches) {
		document.getElementById('indicadorcontainerinthrzlinea1').style.display =
			'none';
		document.getElementById('indicadorcontainerinthrzlinea3').style.display =
			'block';
		document.getElementById('indicadorcontainerinthrz1').style.display = 'none';
		document.getElementById('letrainthrz1').style.display = 'none';
		document.getElementById('letracontainerinthrz1').style.display = 'none';
		document.getElementById('indicadorcontainerinthrz4').style.display =
			'block';
		document.getElementById('letracontainerinthrz4').style.display = 'block';
		document.getElementById('letrainthrz4').style.display = 'block';
		document.getElementById('espacioTitulo1').style.display = 'none';
		document.getElementById('espacioTitulo3').style.display = 'block';
	}
}

function LineainteraccionHorizontal3() {
	var PrimerIteminthrz = document.getElementById('indicadorlineainthrz1');
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var CuartaIteminthrz = document.getElementById('indicadorlineainthrz4');
	var PrimerIndicadorinthrz = document.getElementById('numeroindicadorinthrz1');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var CuartaIndicadorinthrz = document.getElementById('numeroindicadorinthrz4');
	var PrimerLetrainthrz = document.getElementById('letrainthrz1');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');
	var CuartaLetrainthrz = document.getElementById('letrainthrz4');

	if (
		TercerIteminthrz.classList.contains(
			'indicador-linea-avance-terminado-interaccion-govco'
		)
	) {
		TercerIteminthrz.classList.remove(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		TercerIteminthrz.classList.toggle(
			'indicador-linea-avance-activo-interaccion-govco'
		);

		document.getElementById('contactohorizontalcuatro').style.display = 'none';
		document.getElementById('contactohorizontaldos').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'block';
		document.getElementById('contactohorizontaluno').style.display = 'none';

		document.getElementById('numeroindicadorinthrz3').innerText = '3';
		if (
			TercerIndicadorinthrz.classList.contains(
				'num-indicador-terminado-interaccion-govco'
			)
		) {
			TercerIndicadorinthrz.classList.remove(
				'num-indicador-terminado-interaccion-govco'
			);
			TercerIndicadorinthrz.classList.toggle(
				'num-indicador-activo-interaccion-govco'
			);
		}
		if (
			TercerLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			TercerLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			TercerLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}

		if (
			PrimerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			PrimerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			PrimerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);
			document.getElementById('numeroindicadorinthrz1').innerText = '';

			if (
				PrimerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				PrimerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				PrimerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
			if (
				PrimerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				PrimerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				PrimerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}
		}

		if (
			SegundoIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			SegundoIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			SegundoIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);
			document.getElementById('numeroindicadorinthrz2').innerText = '';

			if (
				SegundoLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				SegundoLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				SegundoLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
			if (
				SegundoIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				SegundoIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				SegundoIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}
		}
	}

	if (
		CuartaIteminthrz.classList.contains(
			'indicador-linea-avance-activo-interaccion-govco'
		)
	) {
		CuartaIteminthrz.classList.remove(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		CuartaIteminthrz.classList.toggle(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		document.getElementById('contactohorizontalcuatro').style.display = 'none';
		document.getElementById('contactohorizontaldos').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'block';
		document.getElementById('contactohorizontaluno').style.display = 'none';
		document.getElementById('numeroindicadorinthrz2').innerText = '';
		document.getElementById('numeroindicadorinthrz4').innerText = '';
		if (
			CuartaIndicadorinthrz.classList.contains(
				'num-indicador-activo-interaccion-govco'
			)
		) {
			CuartaIndicadorinthrz.classList.remove(
				'num-indicador-activo-interaccion-govco'
			);
			CuartaIndicadorinthrz.classList.toggle(
				'num-indicador-terminado-interaccion-govco'
			);
		}
		if (
			CuartaLetrainthrz.classList.contains('letra-indicador-activo-interaccion')
		) {
			CuartaLetrainthrz.classList.remove('letra-indicador-activo-interaccion');
			CuartaLetrainthrz.classList.toggle('letra-indicador-activo-interaccion');
		}
	}

	if (window.matchMedia('(max-width: 990px)').matches) {
		document.getElementById('indicadorcontainerinthrzlinea1').style.display =
			'none';
		document.getElementById('indicadorcontainerinthrzlinea3').style.display =
			'block';
		document.getElementById('indicadorcontainerinthrz1').style.display = 'none';
		document.getElementById('letrainthrz1').style.display = 'none';
		document.getElementById('letracontainerinthrz1').style.display = 'none';
		document.getElementById('indicadorcontainerinthrz4').style.display =
			'block';
		document.getElementById('letracontainerinthrz4').style.display = 'block';
		document.getElementById('letrainthrz4').style.display = 'block';
		document.getElementById('espacioTitulo1').style.display = 'none';
		document.getElementById('espacioTitulo3').style.display = 'block';
	}
}

function ContinuarCuatro() {
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var CuartaIteminthrz = document.getElementById('indicadorlineainthrz4');
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var CuartaIndicadorinthrz = document.getElementById('numeroindicadorinthrz4');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');
	var CuartaLetrainthrz = document.getElementById('letrainthrz4');

	if (
		CuartaIteminthrz.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-govco'
		)
	) {
		if (
			CuartaIteminthrz.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-govco'
			)
		) {
			CuartaIteminthrz.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-govco'
			);
			CuartaIteminthrz.classList.toggle(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			document.getElementById('lineavanceinthrz3').style.width = '100%';
			document.getElementById('contactohorizontalcuatro').style.display =
				'block';
			document.getElementById('contactohorizontaldos').style.display = 'none';
			document.getElementById('contactohorizontaltres').style.display = 'none';
			document.getElementById('contactohorizontaluno').style.display = 'none';
			if (
				CuartaIndicadorinthrz.classList.contains(
					'num-indicador-inactivo-interaccion-govco'
				)
			) {
				CuartaIndicadorinthrz.classList.remove(
					'num-indicador-inactivo-interaccion-govco'
				);
				CuartaIndicadorinthrz.classList.toggle(
					'num-indicador-activo-interaccion-govco'
				);
			}
			if (
				CuartaLetrainthrz.classList.contains(
					'letra-indicador-inactivo-interaccion'
				)
			) {
				CuartaLetrainthrz.classList.remove(
					'letra-indicador-inactivo-interaccion'
				);
				CuartaLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}

		if (
			TercerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			TercerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			TercerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz3').innerText = '';
			if (
				TercerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				TercerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				TercerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}

			if (
				TercerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				TercerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				TercerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}
	}
}

function LineainteraccionHorizontal4() {
	var PrimerIteminthrz = document.getElementById('indicadorlineainthrz1');
	var SegundoIteminthrz = document.getElementById('indicadorlineainthrz2');
	var TercerIteminthrz = document.getElementById('indicadorlineainthrz3');
	var CuartaIteminthrz = document.getElementById('indicadorlineainthrz4');
	var PrimerIndicadorinthrz = document.getElementById('numeroindicadorinthrz1');
	var SegundoIndicadorinthrz = document.getElementById(
		'numeroindicadorinthrz2'
	);
	var TercerIndicadorinthrz = document.getElementById('numeroindicadorinthrz3');
	var CuartaIndicadorinthrz = document.getElementById('numeroindicadorinthrz4');
	var PrimerLetrainthrz = document.getElementById('letrainthrz1');
	var SegundoLetrainthrz = document.getElementById('letrainthrz2');
	var TercerLetrainthrz = document.getElementById('letrainthrz3');

	if (
		CuartaIteminthrz.classList.contains(
			'indicador-linea-avance-terminado-interaccion-govco'
		)
	) {
		CuartaIteminthrz.classList.remove(
			'indicador-linea-avance-terminado-interaccion-govco'
		);
		CuartaIteminthrz.classList.toggle(
			'indicador-linea-avance-activo-interaccion-govco'
		);
		document.getElementById('numeroindicadorinthrz4').innerText = '4';
		document.getElementById('contactohorizontalcuatro').style.display = 'block';
		document.getElementById('contactohorizontaldos').style.display = 'none';
		document.getElementById('contactohorizontaltres').style.display = 'none';
		document.getElementById('contactohorizontaluno').style.display = 'none';

		if (
			CuartaIndicadorinthrz.classList.contains(
				'num-indicador-terminado-interaccion-govco'
			)
		) {
			CuartaIndicadorinthrz.classList.remove(
				'num-indicador-terminado-interaccion-govco'
			);
			CuartaIndicadorinthrz.classList.toggle(
				'num-indicador-activo-interaccion-govco'
			);
		}

		if (
			TercerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			TercerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			TercerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz3').innerText = '';
			if (
				TercerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				TercerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				TercerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}

			if (
				TercerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				TercerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				TercerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}

		if (
			SegundoIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			SegundoIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			SegundoIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz2').innerText = '';
			if (
				SegundoIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				SegundoIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				SegundoIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}

			if (
				SegundoLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				SegundoLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				SegundoLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}

		if (
			PrimerIteminthrz.classList.contains(
				'indicador-linea-avance-activo-interaccion-govco'
			)
		) {
			PrimerIteminthrz.classList.remove(
				'indicador-linea-avance-activo-interaccion-govco'
			);
			PrimerIteminthrz.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-govco'
			);

			document.getElementById('numeroindicadorinthrz1').innerText = '';
			if (
				PrimerIndicadorinthrz.classList.contains(
					'num-indicador-activo-interaccion-govco'
				)
			) {
				PrimerIndicadorinthrz.classList.remove(
					'num-indicador-activo-interaccion-govco'
				);
				PrimerIndicadorinthrz.classList.toggle(
					'num-indicador-terminado-interaccion-govco'
				);
			}

			if (
				PrimerLetrainthrz.classList.contains(
					'letra-indicador-activo-interaccion'
				)
			) {
				PrimerLetrainthrz.classList.remove(
					'letra-indicador-activo-interaccion'
				);
				PrimerLetrainthrz.classList.toggle(
					'letra-indicador-activo-interaccion'
				);
			}
		}
	}
}

function focusInteraccionEnter(textChange) {
	if (
		document
			.getElementById('indicadorlineainthrz' + textChange)
			.classList.contains('indicador-linea-avance-inactivo-interaccion-govco')
	) {
		document.getElementById('letrainthrz' + textChange).style.fontFamily =
			'WorkSans-Medium';
	}
}

function focusInteraccionLeave(textChange) {
	if (
		document
			.getElementById('indicadorlineainthrz' + textChange)
			.classList.contains('indicador-linea-avance-inactivo-interaccion-govco')
	) {
		document
			.getElementById('letrainthrz' + textChange)
			.removeAttribute('style');
	}
}

/* - - - - - - - - - - - - -  EJEMPLO INTERACCION VERTICAL - - - - - - - - - - - - - - */

function ContinuarVerticalUno() {
	var PrimerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz1-InteVer'
	);
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var PrimerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz1-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var PrimerLetrainthrzInteVer = document.getElementById('letra1v-InteVer');
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var lineavanceinthrz2InteVer = document.getElementById(
		'lineavanceinthrz2-InteVer'
	);
	var contenedor1InteVer = document.getElementById('contenedor1-InteVer');
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var containerletra1InteVer = document.getElementById(
		'containerletra1InteVer'
	);
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);

	if (
		SegundoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
		)
	) {
		document.getElementById('contlineaver1-InteVer').style.display = 'none';
		document.getElementById('contlineaver2-InteVer').style.display = 'block';
		document.getElementById('contlineaver3-InteVer').style.display = 'none';
		document.getElementById('contlineaver4-InteVer').style.display = 'none';

		if (
			containerletra1InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra1InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra1InteVer.classList.toggle('container-letrafinal-InteVer');
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra2InteVer.classList.toggle(
				'container-letrainactiva-InteVer'
			);
			containerletra2InteVer.classList.toggle(
				'container-letrainicial-paso-3-InteVer'
			);
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
			containerletra2InteVer.classList.toggle(
				'container-letrainicial-paso-3-InteVer'
			);
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			PrimerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			PrimerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz1-InteVer').innerText = '';
			document.getElementById('lineavanceinthrz1-InteVer').style.height =
				'100%';

			if (
				PrimerLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				PrimerLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				PrimerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				PrimerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				PrimerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				PrimerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
		}

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			)
		) {
			SegundoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			);
			SegundoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);

			if (
				lineavanceinthrz2InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz2InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz2InteVer.classList.toggle(
					'progress-bar-activa-interaccion-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz2-InteVer').style.height =
					'45%';
				document.getElementById('numeroindicadorinthrz2-InteVer').innerText =
					'2';
			}
			if (
				SegundoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				)
			) {
				SegundoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				);
				SegundoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
			if (
				SegundoLetrainthrzInteVer.classList.contains(
					'letra-indicador-inactivo-interaccion-InteVer'
				)
			) {
				SegundoLetrainthrzInteVer.classList.remove(
					'letra-indicador-inactivo-interaccion-InteVer'
				);
				SegundoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}
}

function LineainteraccionHorizontal1InteVer() {
	var PrimerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz1-InteVer'
	);
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var CuartoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz4-InteVer'
	);
	var PrimerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz1-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var CuartoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz4-InteVer'
	);
	var PrimerLetrainthrzInteVer = document.getElementById('letra1v-InteVer');
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var CuartoLetrainthrzInteVer = document.getElementById('letra4v-InteVer');
	var lineavanceinthrz2InteVer = document.getElementById(
		'lineavanceinthrz2-InteVer'
	);
	var lineavanceinthrz3InteVer = document.getElementById(
		'lineavanceinthrz3-InteVer'
	);
	var contenedor1InteVer = document.getElementById('contenedor1-InteVer');
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra1InteVer = document.getElementById(
		'containerletra1InteVer'
	);
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	document.getElementById('contlineaver1-InteVer').style.display = 'block';
	document.getElementById('contlineaver2-InteVer').style.display = 'none';
	document.getElementById('contlineaver3-InteVer').style.display = 'none';
	document.getElementById('contlineaver4-InteVer').style.display = 'none';

	if (
		SegundoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-activo-interaccion-InteVer-govco'
		)
	) {
		if (
			containerletra1InteVer.classList.contains('container-letrafinal-InteVer')
		) {
			containerletra1InteVer.classList.remove('container-letrafinal-InteVer');
			containerletra1InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra2InteVer.classList.toggle(
				'container-letrainactiva-InteVer'
			);
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
		}

		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			)
		) {
			PrimerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			PrimerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz1-InteVer').innerText = '1';

			if (
				PrimerLetrainthrzInteVer.classList.contains(
					'letra-indicador-terminado-interaccion-InteVer'
				)
			) {
				PrimerLetrainthrzInteVer.classList.remove(
					'letra-indicador-terminado-interaccion-InteVer'
				);
				PrimerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				PrimerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-terminado-interaccion-InteVer-govco'
				)
			) {
				PrimerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
				PrimerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
		}

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			SegundoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			SegundoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);

			if (
				lineavanceinthrz2InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz2InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz2InteVer.classList.toggle(
					'progress-bar-activa-interaccion-finalizada-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz2-InteVer').style.height =
					'45%';
			}
			document.getElementById('numeroindicadorinthrz2-InteVer').innerText = '';
			if (
				SegundoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				SegundoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				SegundoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
			if (
				SegundoLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				SegundoLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				SegundoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}

	if (
		TercerIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-activo-interaccion-InteVer-govco'
		)
	) {
		if (
			containerletra1InteVer.classList.contains('container-letrafinal-InteVer')
		) {
			containerletra1InteVer.classList.remove('container-letrafinal-InteVer');
			containerletra1InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra3InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra3InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra3InteVer.classList.toggle(
				'container-letrainactiva-InteVer'
			);
		}

		if (
			containerletra1InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra1InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra1InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor3InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor3InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor3InteVer.classList.toggle(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
		}

		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			)
		) {
			PrimerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			PrimerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz1-InteVer').innerText = '1';

			if (
				PrimerLetrainthrzInteVer.classList.contains(
					'letra-indicador-terminado-interaccion-InteVer'
				)
			) {
				PrimerLetrainthrzInteVer.classList.remove(
					'letra-indicador-terminado-interaccion-InteVer'
				);
				PrimerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				PrimerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-terminado-interaccion-InteVer-govco'
				)
			) {
				PrimerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
				PrimerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
		}

		if (
			TercerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			TercerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			TercerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);

			if (
				lineavanceinthrz3InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz3InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz3InteVer.classList.toggle(
					'progress-bar-activa-interaccion-finalizada-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz3-InteVer').style.height =
					'45%';
			}
			document.getElementById('numeroindicadorinthrz3-InteVer').innerText = '';
			if (
				TercerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				TercerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				TercerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
			if (
				TercerLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				TercerLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				TercerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}

	if (
		CuartoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-activo-interaccion-InteVer-govco'
		)
	) {
		if (
			containerletra1InteVer.classList.contains('container-letrafinal-InteVer')
		) {
			containerletra1InteVer.classList.remove('container-letrafinal-InteVer');
			containerletra1InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra1InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra1InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra1InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor1InteVer.classList.contains(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			)
		) {
			contenedor1InteVer.classList.remove(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
			contenedor1InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		document.getElementById(
			'indicadorcontainerinthrzlinea4-InteVer'
		).style.display = 'none';

		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			)
		) {
			PrimerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			PrimerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz1-InteVer').innerText = '1';

			if (
				PrimerLetrainthrzInteVer.classList.contains(
					'letra-indicador-terminado-interaccion-InteVer'
				)
			) {
				PrimerLetrainthrzInteVer.classList.remove(
					'letra-indicador-terminado-interaccion-InteVer'
				);
				PrimerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				PrimerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-terminado-interaccion-InteVer-govco'
				)
			) {
				PrimerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
				PrimerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
		}

		if (
			CuartoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			CuartoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			CuartoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz4-InteVer').innerText = '';
			if (
				CuartoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				CuartoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				CuartoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
			if (
				CuartoLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				CuartoLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				CuartoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}
}

function ContinuarVerticalDos() {
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var lineavanceinthrz3InteVer = document.getElementById(
		'lineavanceinthrz3-InteVer'
	);
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	if (
		TercerIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
		)
	) {
		document.getElementById('contlineaver1-InteVer').style.display = 'none';
		document.getElementById('contlineaver2-InteVer').style.display = 'none';
		document.getElementById('contlineaver3-InteVer').style.display = 'block';
		document.getElementById('contlineaver4-InteVer').style.display = 'none';

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra2InteVer.classList.toggle('container-letrafinal-InteVer');
			containerletra2InteVer.classList.toggle(
				'container-letrainicial-paso-3-InteVer'
			);
		}

		if (
			containerletra3InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra3InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra3InteVer.classList.toggle(
				'container-letrainactiva-InteVer'
			);
		}

		if (
			containerletra3InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra3InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra3InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
		}

		if (
			contenedor3InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor3InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor3InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			SegundoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			SegundoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz2-InteVer').innerText = '';
			document.getElementById('lineavanceinthrz2-InteVer').style.height =
				'100%';

			if (
				SegundoLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				SegundoLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				SegundoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				SegundoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				SegundoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				SegundoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
		}

		if (
			TercerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			)
		) {
			TercerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			);
			TercerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);

			if (
				lineavanceinthrz3InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz3InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz3InteVer.classList.toggle(
					'progress-bar-activa-interaccion-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz3-InteVer').style.height =
					'45%';
				document.getElementById('numeroindicadorinthrz3-InteVer').innerText =
					'3';
			}
			if (
				TercerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				)
			) {
				TercerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				);
				TercerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
			if (
				TercerLetrainthrzInteVer.classList.contains(
					'letra-indicador-inactivo-interaccion-InteVer'
				)
			) {
				TercerLetrainthrzInteVer.classList.remove(
					'letra-indicador-inactivo-interaccion-InteVer'
				);
				TercerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}
}

function LineainteraccionHorizontal2InteVer() {
	var PrimerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz1-InteVer'
	);
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var CuartoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz4-InteVer'
	);
	var PrimerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz1-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var CuartoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz4-InteVer'
	);
	var PrimerLetrainthrzInteVer = document.getElementById('letra1v-InteVer');
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var CuartoLetrainthrzInteVer = document.getElementById('letra4v-InteVer');
	var lineavanceinthrz2InteVer = document.getElementById(
		'lineavanceinthrz2-InteVer'
	);
	var lineavanceinthrz3InteVer = document.getElementById(
		'lineavanceinthrz3-InteVer'
	);
	var contenedor1InteVer = document.getElementById('contenedor1-InteVer');
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra1InteVer = document.getElementById(
		'containerletra1InteVer'
	);
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	if (
		SegundoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-terminado-interaccion-InteVer-govco'
		)
	) {
		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			if (
				containerletra1InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra1InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra1InteVer.classList.toggle('container-letrafinal-InteVer');
			}

			if (
				containerletra2InteVer.classList.contains(
					'container-letrainactiva-InteVer'
				)
			) {
				containerletra2InteVer.classList.remove(
					'container-letrainactiva-InteVer'
				);
				containerletra2InteVer.classList.toggle(
					'container-letrainicial-InteVer'
				);
			}

			if (
				contenedor1InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor1InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor1InteVer.classList.toggle(
					'linea-avance-interaccion-finalizada-InteVer-govco'
				);
			}

			if (
				contenedor2InteVer.classList.contains(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				)
			) {
				contenedor2InteVer.classList.remove(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
				contenedor2InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			if (
				contenedor2InteVer.classList.contains(
					'linea-avance-interaccion-finalizada-InteVer-govco'
				)
			) {
				contenedor2InteVer.classList.remove(
					'linea-avance-interaccion-finalizada-InteVer-govco'
				);
				contenedor2InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			if (
				PrimerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				PrimerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				PrimerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz1-InteVer').innerText =
					'';

				if (
					PrimerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					PrimerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					PrimerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					PrimerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					PrimerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					PrimerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
			}

			if (
				SegundoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				SegundoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				SegundoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);

				document.getElementById('contlineaver1-InteVer').style.display = 'none';
				document.getElementById('contlineaver2-InteVer').style.display =
					'block';
				document.getElementById('contlineaver3-InteVer').style.display = 'none';
				document.getElementById('contlineaver4-InteVer').style.display = 'none';

				if (
					lineavanceinthrz2InteVer.classList.contains(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					)
				) {
					lineavanceinthrz2InteVer.classList.remove(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					);
					lineavanceinthrz2InteVer.classList.toggle(
						'progress-bar-activa-interaccion-finalizada-InteVer-govco'
					);
					document.getElementById('lineavanceinthrz2-InteVer').style.height =
						'45%';
				}
				document.getElementById('numeroindicadorinthrz2-InteVer').innerText =
					'2';
				if (
					SegundoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					SegundoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					SegundoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
				if (
					SegundoLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					SegundoLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					SegundoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}
	}

	if (
		TercerIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-activo-interaccion-InteVer-govco'
		)
	) {
		if (
			containerletra2InteVer.classList.contains('container-letrafinal-InteVer')
		) {
			containerletra2InteVer.classList.remove('container-letrafinal-InteVer');
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra3InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra3InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra3InteVer.classList.toggle(
				'container-letrainactiva-InteVer'
			);
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor3InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor3InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor3InteVer.classList.toggle(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
		}

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			)
		) {
			SegundoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			SegundoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz2-InteVer').innerText = '2';

			document.getElementById('contlineaver1-InteVer').style.display = 'none';
			document.getElementById('contlineaver2-InteVer').style.display = 'block';
			document.getElementById('contlineaver3-InteVer').style.display = 'none';
			document.getElementById('contlineaver4-InteVer').style.display = 'none';

			if (
				SegundoLetrainthrzInteVer.classList.contains(
					'letra-indicador-terminado-interaccion-InteVer'
				)
			) {
				SegundoLetrainthrzInteVer.classList.remove(
					'letra-indicador-terminado-interaccion-InteVer'
				);
				SegundoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				SegundoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-terminado-interaccion-InteVer-govco'
				)
			) {
				SegundoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
				SegundoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
		}

		if (
			TercerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			TercerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			TercerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);

			if (
				lineavanceinthrz3InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz3InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz3InteVer.classList.toggle(
					'progress-bar-activa-interaccion-finalizada-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz3-InteVer').style.height =
					'45%';
			}
			document.getElementById('numeroindicadorinthrz3-InteVer').innerText = '';
			if (
				TercerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				TercerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				TercerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
			if (
				TercerLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				TercerLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				TercerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}

	if (
		CuartoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-activo-interaccion-InteVer-govco'
		)
	) {
		if (
			containerletra2InteVer.classList.contains('container-letrafinal-InteVer')
		) {
			containerletra2InteVer.classList.remove('container-letrafinal-InteVer');
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			containerletra2InteVer.classList.contains(
				'container-letrainactiva-InteVer'
			)
		) {
			containerletra2InteVer.classList.remove(
				'container-letrainactiva-InteVer'
			);
			containerletra2InteVer.classList.toggle('container-letrainicial-InteVer');
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		if (
			contenedor2InteVer.classList.contains(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			)
		) {
			contenedor2InteVer.classList.remove(
				'linea-avance-interaccion-inactivada-InteVer-govco'
			);
			contenedor2InteVer.classList.toggle(
				'linea-avance-interaccion-InteVer-govco'
			);
		}

		document.getElementById(
			'indicadorcontainerinthrzlinea4-InteVer'
		).style.display = 'none';
		document.getElementById('contlineaver1-InteVer').style.display = 'none';
		document.getElementById('contlineaver2-InteVer').style.display = 'block';
		document.getElementById('contlineaver3-InteVer').style.display = 'none';
		document.getElementById('contlineaver4-InteVer').style.display = 'none';

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			)
		) {
			SegundoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			SegundoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz2-InteVer').innerText = '2';

			if (
				SegundoLetrainthrzInteVer.classList.contains(
					'letra-indicador-terminado-interaccion-InteVer'
				)
			) {
				SegundoLetrainthrzInteVer.classList.remove(
					'letra-indicador-terminado-interaccion-InteVer'
				);
				SegundoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				SegundoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-terminado-interaccion-InteVer-govco'
				)
			) {
				SegundoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
				SegundoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
		}

		if (
			CuartoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			CuartoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			CuartoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz4-InteVer').innerText = '';
			if (
				CuartoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				CuartoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				CuartoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
			if (
				CuartoLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				CuartoLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				CuartoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}
}

function ContinuarVerticalTres() {
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var CuartoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz4-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var CuartoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz4-InteVer'
	);
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var CuartoLetrainthrzInteVer = document.getElementById('letra4v-InteVer');
	var lineavanceinthrz4InteVer = document.getElementById(
		'lineavanceinthrz4-InteVer'
	);
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	if (
		CuartoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
		)
	) {
		document.getElementById('contlineaver1-InteVer').style.display = 'none';
		document.getElementById('contlineaver2-InteVer').style.display = 'none';
		document.getElementById('contlineaver3-InteVer').style.display = 'none';
		document.getElementById('contlineaver4-InteVer').style.display = 'block';

		if (
			containerletra3InteVer.classList.contains(
				'container-letrainicial-InteVer'
			)
		) {
			containerletra3InteVer.classList.remove('container-letrainicial-InteVer');
			containerletra3InteVer.classList.toggle('container-letrafinal-InteVer');
		}

		if (
			contenedor3InteVer.classList.contains(
				'linea-avance-interaccion-InteVer-govco'
			)
		) {
			contenedor3InteVer.classList.remove(
				'linea-avance-interaccion-InteVer-govco'
			);
			contenedor3InteVer.classList.toggle(
				'linea-avance-interaccion-finalizada-InteVer-govco'
			);
		}

		if (
			TercerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			TercerIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			TercerIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-terminado-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz3-InteVer').innerText = '';
			document.getElementById('lineavanceinthrz3-InteVer').style.height =
				'100%';

			if (
				TercerLetrainthrzInteVer.classList.contains(
					'letra-indicador-activo-interaccion-InteVer'
				)
			) {
				TercerLetrainthrzInteVer.classList.remove(
					'letra-indicador-activo-interaccion-InteVer'
				);
				TercerLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}

			if (
				TercerIndicadorinthrzInteVer.classList.contains(
					'num-indicador-activo-interaccion-InteVer-govco'
				)
			) {
				TercerIndicadorinthrzInteVer.classList.remove(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
				TercerIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-terminado-interaccion-InteVer-govco'
				);
			}
		}

		if (
			CuartoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			)
		) {
			CuartoIteminthrzInteVer.classList.remove(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			);
			CuartoIteminthrzInteVer.classList.toggle(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			);
			document.getElementById('numeroindicadorinthrz3-InteVer').innerText = '';
			document.getElementById(
				'indicadorcontainerinthrzlinea4-InteVer'
			).style.display = 'block';
			if (
				lineavanceinthrz4InteVer.classList.contains(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				)
			) {
				lineavanceinthrz4InteVer.classList.remove(
					'progress-bar-activa-interaccion-inactivada-InteVer-govco'
				);
				lineavanceinthrz4InteVer.classList.toggle(
					'progress-bar-activa-interaccion-InteVer-govco'
				);
				document.getElementById('lineavanceinthrz4-InteVer').style.height =
					'100%';
			}

			if (
				CuartoIndicadorinthrzInteVer.classList.contains(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				)
			) {
				CuartoIndicadorinthrzInteVer.classList.remove(
					'num-indicador-inactivo-interaccion-InteVer-govco'
				);
				CuartoIndicadorinthrzInteVer.classList.toggle(
					'num-indicador-activo-interaccion-InteVer-govco'
				);
			}
			if (
				CuartoLetrainthrzInteVer.classList.contains(
					'letra-indicador-inactivo-interaccion-InteVer'
				)
			) {
				CuartoLetrainthrzInteVer.classList.remove(
					'letra-indicador-inactivo-interaccion-InteVer'
				);
				CuartoLetrainthrzInteVer.classList.toggle(
					'letra-indicador-activo-interaccion-InteVer'
				);
			}
		}
	}
}

function LineainteraccionHorizontal3InteVer() {
	var PrimerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz1-InteVer'
	);
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var CuartoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz4-InteVer'
	);
	var PrimerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz1-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var CuartoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz4-InteVer'
	);
	var PrimerLetrainthrzInteVer = document.getElementById('letra1v-InteVer');
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var CuartoLetrainthrzInteVer = document.getElementById('letra4v-InteVer');
	var lineavanceinthrz2InteVer = document.getElementById(
		'lineavanceinthrz2-InteVer'
	);
	var lineavanceinthrz3InteVer = document.getElementById(
		'lineavanceinthrz3-InteVer'
	);
	var contenedor1InteVer = document.getElementById('contenedor1-InteVer');
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra1InteVer = document.getElementById(
		'containerletra1InteVer'
	);
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	if (
		TercerIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-terminado-interaccion-InteVer-govco'
		)
	) {
		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			document.getElementById('contlineaver1-InteVer').style.display = 'none';
			document.getElementById('contlineaver2-InteVer').style.display = 'none';
			document.getElementById('contlineaver3-InteVer').style.display = 'block';
			document.getElementById('contlineaver4-InteVer').style.display = 'none';

			if (
				containerletra3InteVer.classList.contains(
					'container-letrainactiva-InteVer'
				)
			) {
				containerletra3InteVer.classList.remove(
					'container-letrainactiva-InteVer'
				);
				containerletra3InteVer.classList.toggle(
					'container-letrainicial-InteVer'
				);
			}

			if (
				containerletra2InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra2InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra2InteVer.classList.toggle(
					'container-letrainactiva-InteVer'
				);
			}

			if (
				contenedor3InteVer.classList.contains(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				)
			) {
				contenedor3InteVer.classList.remove(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
				contenedor3InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			if (
				contenedor2InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor2InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor2InteVer.classList.toggle(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
			}

			if (
				TercerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				TercerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				TercerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz3-InteVer').innerText =
					'3';

				if (
					TercerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					TercerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					TercerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					TercerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					TercerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					TercerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}

				if (
					containerletra3InteVer.classList.contains(
						'container-letrafinal-InteVer'
					)
				) {
					containerletra3InteVer.classList.remove(
						'container-letrafinal-InteVer'
					);
					containerletra3InteVer.classList.toggle(
						'container-letrainicial-InteVer'
					);
				}

				if (
					contenedor3InteVer.classList.contains(
						'linea-avance-interaccion-finalizada-InteVer-govco'
					)
				) {
					contenedor3InteVer.classList.remove(
						'linea-avance-interaccion-finalizada-InteVer-govco'
					);
					contenedor3InteVer.classList.toggle(
						'linea-avance-interaccion-InteVer-govco'
					);
				}
			}

			if (
				SegundoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				SegundoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				SegundoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);

				if (
					lineavanceinthrz2InteVer.classList.contains(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					)
				) {
					lineavanceinthrz2InteVer.classList.remove(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					);
					lineavanceinthrz2InteVer.classList.toggle(
						'progress-bar-activa-interaccion-finalizada-InteVer-govco'
					);
					document.getElementById('lineavanceinthrz2-InteVer').style.height =
						'45%';
				}
				document.getElementById('numeroindicadorinthrz2-InteVer').innerText =
					'';
				if (
					SegundoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					SegundoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					SegundoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					SegundoLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					SegundoLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					SegundoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}

		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			document.getElementById('contlineaver1-InteVer').style.display = 'none';
			document.getElementById('contlineaver2-InteVer').style.display = 'none';
			document.getElementById('contlineaver3-InteVer').style.display = 'block';
			document.getElementById('contlineaver4-InteVer').style.display = 'none';

			if (
				containerletra3InteVer.classList.contains(
					'container-letrainactiva-InteVer'
				)
			) {
				containerletra3InteVer.classList.remove(
					'container-letrainactiva-InteVer'
				);
				containerletra3InteVer.classList.toggle(
					'container-letrainicial-InteVer'
				);
			}

			if (
				containerletra1InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra1InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra1InteVer.classList.toggle(
					'container-letrainactiva-InteVer'
				);
			}

			if (
				contenedor3InteVer.classList.contains(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				)
			) {
				contenedor3InteVer.classList.remove(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
				contenedor3InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			if (
				contenedor1InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor1InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor1InteVer.classList.toggle(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
			}

			if (
				TercerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				TercerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				TercerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz3-InteVer').innerText =
					'3';

				if (
					TercerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					TercerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					TercerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					TercerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					TercerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					TercerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
			}

			if (
				PrimerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				PrimerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				PrimerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);

				if (
					lineavanceinthrz3InteVer.classList.contains(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					)
				) {
					lineavanceinthrz3InteVer.classList.remove(
						'progress-bar-activa-interaccion-inactivada-InteVer-govco'
					);
					lineavanceinthrz3InteVer.classList.toggle(
						'progress-bar-activa-interaccion-finalizada-InteVer-govco'
					);
					document.getElementById('lineavanceinthrz3-InteVer').style.height =
						'45%';
				}
				document.getElementById('numeroindicadorinthrz1-InteVer').innerText =
					'';
				if (
					PrimerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					PrimerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					PrimerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					PrimerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					PrimerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					PrimerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}

		if (
			CuartoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			if (
				containerletra3InteVer.classList.contains(
					'container-letrafinal-InteVer'
				)
			) {
				containerletra3InteVer.classList.remove('container-letrafinal-InteVer');
				containerletra3InteVer.classList.toggle(
					'container-letrainicial-InteVer'
				);
			}

			if (
				containerletra3InteVer.classList.contains(
					'container-letrainactiva-InteVer'
				)
			) {
				containerletra3InteVer.classList.remove(
					'container-letrainactiva-InteVer'
				);
				containerletra3InteVer.classList.toggle(
					'container-letrainicial-InteVer'
				);
			}

			if (
				contenedor3InteVer.classList.contains(
					'linea-avance-interaccion-finalizada-InteVer-govco'
				)
			) {
				contenedor3InteVer.classList.remove(
					'linea-avance-interaccion-finalizada-InteVer-govco'
				);
				contenedor3InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			if (
				contenedor3InteVer.classList.contains(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				)
			) {
				contenedor3InteVer.classList.remove(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
				contenedor3InteVer.classList.toggle(
					'linea-avance-interaccion-InteVer-govco'
				);
			}

			document.getElementById(
				'indicadorcontainerinthrzlinea4-InteVer'
			).style.display = 'none';

			document.getElementById('contlineaver1-InteVer').style.display = 'none';
			document.getElementById('contlineaver2-InteVer').style.display = 'none';
			document.getElementById('contlineaver3-InteVer').style.display = 'block';
			document.getElementById('contlineaver4-InteVer').style.display = 'none';

			if (
				TercerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				TercerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				TercerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz3-InteVer').innerText =
					'3';

				if (
					TercerLetrainthrzInteVer.classList.contains(
						'letra-indicador-terminado-interaccion-InteVer'
					)
				) {
					TercerLetrainthrzInteVer.classList.remove(
						'letra-indicador-terminado-interaccion-InteVer'
					);
					TercerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					TercerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					TercerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					TercerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
			}

			if (
				CuartoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				CuartoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				CuartoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz4-InteVer').innerText =
					'';
				if (
					CuartoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					CuartoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					CuartoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					CuartoLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					CuartoLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					CuartoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}
	}
}

function LineainteraccionHorizontal4InteVer() {
	var PrimerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz1-InteVer'
	);
	var SegundoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz2-InteVer'
	);
	var TercerIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz3-InteVer'
	);
	var CuartoIteminthrzInteVer = document.getElementById(
		'indicadorlineainthrz4-InteVer'
	);
	var PrimerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz1-InteVer'
	);
	var SegundoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz2-InteVer'
	);
	var TercerIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz3-InteVer'
	);
	var CuartoIndicadorinthrzInteVer = document.getElementById(
		'numeroindicadorinthrz4-InteVer'
	);
	var PrimerLetrainthrzInteVer = document.getElementById('letra1v-InteVer');
	var SegundoLetrainthrzInteVer = document.getElementById('letra2v-InteVer');
	var TercerLetrainthrzInteVer = document.getElementById('letra3v-InteVer');
	var CuartoLetrainthrzInteVer = document.getElementById('letra4v-InteVer');
	var contenedor1InteVer = document.getElementById('contenedor1-InteVer');
	var contenedor2InteVer = document.getElementById('contenedor2-InteVer');
	var contenedor3InteVer = document.getElementById('contenedor3-InteVer');
	var containerletra1InteVer = document.getElementById(
		'containerletra1InteVer'
	);
	var containerletra2InteVer = document.getElementById(
		'containerletra2InteVer'
	);
	var containerletra3InteVer = document.getElementById(
		'containerletra3InteVer'
	);

	if (
		CuartoIteminthrzInteVer.classList.contains(
			'indicador-linea-avance-terminado-interaccion-InteVer-govco'
		)
	) {
		if (
			PrimerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			if (
				containerletra1InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra1InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra1InteVer.classList.toggle(
					'container-letrainactiva-InteVer'
				);
			}

			if (
				contenedor1InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor1InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor1InteVer.classList.toggle(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
			}

			document.getElementById(
				'indicadorcontainerinthrzlinea4-InteVer'
			).style.display = 'block';
			if (
				CuartoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				CuartoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				CuartoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz4-InteVer').innerText =
					'4';

				document.getElementById('contlineaver1-InteVer').style.display = 'none';
				document.getElementById('contlineaver2-InteVer').style.display = 'none';
				document.getElementById('contlineaver3-InteVer').style.display = 'none';
				document.getElementById('contlineaver4-InteVer').style.display =
					'block';

				if (
					CuartoLetrainthrzInteVer.classList.contains(
						'letra-indicador-terminado-interaccion-InteVer'
					)
				) {
					CuartoLetrainthrzInteVer.classList.remove(
						'letra-indicador-terminado-interaccion-InteVer'
					);
					CuartoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					CuartoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					CuartoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					CuartoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
			}

			if (
				PrimerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				PrimerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				PrimerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz1-InteVer').innerText =
					'';
				if (
					PrimerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					PrimerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					PrimerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					PrimerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					PrimerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					PrimerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}

		if (
			SegundoIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			if (
				containerletra2InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra2InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra2InteVer.classList.toggle(
					'container-letrainactiva-InteVer'
				);
			}

			if (
				contenedor2InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor2InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor2InteVer.classList.toggle(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
			}

			document.getElementById(
				'indicadorcontainerinthrzlinea4-InteVer'
			).style.display = 'block';
			if (
				CuartoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				CuartoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				CuartoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz4-InteVer').innerText =
					'4';

				document.getElementById('contlineaver1-InteVer').style.display = 'none';
				document.getElementById('contlineaver2-InteVer').style.display = 'none';
				document.getElementById('contlineaver3-InteVer').style.display = 'none';
				document.getElementById('contlineaver4-InteVer').style.display =
					'block';

				if (
					CuartoLetrainthrzInteVer.classList.contains(
						'letra-indicador-terminado-interaccion-InteVer'
					)
				) {
					CuartoLetrainthrzInteVer.classList.remove(
						'letra-indicador-terminado-interaccion-InteVer'
					);
					CuartoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					CuartoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					CuartoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					CuartoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
			}

			if (
				SegundoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				SegundoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				SegundoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz2-InteVer').innerText =
					'';
				if (
					SegundoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					SegundoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					SegundoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					SegundoLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					SegundoLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					SegundoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}

		if (
			TercerIteminthrzInteVer.classList.contains(
				'indicador-linea-avance-activo-interaccion-InteVer-govco'
			)
		) {
			if (
				containerletra3InteVer.classList.contains(
					'container-letrainicial-InteVer'
				)
			) {
				containerletra3InteVer.classList.remove(
					'container-letrainicial-InteVer'
				);
				containerletra3InteVer.classList.toggle(
					'container-letrainactiva-InteVer'
				);
			}

			if (
				contenedor3InteVer.classList.contains(
					'linea-avance-interaccion-InteVer-govco'
				)
			) {
				contenedor3InteVer.classList.remove(
					'linea-avance-interaccion-InteVer-govco'
				);
				contenedor3InteVer.classList.toggle(
					'linea-avance-interaccion-inactivada-InteVer-govco'
				);
			}

			document.getElementById(
				'indicadorcontainerinthrzlinea4-InteVer'
			).style.display = 'block';
			if (
				CuartoIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				)
			) {
				CuartoIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				CuartoIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz4-InteVer').innerText =
					'4';

				document.getElementById('contlineaver1-InteVer').style.display = 'none';
				document.getElementById('contlineaver2-InteVer').style.display = 'none';
				document.getElementById('contlineaver3-InteVer').style.display = 'none';
				document.getElementById('contlineaver4-InteVer').style.display =
					'block';

				if (
					CuartoLetrainthrzInteVer.classList.contains(
						'letra-indicador-terminado-interaccion-InteVer'
					)
				) {
					CuartoLetrainthrzInteVer.classList.remove(
						'letra-indicador-terminado-interaccion-InteVer'
					);
					CuartoLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}

				if (
					CuartoIndicadorinthrzInteVer.classList.contains(
						'num-indicador-terminado-interaccion-InteVer-govco'
					)
				) {
					CuartoIndicadorinthrzInteVer.classList.remove(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
					CuartoIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
				}
			}

			if (
				TercerIteminthrzInteVer.classList.contains(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				)
			) {
				TercerIteminthrzInteVer.classList.remove(
					'indicador-linea-avance-activo-interaccion-InteVer-govco'
				);
				TercerIteminthrzInteVer.classList.toggle(
					'indicador-linea-avance-terminado-interaccion-InteVer-govco'
				);
				document.getElementById('numeroindicadorinthrz3-InteVer').innerText =
					'';
				if (
					TercerIndicadorinthrzInteVer.classList.contains(
						'num-indicador-activo-interaccion-InteVer-govco'
					)
				) {
					TercerIndicadorinthrzInteVer.classList.remove(
						'num-indicador-activo-interaccion-InteVer-govco'
					);
					TercerIndicadorinthrzInteVer.classList.toggle(
						'num-indicador-terminado-interaccion-InteVer-govco'
					);
				}
				if (
					TercerLetrainthrzInteVer.classList.contains(
						'letra-indicador-activo-interaccion-InteVer'
					)
				) {
					TercerLetrainthrzInteVer.classList.remove(
						'letra-indicador-activo-interaccion-InteVer'
					);
					TercerLetrainthrzInteVer.classList.toggle(
						'letra-indicador-activo-interaccion-InteVer'
					);
				}
			}
		}
	}
}

function focusInteraccionVerEnter(textChange) {
	if (
		document
			.getElementById('indicadorlineainthrz' + textChange + '-InteVer')
			.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			)
	) {
		document.getElementById(
			'letra' + textChange + 'v-InteVer'
		).style.fontFamily = 'WorkSans-Medium';
	}
}

function focusInteraccionVerLeave(textChange) {
	if (
		document
			.getElementById('indicadorlineainthrz' + textChange + '-InteVer')
			.classList.contains(
				'indicador-linea-avance-inactivo-interaccion-InteVer-govco'
			)
	) {
		document
			.getElementById('letra' + textChange + 'v-InteVer')
			.removeAttribute('style');
	}
}
