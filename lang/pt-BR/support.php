<?php

return [
    'attributes' => [
        'name' => 'nome',
        'contact' => 'contato',
        'subject' => 'assunto',
        'message' => 'mensagem',
    ],
    'messages' => [
        'sent' => 'Mensagem enviada ao suporte.',
        'send_failed' => 'Recebemos sua mensagem, mas não foi possível enviá-la ao suporte agora. Tente novamente em alguns minutos.',
    ],
    'validation' => [
        'contact' => 'Informe um e-mail válido ou um WhatsApp com DDD.',
    ],
];
