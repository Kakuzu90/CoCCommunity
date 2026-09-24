<?php

return [
    // FR-COC-2: bounds exclude the leading #. These describe tag syntax, not existence.
    'tags' => [
        'min_length' => 3,
        'max_length' => 12,
        'alphabet' => '0289PYLQGRJCUV',
    ],

    // No maximum: future Town Hall levels must work without a deployment (spec 23 §5).
    // Publishing's minimum is a separate Bases policy, not an account identity constraint.
    'th_min_level' => 1,
];
