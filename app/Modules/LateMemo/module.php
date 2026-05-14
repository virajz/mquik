<?php

return [
    'label' => 'Late Memo',
    'description' => 'Memos issued to employees for late arrival.',
    'group' => 'HR',
    'icon' => 'exclamation-triangle',
    'permissions' => [
        'late_memo.view',
        'late_memo.create',
        'late_memo.update',
        'late_memo.delete',
    ],
];
