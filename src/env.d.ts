/// <reference path="../.astro/types.d.ts" />

/**
 * Tipado de variables de entorno.
 * REGLA: nada de dominios ni secretos hardcodeados en el codigo. Todo entra
 * por aqui. Las PUBLIC_* se exponen al cliente; el resto son solo de servidor
 * (build / edge functions de Fase 3) y NUNCA deben referenciarse en componentes
 * que se hidraten en el navegador.
 */
interface ImportMetaEnv {
  // --- Dominio / marca (publicas) ---
  readonly PUBLIC_SITE_URL: string; // ej. https://staging.midominio.mx  (sin barra final)
  readonly PUBLIC_SITE_NAME: string; // "Aerodiverti"
  readonly PUBLIC_DEFAULT_LOCALE: "es" | "en";
  readonly PUBLIC_WHATSAPP_DISPLAY_NUMBER?: string; // numero visible para el boton flotante

  // --- Analitica (publicas; los IDs se exponen en el cliente por diseño) ---
  readonly PUBLIC_GA_ID?: string; // GA4  (G-XXXX)
  readonly PUBLIC_GOOGLE_ADS_ID?: string; // Google Ads (AW-XXXX)
  readonly PUBLIC_META_PIXEL_ID?: string; // Meta Pixel (numerico)
  readonly PUBLIC_PLAUSIBLE_DOMAIN?: string;

  // --- Pagos: Stripe (secreto en servidor, publishable en cliente) ---
  readonly PUBLIC_STRIPE_PUBLISHABLE_KEY?: string;
  readonly STRIPE_SECRET_KEY?: string;
  readonly STRIPE_WEBHOOK_SECRET?: string;

  // --- Notificaciones (proveedor por definir en Fase 3; interfaz ya existe) ---
  readonly NOTIFICATIONS_EMAIL_PROVIDER?: "resend" | "sendgrid" | "log";
  readonly NOTIFICATIONS_EMAIL_API_KEY?: string;
  readonly NOTIFICATIONS_EMAIL_FROM?: string;
  readonly NOTIFICATIONS_WHATSAPP_PROVIDER?: "meta_cloud" | "twilio" | "log";
  readonly NOTIFICATIONS_WHATSAPP_TOKEN?: string;
  readonly NOTIFICATIONS_WHATSAPP_PHONE_ID?: string;
  readonly NOTIFICATIONS_ADMIN_EMAIL?: string; // copia al administrador
  readonly NOTIFICATIONS_ADMIN_WHATSAPP?: string;

  // --- CMS (Decap / GitHub backend) ---
  readonly PUBLIC_CMS_REPO?: string; // "org/repo" para el backend GitHub de Decap
  readonly PUBLIC_CMS_BRANCH?: string; // rama que edita el CMS
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
