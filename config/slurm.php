<?php
// config/slurm.php

return [
    'host' => env('SLURM_HOST'),
    'username' => env('SLURM_USERNAME'),
    'private_key_path' => env('SLURM_PRIVATE_KEY_PATH'),
    'remote_work_dir' => env('SLURM_REMOTE_WORK_DIR', '/tmp/laravel_jobs'),
    
    // outras configurações...
];