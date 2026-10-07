<?php
// Contenido por defecto del sitio. El panel de administración guarda sus
// cambios en storage/content.json y esos valores se superponen a estos.
//
// Origen de los textos (no se inventa copy):
// - Sitio actual (pantallazo 2026-09-30): tagline, títulos de sección,
//   programación, formulario, footer.
// - "e Reseña Joyas Colombianas Dinner & Show®.pdf" (Drive): experiencia y
//   "¿Qué es un Dinner Show?". Nombre normalizado a "Joyas Colombianas® Dinner & Show".
// - Brief del proyecto: precios, puertas, dress code.
// Todo texto marcado {{...}} lo entrega John; el resto es provisional hasta su contenido SEO.
return [
    'site' => [
        'name'             => 'Joyas Colombianas® Dinner & Show',
        'cities_line'      => 'Medellín & Bogotá',
        'whatsapp_number'  => '',
        'whatsapp_message' => 'Hola, quiero información sobre Joyas Colombianas® Dinner & Show',
        'instagram'        => 'https://www.instagram.com/joyascolombianas.show/',
        'facebook'         => '',
        'tripadvisor'      => '',
    ],

    'seo' => [
        'home' => [
            'title'       => 'Joyas Colombianas® Dinner & Show',
            'description' => '{{META_DESCRIPTION_HOME}}',
        ],
    ],

    'hero' => [
        'cta_label'      => 'Compra de entradas aquí',
        'whatsapp_label' => 'Escríbenos por WhatsApp',
        'video_src'      => '',
    ],

    'statement' => 'Una explosión de ritmos y sabores para descubrir los tesoros de Colombia',

    'experiencia' => [
        'title' => 'Dinner & Show Joyas Colombianas®',
        'body'  => [
            'Joyas Colombianas® Dinner & Show es un homenaje profundo y vibrante a la riqueza cultural de nuestro país. Más que un espectáculo, es una experiencia sensorial donde el arte, la gastronomía y el folclor se integran para narrar la historia viva de Colombia.',
            'A través de puestas en escena que combinan música, danza, narrativas visuales y propuestas culinarias inspiradas en distintas regiones, el evento invita al público a conectarse con los tesoros culturales de Colombia: su gente, sus raíces, sus sabores y su identidad.',
        ],
    ],

    'ciudades' => [
        'title' => 'Dinner & Show Joyas Colombianas®',
    ],

    'programacion' => [
        'title'  => 'Programación Joyas Colombianas® 2026',
        'events' => [
            [
                'id'         => 'medellin',
                'city'       => 'Medellín',
                'summary'    => 'Funciones regulares todos los sábados',
                'weekday'    => 6,
                'time'       => '20:00',
                'doors'      => '19:45',
                'venue'      => 'Hotel Marriott Medellín',
                'dress_code' => 'Formal',
                'buyable'    => true,
                'prices'     => [
                    ['sku' => 'menu', 'label' => 'Menú completo', 'amount' => 195000],
                    ['sku' => 'tapeo', 'label' => 'Tapeo', 'amount' => 180000],
                ],
                'cta_label'  => 'Compra de entradas aquí',
                'cta_url'    => '/comprar/?funcion=medellin',
            ],
            [
                'id'         => 'bogota',
                'city'       => 'Bogotá',
                'summary'    => 'Funciones privadas',
                'weekday'    => null,
                'time'       => '',
                'doors'      => '',
                'venue'      => 'Hotel Tequendama',
                'dress_code' => 'Formal',
                'buyable'    => false,
                'prices'     => [],
                'cta_label'  => 'Ver más',
                'cta_url'    => '/bogota/',
            ],
        ],
    ],

    'que_es' => [
        'title' => '¿Qué es un Dinner Show?',
        'body'  => [
            'El concepto Dinner & Show es una propuesta de entretenimiento que combina una experiencia gastronómica completa con un espectáculo en vivo. Su esencia radica en ofrecer, en un mismo espacio y durante una misma velada, una cena cuidadosamente diseñada y una puesta en escena artística de alto nivel.',
            'A nivel mundial se ha convertido en una de las modalidades de ocio más valoradas, especialmente en grandes capitales culturales y turísticas: un plan integral donde la gastronomía, la música y la danza se convierten en un solo producto artístico.',
        ],
    ],

    'contacto' => [
        'title'        => 'Comunícate con nosotros',
        'submit_label' => 'Enviar solicitud',
        'privacy_url'  => '/politica-de-datos/',
    ],

    'footer' => [
        'review_quote'  => '{{RESEÑA_TRIPADVISOR}}',
        'review_author' => '{{AUTOR_RESEÑA}}',
    ],
];
