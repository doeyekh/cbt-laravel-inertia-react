<?php

use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [
    'backup' => [
        'name' => env('APP_NAME', 'cbt'),

        'source' => [
            'files' => [
                // Full backup mencakup file aplikasi; backup database memakai --only-db.
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('.git'),
                    base_path('.env'),
                    storage_path('framework'),
                    storage_path('logs'),
                    storage_path('app/backup-temp'),
                    storage_path('app/private/backups'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],

            'databases' => [env('DB_CONNECTION', 'sqlite')],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',
        'database_dump_filename_base' => 'connection',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => [env('BACKUP_DISK', 'backups')],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => env('BACKUP_ARCHIVE_PASSWORD') ? 'default' : 'none',
        'verify_backup' => true,

        'tries' => 1,
        'retry_delay' => 0,
    ],

    // Matikan notifikasi default.
    'notifications' => [
        'notifications' => [],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'cbt'),
            'disks' => [env('BACKUP_DISK', 'backups')],
            'health_checks' => [
                MaximumAgeInDays::class => 2,
                MaximumStorageInMegabytes::class => 2048,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 14,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 2048,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
