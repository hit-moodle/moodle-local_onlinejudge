<?php

$handlers = array (

/*
 * Event Handlers
 */
    'local_onlinejudge2_judge_begin' => array (
        'handlerfile'      => '/local/onlinejudge2/judgelib.php',
        'handlerfunction'  => 'event_judge_begin',
        'schedule'         => 'cron'
    ),
    'local_onlinejudge2_judge_over' => array (
        'handlerfile'      => '/local/onlinejudge2/judgelib.php',
        'handlerfunction'  => 'event_judge_over',
        'schedule'         => 'instant'
    ),
    'local_onlinejudge2_judge_error' => array (
        'handlerfile'      => '/local/onlinejudge2/judgelib.php',
        'handlerfunction'  => 'event_judge_error',
        'schedule'         => 'instant'
    ),


);

?>