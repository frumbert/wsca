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


    /* ------------------------ create users and link them to multiple enrolments, or remove multiple user enrolments --------------------------

REQUEST:
enrolments: {
    “enrol”: [
        {“id”: 123,
                  "firstname":"test",
                  "lastname":"test1",
                  "emailid":"236_test@test.com"
                  “course”: [
            {“id”:456},
            {“id”:789}
        ]},
    ],
    “unenrol”: [
        {“id”: 234, “course”: [{
            {“id”: 654},
            {“id”, 987}
        ]},
    ]
}


RESPONSE:

enrolments: {
    “enrol”: [
        {“id”: 123,
            "firstname":"test",
            "lastname":"test1",
            "emailid":"236_test@test.com"
            “course”: [
            {“id”: 456, “status”: 201, "lmsid": 6437}, // created, new userid = 6437
            {“id”: 789, “status”: 302, "lmsid": 4567} // found existing
        ]},
    ],
    “unenrol”: [
        {“id”: 234, “course”: [
            {“id”: 654, “status”: 200, "lmsid": 423}, // ok
            {“id”: 987, “status”: 404, "lmsid": 0} // not found
        ]},
    ]
}
    status codes:

         200=ok
         201=created
         302=found
         404=not found
         409=conflict
         500=error

    */

    public static function enroller_parameters() {
        return new external_function_parameters(
                array("json" => new external_value(PARAM_TEXT, "The JSON for the enrolments - {enrolment:{enrol:[{id,firstname,lastname,course:[id]}],unenrol:[{id,course:[id,purge]}]}"))
        );
    }
    public static function enroller_returns() {
        return new external_value(PARAM_TEXT, 'The updated JSON for the enrolments - enrolment:{enrol:[{id,firstname,lastname,course:[id,status,lmsid]}],unenrol:[{id,course:[id,purge,status,lmsid]}]}');
    }

    public static function enroller($json) {
        global $DB, $USER;

        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        $enrollment_plugin = enrol_get_plugin('manual');
        $obj = json_decode($json, JSON_NUMERIC_CHECK);
        $root = &$obj["enrolments"]; // byref

        foreach ($root["enrol"] as &$entry) { // byref
            $id = $entry["id"];
            $firstname = $entry["firstname"];
            $lastname = $entry["lastname"];
            $email = $entry["email"];
            $find_existing = (isset($entry["existing"]) && (bool)   $entry["existing"] == "true"); // if true find and return existing user ids, otherwise always create new users
            $username = $firstname[0] . $lastname[0] . hash("crc32b", time()); // username is not used
            while ($DB->record_exists('user', array('username'=>$username))) { // but must be unique
                $username = $firstname[0] . $lastname[0] . hash("crc32b", time() + rand()); // add a bit of randomness
            }
            foreach ($entry["course"] as &$course) { // byref
                if (!$DB->record_exists('course', array('id'=>$course["id"]))) {
                    $course["status"] = 404; // not found
                } else {
                    // manual enrolment instance for this course
                    $enrolinstance = $DB->get_record('enrol',
                        array('courseid'=>$course["id"],
                            'status'=>ENROL_INSTANCE_ENABLED,
                            'enrol'=>'manual'
                        ),
                        '*',
                        MUST_EXIST
                    );
                    $lmsid = 0;
                    if ($find_existing) { // does a user belong to an existing enrolment instance?
                        $sql = "SELECT userid FROM {user_enrolments} WHERE enrolid=:enrolid AND userid IN (SELECT id FROM {user} WHERE idnumber = :id)";
                        $params = array(
                            "enrolid" => $enrolinstance->id,
                            "id" => $id
                        );
                        $lmsid = $DB->get_field_sql($sql, $params); // ,IGNORE_MULTIPLE
                    }
                    // $course["query"] = str_replace(":enrolid", $enrolinstance->id, str_replace(":id", $id, $sql));
                    if ($lmsid > 0) {
                        $course["status"] = 302; // found
                        $course["lmsid"] = intval($lmsid);
                    } else {
                            $lmsid = self::enroller_create_user($username, $firstname, $lastname, $email, $id, $course["id"]); // create a new user instance
                            $enrollment_plugin->enrol_user($enrolinstance, $lmsid, 5); // enrol the user as a student
                            $stuff = new stdClass();
                            $stuff->timeenrolled = time(); // record the time this new user instance was enrolled
                            $stuff->userid = $lmsid;
                            $stuff->course = $course["id"];
                            $DB->insert_record("course_completions", $stuff);
                            $course["status"] = 201; // created
                            $course["lmsid"] = intval($lmsid);
                    } // lmsid > 0
                } // course exists
            } // foreach course
            // unset($course); // dereference byref $course instance
        } // foreach enrol
        // unset($entry); // dereference byref $entry instance

        foreach ($root["unenrol"] as &$entry) { // byref
            $id = $entry["id"];
            foreach ($entry["course"] as &$course) { // byref
                if (!$DB->record_exists('course', array('id'=>$course["id"]))) {
                    $course["status"] = 404; // not found
                } else {
                    // manual enrolment instance for this course
                    $enrolinstance = $DB->get_record('enrol',
                        array('courseid'=>$course["id"],
                            'status'=>ENROL_INSTANCE_ENABLED,
                            'enrol'=>'manual'
                        ),
                        '*',
                        MUST_EXIST
                    );
                    $sql = "SELECT userid FROM {user_enrolments} WHERE enrolid=:enrolid AND userid IN (SELECT id FROM {user} WHERE idnumber = :id)";
                    $params = array(
                        "enrolid" => $enrolinstance->id,
                        "id" => $id
                    );
                    $lmsid = $DB->get_field_sql($sql, $params); // ,IGNORE_MULTIPLE
                    if ($lmsid > 0) {
                        if (isset($course["purge"]) && (bool)$course["purge"] == true) {
                            $lmsuser =  get_complete_user_data('id', $lmsid);
                            delete_user($lmsuser); // cleans out everything for the user, inclduing unenrolling and removing completion records
                            // $DB->delete_records("course_completions", array("userid" => $lmsid, "course" => $course["id"])); // remove the completion table record
                        } else {
                            $enrollment_plugin->unenrol_user($enrolinstance->id, $lmsid); // perform unenrolment
                        }
                        $course["lmsid"] = intval($lmsid);
                        $course["status"] =  200;
                    } else {
                        $course["status"] =  404;
                    } // $lmsid > 0
                } // course exists
            } // foreach course
            // unset($course); // dereference byref $course instance
        } // foreach unenrol
        // unset($entry); // dereference byref $entry instance

        // the return value is the same as the ingres, but it is modified along the way (object passed byref within loops)
        return json_encode($obj);

    }

    /* private function truncate_newuser($userobj) {
        $user_array = truncate_userinfo((array) $userobj);
        $obj = new stdClass();
        foreach($user_array as $key=>$value) {
            $obj->{$key} = $value;
        }
        return $obj;
    } */

    private function enroller_create_user($username, $firstname, $lastname, $email, $idnumber, $courseid) {

            // /lib/moodlelib.php
            $password = hash("md5", time()); // password is an unknown for optimal security
            $newuser = create_user_record($username . "_$courseid", $password); // username, password, "manual"

            // add our known fields
            $newuser->email = $email;
            $newuser->firstname = $firstname;
            $newuser->lastname = $lastname;
            $newuser->idnumber = $idnumber;

            // /user/lib.php
            user_update_user($newuser, false, false);

            // and that's all
            return $newuser->id;
    }

            /*
            $newuser = new stdClass();
            $newuser->email = $email;
            $newuser->firstname = $firstname;
            $newuser->lastname = $lastname;
            $newuser->idnumber = $idnumber;
            $newuser->city = '';
            $newuser->confirmed = 1;
            $newuser->auth = 'manual'; // or is it opensesame ?
            $newuser->policyagreed = 1;
            $newuser->username = $email;
            $newuser->password = hash_internal_user_password(md5(time())); // don't care
            if (empty($newuser->lang) || !get_string_manager()->translation_exists($newuser->lang)) {
                $newuser->lang = $CFG->lang;
            }
            $newuser->lastip = getremoteaddr();
            $newuser->timecreated = time();
            $newuser->timemodified = $newuser->timecreated;
            $newuser->mnethostid = $CFG->mnet_localhost_id;
            $newuser = self::truncate_newuser($newuser);
            $id = $DB->insert_record('user', $newuser);
            return $id;
            // $user = get_complete_user_data('id', $newuser->id);
            \core\event\user_created::create_from_userid($id)->trigger();
            return $id;
            */
//    }

   /* private function enroller_perform_enrolment($courseid, $userid) {
        global $DB;

        if (!$DB->record_exists('course', array('id'=>$courseid))) {
             throw new Exception('Course id was not found in database (' . $courseid . ')');
        }

        // if this happens we are up the creek, since we should have just created the user a momemt ago
        if (!$DB->record_exists('user', array('id'=>$userid))) {
             throw new Exception('User id was not found in database (' . $userid . ')');
        }

        $enrolinstance = $DB->get_record('enrol',
            array('courseid'=>$courseid,
                'status'=>ENROL_INSTANCE_ENABLED,
                'enrol'=>'manual'
            ),
            '*',
            MUST_EXIST
        );

        self::MANUAL_ENROLMENT_PLUGIN->enrol_user($enrolinstance, $userid, 5); // enrol the user as a student

        $stuff = new stdClass();
        $stuff->timeenrolled = time();
        $stuff->userid = $userid;
        $stuff->course = $courseid;
        $result = $DB->insert_record("course_completions", $stuff);

    } */


}
