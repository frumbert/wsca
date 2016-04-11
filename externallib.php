<?php

require_once($CFG->libdir . "/externallib.php");

class local_wsca_external extends external_api {


    /* ------------------------ hello world --------------------------*/


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function helloworld_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
   public static function helloworld($welcomemessage = 'Hello world, ') { // $welcomemessage = 'Hello world, ') {
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::helloworld_parameters(), array('welcomemessage' => $welcomemessage));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        return json_encode(array("result" => $params['welcomemessage'] . $USER->firstname));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function helloworld_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }

   /* ------------------------ force completions to run --------------------------*/

    public static function completioncron_parameters() {
        return new external_function_parameters(array("none"  => new external_value(PARAM_TEXT, "Does not have any parameters")));
    }
   public static function completioncron() {
        global $USER, $CFG;
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);
        require_once($CFG->dirroot.'/completion/cron.php');
        completion_cron_completions();
        return json_encode(array("result" => 1));
    }
    public static function completioncron_returns() {
        return new external_value(PARAM_TEXT, 'JSON {"result": 1}');
    }


    /* ------------------------ completions for user(s) --------------------------*/
    public static function userscompletions_parameters() {
        return new external_function_parameters(
            array("userids" => new external_value(PARAM_SEQUENCE, "One or more comma-seperated user ids to include results for, e.g. 1,26,3,464,55435"))
        );
    }
    public static function userscompletions_returns() {
        return new external_multiple_structure( // rows
            new external_single_structure( // cols
                array( // fields
                    'id' => new external_value(PARAM_INT, 'record id'),
                    'userid' => new external_value(PARAM_INT, 'user row id'),
                    'courseid' => new external_value(PARAM_INT, 'course row id'),
                    'timeenrolled' => new external_value(PARAM_INT, 'timestamp when user was enrolled in this course'),
                    'timestarted' => new external_value(PARAM_INT, 'timestamp when user first started this course, or zero if the user hasn\'t started'),
                    'timecompleted' => new external_value(PARAM_INT, 'timestamp when user was marked complete by the cron, or zero if the user hasn\'t completed'),
                    'reaggregate' => new external_value(PARAM_INT, 'timestamp if the record is currently being calculated, otherwize zero')
                )
            )
        );
    }

    public static function userscompletions($args) {
        global $DB, $USER;
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);
        $user_ids = explode(",", $args);
        $union = "(select " . implode(" union select ", $user_ids) . ")";
        $rows = $DB->get_records_select('course_completions', "userid IN $union", null, $DB->sql_order_by_text('userid'));
        $ret = array();
        foreach ($rows as $row) {
            $ret[] = array(
                    'id' => intval($row->id),
                    'userid' => intval($row->userid),
                    'courseid' => intval($row->course),
                    'timeenrolled' => intval($row->timeenrolled),
                    'timestarted' => intval($row->timestarted),
                    'timecompleted' => intval($row->timecompleted),
                    'reaggregate' => intval($row->reaggregate)
            );
        }
        return $ret;
    }

    /* ------------------------ completions for course(s) --------------------------*/
    public static function coursescompletions_parameters() {
        return new external_function_parameters(
            array("courseids" => new external_value(PARAM_SEQUENCE, "One or more comma-seperated course ids to include results for, e.g. 1,26,3,464,55435"))
        );
    }
    public static function coursescompletions_returns() {
        return new external_multiple_structure( // rows
            new external_single_structure( // cols
                array( // fields
                    'id' => new external_value(PARAM_INT, 'record id'),
                    'userid' => new external_value(PARAM_INT, 'user row id'),
                    'courseid' => new external_value(PARAM_INT, 'course row id'),
                    'timeenrolled' => new external_value(PARAM_INT, 'timestamp when user was enrolled in this course'),
                    'timestarted' => new external_value(PARAM_INT, 'timestamp when user first started this course, or zero if the user hasn\'t started'),
                    'timecompleted' => new external_value(PARAM_INT, 'timestamp when user was marked complete by the cron, or zero if the user hasn\'t completed'),
                    'reaggregate' => new external_value(PARAM_INT, 'timestamp if the record is currently being calculated, otherwize zero')
                )
            )
        );
    }

    public static function coursescompletions($args) {
        global $DB, $USER;
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);
        $course_ids = explode(",", $args); // 5,7,9
        $union = "(select " . implode(" union select ", $course_ids) . ")"; // (select 5 union select 7 union select 9)
        $rows = $DB->get_records_select('course_completions', "course IN $union", null, $DB->sql_order_by_text('course'));
        $ret = array();
        foreach ($rows as $row) {
            $ret[] = array(
                    'id' => intval($row->id),
                    'userid' => intval($row->userid),
                    'courseid' => intval($row->course),
                    'timeenrolled' => intval($row->timeenrolled),
                    'timestarted' => intval($row->timestarted),
                    'timecompleted' => intval($row->timecompleted),
                    'reaggregate' => intval($row->reaggregate)
            );
        }
        return $ret;
    }

    /* ------------------------ manual enrolment -------------------------- */
    public static function manualenrolment_parameters() {
        return new external_function_parameters(
            array(
                "courseid" => new external_value(PARAM_INT, "The row id of a course"),
                "userid" => new external_value(PARAM_INT, "The row id of a user")
            )
        );
    }
    public static function manualenrolment_returns() {
        new external_single_structure( // cols
            array( // fields
                'success' => new external_value(PARAM_BOOL, 'true if record is created')
            )
        );
    }

    public static function manualenrolment($courseid, $userid) {
        global $DB, $USER;
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);


        if (!$DB->record_exists('user', array('id'=>$userid))) {
             throw new Exception('User id was not found in database (' . $userid . ')');
        }

        if (!$DB->record_exists('course', array('id'=>$courseid))) {
             throw new Exception('Course id was not found in database (' . $courseid . ')');
        }

        $manualenrol = enrol_get_plugin('manual');
        $enrolinstance = $DB->get_record('enrol',
            array('courseid'=>$courseid,
                'status'=>ENROL_INSTANCE_ENABLED,
                'enrol'=>'manual'
            ),
            '*',
            MUST_EXIST
        );
        $manualenrol->enrol_user($enrolinstance, $userid, 5); // enrol the user

        // run the cron service for completions, should then create the completions row for this user/course
        //require_once($CFG->dirroot.'/completion/cron.php');
        //completion_cron_completions();
        // $DB->execute('UPDATE {course_completions} SET timeenrolled = ? WHERE userid = ? AND course = ? AND (timeenrolled IS NULL OR timeenrolled = 0)', array(time(), $userid, $courseid));

       // ensure the timeenrolled is recorded on the completions table
        $stuff = new stdClass();
        $stuff->timeenrolled = time();
        $stuff->userid = $userid;
        $stuff->course = $courseid;
        $result = $DB->insert_record("course_completions", $stuff);

        return json_encode('{"success":' .$result. '}');

    }
}
