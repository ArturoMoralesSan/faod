/**
 * Datos e imágenes de la facultad para la página pública y el inicio de sesión.
 * Fuente: https://www.ujed.mx/facultad-de-odontologia
 */
export const INSTITUTION = {
    university: 'Universidad Juárez del Estado de Durango',
    universityShort: 'UJED',
    name: 'Facultad de Odontología',
    short: 'FAOD',
    system: 'Sistema integral de gestión interna',
    purpose:
        'Formar profesionales comprometidos con la atención odontológica y el abordaje científico de las enfermedades bucales, con una formación ética y humanista que abarca la promoción, prevención, diagnóstico, tratamiento y rehabilitación bucal en todos los grupos de edad.',
    address: 'Predio Canoas s/n, Col. Los Ángeles, C.P. 34070, Durango, Dgo.',
    phone: '(618) 827 13 85',
    phoneHref: 'tel:+526188271385',
    email: 'faodonto@ujed.mx',
    website: 'https://www.ujed.mx/facultad-de-odontologia',
    map: 'https://www.google.com/maps/d/edit?hl=es&authuser=0&mid=1E8JhnMCAcZZpwRRLZFDcQc5BNDw&z=17',
};

/**
 * Fotografías. La fachada usa por ahora la panorámica del campus publicada por la UJED;
 * para el inicio de sesión conviene reemplazarla por una foto vertical en alta resolución
 * con el mismo nombre (public/images/faod/fachada.jpg) y cambiar la ruta aquí.
 */
export const IMAGES = {
    facade: '/images/faod/campus-panoramica.jpg',
    // Encuadre de la fachada dentro de la panorámica (el edificio con el letrero está a la izquierda).
    facadePosition: '14% center',
    campus: '/images/faod/campus-panoramica.jpg',
    logoUjedWhite: '/images/faod/logo-ujed-blanco.png',
};
