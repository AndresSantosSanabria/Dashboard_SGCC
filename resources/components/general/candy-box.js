let itemsDropdownCandy = document.querySelectorAll('.dropdown-item-govco');
itemsDropdownCandy.forEach((element) => {
	if (element.classList.contains('disabled')) {
		element.setAttribute('tabIndex', -1);
	} else {
		element.removeAttribute('tabIndex');
	}
});

function activeItemCandy(itemSelected) {
	const itemsCollection = document.querySelectorAll('.dropdown-item-govco');
	itemsCollection.forEach((element) => {
		element.classList.remove('active-select');
	});
	document.getElementById(itemSelected).classList.add('active-select');
}
