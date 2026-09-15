/**
 * Resuelve rutas de archivos de /public respetando la base del build.
 *
 * Con `base: './'` el sitio funciona igual servido desde la raíz del dominio
 * que desde una subcarpeta (kosecha.fit/ o kosecha.fit/prueba/), que es lo que
 * hace falta para subirlo a mano a un hosting.
 */
export const ruta = (camino) => `${import.meta.env.BASE_URL}${String(camino).replace(/^\//, '')}`;
