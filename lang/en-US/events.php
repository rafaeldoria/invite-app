<?php

return [
    'attributes' => [
        'name' => 'event name',
        'description' => 'description',
        'starts_date' => 'event date',
        'starts_time' => 'event time',
        'timezone' => 'timezone',
        'location' => 'location',
        'theme' => 'theme',
        'share_message' => 'share message',
        'cover_image' => 'cover image',
        'remove_cover_image' => 'remove cover image',
    ],
    'messages' => [
        'created' => 'Event created.',
        'updated' => 'Event updated.',
        'deleted' => 'Event deleted.',
        'share_updated' => 'Share message updated.',
        'save_failed' => 'The event could not be saved. Please try again.',
    ],
    'validation' => [
        'invalid_start' => 'Enter a valid event date and time.',
        'future_start' => 'Choose a future date and time for the event.',
    ],
    'share' => [
        'default_message' => 'You are invited to :name.',
        'summary' => "Date and time: :date\nLocation: :location",
    ],
];
