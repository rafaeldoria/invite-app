<?php

return [
    'attributes' => [
        'name' => 'nome do evento',
        'description' => 'descrição',
        'starts_date' => 'data do evento',
        'starts_time' => 'hora do evento',
        'timezone' => 'fuso horário',
        'location' => 'local',
        'theme' => 'tema',
        'cover_image' => 'imagem de capa',
        'remove_cover_image' => 'remover imagem de capa',
    ],
    'messages' => [
        'created' => 'Evento criado.',
        'updated' => 'Evento atualizado.',
        'deleted' => 'Evento excluído.',
        'save_failed' => 'Não foi possível salvar o evento. Tente novamente.',
    ],
    'validation' => [
        'invalid_start' => 'Informe uma data e hora válidas para o evento.',
        'future_start' => 'Escolha uma data e hora futuras para o evento.',
    ],
];
