<?php

return [
    'fake' => (bool) env('ASSISTANT_FAKE', false),
    'rate_limit' => (int) env('ASSISTANT_RATE_LIMIT', 10),
];
