import type { APIRoute } from "astro";
import { absoluteUrl } from "@config/site";

/**
 * robots.txt generado en build. La URL del sitemap se construye con site.url
 * (de env), asi que es dominio-agnostico: al cambiar de entorno, apunta solo.
 * El panel de administracion (/admin) se excluye del rastreo.
 */
export const GET: APIRoute = () => {
  const body = [
    "User-agent: *",
    "Allow: /",
    "Disallow: /admin",
    "",
    `Sitemap: ${absoluteUrl("/sitemap-index.xml")}`,
    "",
  ].join("\n");

  return new Response(body, {
    headers: { "Content-Type": "text/plain; charset=utf-8" },
  });
};
