<?php
// NexusPag server-side configuration.
// Set the API key as an environment variable named NEXUSPAG_API_KEY.
// For shared hosting without environment variables, you may uncomment the
// fallback below, but do not commit the key to a public repository.
function nexuspag_api_key(): string {
    $key = getenv('NEXUSPAG_API_KEY');
    if (!$key) {
        $key = ''; // Put your NexusPag key here only if your host has no env support.
    }
    if (!$key) {
        throw new RuntimeException('NexusPag API key is not configured.');
    }
    return $key;
}
