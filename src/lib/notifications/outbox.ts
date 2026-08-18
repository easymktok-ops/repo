import type { NotificationChannel, NotificationMessage, NotificationResult } from "./types";

/**
 * OUTBOX con reintentos y backoff. Este es el corazon anti-"sin correo enviado".
 *
 * - Reintenta ante fallo (backoff exponencial con tope).
 * - Devuelve SIEMPRE un NotificationResult (nunca lanza hacia arriba): el
 *   llamador puede persistir el resultado en la reserva (enviado / fallido +
 *   error + intentos) para monitoreo. En Fase 3 este resultado se guarda y un
 *   job de barrido reintenta los `ok:false` pendientes.
 */

export interface OutboxOptions {
  maxAttempts?: number; // default 3
  baseDelayMs?: number; // default 400ms
  onAttempt?: (result: NotificationResult, attempt: number) => void; // hook de logging/persistencia
}

export async function sendWithRetry(
  channel: NotificationChannel,
  message: NotificationMessage,
  options: OutboxOptions = {},
): Promise<NotificationResult> {
  const maxAttempts = options.maxAttempts ?? 3;
  const baseDelayMs = options.baseDelayMs ?? 400;

  let last: NotificationResult = {
    ok: false,
    channel: channel.channel,
    to: message.to,
    bookingId: message.bookingId,
    error: "sin intentos",
    attemptedAt: new Date().toISOString(),
  };

  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      last = await channel.send(message);
    } catch (err) {
      last = {
        ok: false,
        channel: channel.channel,
        to: message.to,
        bookingId: message.bookingId,
        error: err instanceof Error ? err.message : String(err),
        attemptedAt: new Date().toISOString(),
      };
    }

    options.onAttempt?.(last, attempt);
    if (last.ok) return last;

    // Backoff exponencial (400ms, 800ms, ...) con tope de 8s. No espera tras
    // el ultimo intento.
    if (attempt < maxAttempts) {
      const delay = Math.min(baseDelayMs * 2 ** (attempt - 1), 8000);
      await sleep(delay);
    }
  }

  // Fallo definitivo: se devuelve el ultimo resultado para persistir/monitorear.
  return last;
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
