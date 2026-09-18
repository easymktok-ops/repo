import { horarios } from '../data/contenido.js';

/**
 * Estado del local calculado en la hora de Libres, no en la del visitante:
 * alguien mirando la página desde otro huso tiene que ver si está abierto allá,
 * no acá.
 */
const DIAS_ES = {
  Monday: 'lunes',
  Tuesday: 'martes',
  Wednesday: 'miércoles',
  Thursday: 'jueves',
  Friday: 'viernes',
  Saturday: 'sábado',
  Sunday: 'domingo',
};
const ORDEN = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const aMinutos = (hhmm) => {
  const [h, m] = hhmm.split(':').map(Number);
  return h * 60 + m;
};

/** "09:00" → "9:00" */
export const formatoHora = (hhmm) => hhmm.replace(/^0/, '');

export function horaEnLibres(fecha = new Date()) {
  const partes = Object.fromEntries(
    new Intl.DateTimeFormat('en-US', {
      timeZone: horarios.zona,
      weekday: 'long',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    })
      .formatToParts(fecha)
      .map((p) => [p.type, p.value]),
  );
  // Algunos entornos devuelven "24" para la medianoche.
  const hora = Number(partes.hour) % 24;
  return { dia: partes.weekday, minutos: hora * 60 + Number(partes.minute) };
}

const tramoDe = (dia) => horarios.tramos.find((t) => t.dias.includes(dia) && t.abre);

/** { abierto, cierra } o { abierto: false, proximo: { cuando, hora } } */
export function estadoDelLocal(fecha = new Date()) {
  const { dia, minutos } = horaEnLibres(fecha);
  const hoy = tramoDe(dia);

  if (hoy) {
    if (minutos >= aMinutos(hoy.abre) && minutos < aMinutos(hoy.cierra)) {
      return { abierto: true, cierra: hoy.cierra };
    }
    if (minutos < aMinutos(hoy.abre)) {
      return { abierto: false, proximo: { cuando: 'hoy', hora: hoy.abre } };
    }
  }

  const desde = ORDEN.indexOf(dia);
  for (let salto = 1; salto <= 7; salto += 1) {
    const siguiente = ORDEN[(desde + salto) % 7];
    const tramo = tramoDe(siguiente);
    if (tramo) {
      return {
        abierto: false,
        proximo: { cuando: salto === 1 ? 'mañana' : `el ${DIAS_ES[siguiente]}`, hora: tramo.abre },
      };
    }
  }
  return { abierto: false };
}
