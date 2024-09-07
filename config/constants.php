<?php

return [
    'project_roles' => [
        'project_admin',
        'project_user',
    ],

    'project_task_progress' => [
        'Not started',
        'In progress',
        'Reviewing',
        'Completed',
        'Backlog',
    ],

    'project_task_priority_level' => [
        'Low',
        'Medium',
        'High',
    ],

    'dtr_schedules' => [
        'early_shift' => [
            'full_time' => [
                'start_time' => '06:00:00', // 6:00 AM
                'end_time' => '14:00:00',   // 2:00 PM
            ],
            'part_time' => [
                'start_time' => '06:00:00', // 6:00 AM
                'end_time' => '10:00:00',   // 10:00 AM
            ],
        ],
        'day_shift' => [
            'full_time' => [
                'start_time' => '08:00:00', // 8:00 AM
                'end_time' => '16:00:00',   // 4:00 PM
            ],
            'part_time' => [
                'start_time' => '08:00:00', // 8:00 AM
                'end_time' => '12:00:00',   // 12:00 PM
            ],
        ],
        'afternoon_shift' => [
            'full_time' => [
                'start_time' => '12:00:00', // 12:00 PM
                'end_time' => '20:00:00',   // 8:00 PM
            ],
            'part_time' => [
                'start_time' => '12:00:00', // 12:00 PM
                'end_time' => '16:00:00',   // 4:00 PM
            ],
        ],
        'night_shift' => [
            'full_time' => [
                'start_time' => '22:00:00', // 10:00 PM
                'end_time' => '06:00:00',   // 6:00 AM (next day)
            ],
            'part_time' => [
                'start_time' => '22:00:00', // 10:00 PM
                'end_time' => '02:00:00',   // 2:00 AM (next day)
            ],
        ],
        'evening_shift' => [
            'full_time' => [
                'start_time' => '14:00:00', // 2:00 PM
                'end_time' => '22:00:00',   // 10:00 PM
            ],
            'part_time' => [
                'start_time' => '14:00:00', // 2:00 PM
                'end_time' => '18:00:00',   // 6:00 PM
            ],
        ],
    ],

    'leave_requests' => [
        'daily_limit' => 2,
        'monthly_limit' => 10,
        'yearly_limit' => 30,
        'evaluation_period_in_days' => 14,
    ],
];
