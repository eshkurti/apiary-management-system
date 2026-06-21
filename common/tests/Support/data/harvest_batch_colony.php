<?php

declare(strict_types=1);

// Source-colony links:
//   batch 1 (clean)    ← colony 2 (eligible)
//   batch 2 (blocked)  ← colony 1 (within Wartezeit at harvest)
//   batch 3 (released) ← colony 2 (disease-flag cascade target)
return [
    'link_clean'    => ['batch_id' => 1, 'colony_id' => 2],
    'link_blocked'  => ['batch_id' => 2, 'colony_id' => 1],
    'link_released' => ['batch_id' => 3, 'colony_id' => 2],
];
