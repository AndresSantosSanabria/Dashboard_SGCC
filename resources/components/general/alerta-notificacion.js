document.addEventListener("DOMContentLoaded", function () {
    let fixedToast = document.getElementById("fixedtoast");
    fixedToast.classList.add("show");
})

function closeAlert() {
    document.getElementById('closealertcontainer').style.display = 'none';
}

function openAlert() {
    document.getElementById('closealertcontainer').style.display = 'flex';
    document.getElementById('closealert').style.cssText = 'position: fixed; bottom: 23px; width: 100%; z-index: 2; display: flex;';
}

function openLiveToast() {
    document.getElementById('closetoastcontainer').style.display = 'flex';
    document.getElementById("livetoast").classList.add("show");
    document.getElementById("livetoast").setAttribute('style', 'display: block !important;');
    document.getElementById('toastlivecontainer').style.cssText = 'position: fixed; top: 0; right: 0; z-index: 2;';
}

function closeLiveToast() {
    document.getElementById('closetoastcontainer').style.display = 'none';
}

function closeToast(toast_name) {
    document.getElementById(toast_name).setAttribute('style', 'display: none !important;');
}