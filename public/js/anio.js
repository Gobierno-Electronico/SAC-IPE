document.addEventListener("DOMContentLoaded", function () {
    const anioGuardado = localStorage.getItem('añoSelect');

    if (!anioGuardado) return;

    const hoy = new Date();
    const anioActual = hoy.getFullYear();

    const fechaMin = `${anioGuardado}-01-01`;

    let fechaMax;
    if (parseInt(anioGuardado) === anioActual) {
        fechaMax = hoy.toISOString().split('T')[0];
    } else {
        fechaMax = `${anioGuardado}-12-31`;
    }

    const inputFecha = document.getElementById('inputFechaAfectacion');
    const inputFecha2 = document.getElementById('inputFechaRegistro');

    if (inputFecha) {
        inputFecha.min = fechaMin;
        inputFecha.max = fechaMax;
    }

    if (inputFecha2) {
        inputFecha2.min = fechaMin;
        inputFecha2.max = fechaMax;
    }
});