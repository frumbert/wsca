<?php

$functions = array(
        'local_wsca_helloworld' => array(
                'classname'   => 'local_wsca_external',
                'methodname'  => 'helloworld',
                'classpath'   => 'local/wsca/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read',
        ),
        'local_wsca_completioncron' => array(
                'classname'   => 'local_wsca_external',
                'methodname'  => 'completioncron',
                'classpath'   => 'local/wsca/externallib.php',
                'description' => 'RUNS the completions cron task (for all users/courses)',
                'type'        => 'read',
        ),
        'local_wsca_coursescompletions' => array(
                'classname'   => 'local_wsca_external',
                'methodname'  => 'coursescompletions',
                'classpath'   => 'local/wsca/externallib.php',
                'description' => 'Return completion records for one or more courses',
                'type'        => 'read',
        ),
        'local_wsca_userscompletions' => array(
                'classname'   => 'local_wsca_external',
                'methodname'  => 'userscompletions',
                'classpath'   => 'local/wsca/externallib.php',
                'description' => 'Return completion records for one or more users',
                'type'        => 'read',
        ),
        'local_wsca_manualenrolment' => array (
                'classname' => 'local_wsca_external',
                'methodname' => 'manualenrolment',
                'classpath' => 'local/wsca/externallib.php',
                'description' => 'Enrol a user in a course using the manual enrolment method',
                'type' => 'write',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Hello World service' => array(
                'functions' => array ('local_wsca_helloworld'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'Completions Cron' => array(
                'functions' => array ('local_wsca_completioncron'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'Completion records for multiple courses service' => array(
                'functions' => array ('local_wsca_coursescompletions'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'Completion records for multiple users service' => array(
                'functions' => array ('local_wsca_userscompletions'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'Enrol a user in a course using manual enrolment' => array(
                'functions' => array ('local_wsca_manualenrolment'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
