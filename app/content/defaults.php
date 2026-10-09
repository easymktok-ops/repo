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
                    [
                        'sku' => 'menu', 'label' => 'Menú completo', 'tag' => '3 tiempos', 'amount' => 195000,
                        'courses' => [
                            'Entrada' => 'Ceviche cartagenero de camarón, aguacate y crocante de plátano',
                            'Plato' => 'Posta negra cartagenera, acompañada de puré de papa criolla y vegetales salteados',
                            'Postre' => 'Tres leches del famoso Café Colombiano',
                            'Bebida' => '1 copa de vino o 1 cerveza nacional',
                        ],
                    ],
                    [
                        'sku' => 'vegetariano', 'label' => 'Menú vegetariano', 'tag' => '3 tiempos', 'amount' => 195000,
                        'courses' => [
                            'Entrada' => 'Ceviche de mango y champiñones confitados, leche de tigre de chontaduro y crocante de plátano',
                            'Plato vegetariano' => 'Croqueta de lentejas sobre arroz cremoso de maíz',
                            'Postre' => 'Panna cotta de gulupa',
                            'Bebida' => '1 copa de vino o 1 cerveza nacional',
                        ],
                    ],
                    [
                        'sku' => 'tapeo', 'label' => 'Para picar / Tapeo', 'tag' => 'Tapeo', 'amount' => 180000,
                        'courses' => [
                            'Entradas, con guacamole y suero costeño' => 'Pork belly en reducción de panela sobre arepa de maíz dulce y queso costeño; aborrajados con relleno de carne estofada y queso; papa rellena, maíz dulce, chorizo campesino y sofrito colombiano',
                            'Bebida' => '1 copa de vino o 1 cerveza nacional',
                        ],
                    ],
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

    // Landings de campaña: sencillas, sin indexar, con un solo objetivo. Los datos (lugar, hora, precios)
    // salen de programacion.events; aquí solo van los textos propios de cada landing.
    'landings' => [
        'medellin' => [
            'event'       => 'medellin',
            'mode'        => 'buy',
            'meta_title'  => 'Entradas Joyas Colombianas® Dinner & Show en Medellín',
            'h1'          => 'Joyas Colombianas® Dinner & Show en Medellín',
            'lead'        => 'Una explosión de ritmos y sabores para descubrir los tesoros de Colombia',
            'cta_label'   => 'Comprar entradas',
            'wa_message'  => 'Hola, quiero información sobre las entradas para Joyas Colombianas® Dinner & Show en Medellín',
        ],
        'cartagena' => [
            'event'       => 'cartagena',
            'mode'        => 'reserve',
            'meta_title'  => 'Reserva tu mesa en La Oveja, Cartagena | Joyas Colombianas®',
            'h1'          => 'Reserva tu mesa en La Oveja, Cartagena',
            'lead'        => 'Asegura tu mesa con un cover de USD 5 por persona',
            'cta_label'   => 'Reservar mesa',
            'wa_message'  => 'Hola, quiero información sobre reservas en La Oveja, Cartagena',
        ],
        'bogota' => [
            'event'       => 'bogota',
            'mode'        => 'quote',
            'meta_title'  => 'Dinner & Show para empresas y eventos privados en Bogotá | Joyas Colombianas®',
            'h1'          => 'Joyas Colombianas® Dinner & Show para empresas y eventos privados en Bogotá',
            'lead'        => 'Una explosión de ritmos y sabores para descubrir los tesoros de Colombia',
            'cta_label'   => 'Cotizar mi evento',
            'wa_message'  => 'Hola, quiero cotizar un Dinner & Show privado de Joyas Colombianas® en Bogotá',
        ],
    ],

    // Reservas con cover (no son función propia del show): no aparecen en la programación de la home.
    'reservas' => [
        [
            'id'           => 'cartagena',
            'kind'         => 'reservation',
            'city'         => 'Cartagena',
            'venue'        => 'La Oveja',
            'summary'      => 'Reserva de mesa con cover de USD 5 por persona',
            'weekdays'     => [4, 5, 6],
            'time'         => '',
            'doors'        => '',
            'dress_code'   => '',
            'capacity'     => 50,
            'max_party'    => 6,
            'lead_days'    => 1,
            'dates_offered' => 12,
            'buyable'      => true,
            'prices'       => [
                ['sku' => 'cover', 'label' => 'Cover de reserva, por persona', 'usd' => 5],
            ],
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
