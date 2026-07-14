<?php

return [
    'attributes' => [
        'name' => 'name',
        'contact' => 'contact',
        'subject' => 'subject',
        'message' => 'message',
    ],
    'messages' => [
        'sent' => 'Support message sent.',
        'send_failed' => 'We received your message, but could not email support right now. Please try again in a few minutes.',
    ],
    'validation' => [
        'contact' => 'Enter a valid email address or a WhatsApp number with area code.',
    ],
];
