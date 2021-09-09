# WSCA

Moodle 3 web services for looking at course completions

Services:
local_wsca_helloworld - a helloworld for testing
local_wsca_completioncron - RUNS the completions cron task (for all users/courses)
local_wsca_coursescompletions - Return completion records for one or more courses
local_wsca_userscompletions - Return completion records for one or more users
local_wsca_manualenrolment - Enrol a user in a course using the manual enrolment method, create a course completion record

## special purpose service

local_wsca_enroller - create a user per course - a one-to-one enrolment

Input data:

    {"enrolments":{"enrol":[{"id":59001,"firstname":"gavin","lastname":"element","email":"gavin.element@spoopy.co","course":[{"id":12},{"id":13},{"id":9},{"id":6},{"id":38}]}],"unenrol":[{"id":59001,"course":[{"id":39}]}]}}

Result:

    {"enrolments":{"enrol":[{"id":59001,"firstname":"gavin","lastname":"element","email":"gavin.element@spoopy.co","course":[{"id":12,"status":201,"lmsid":"214"},{"id":13,"status":201,"lmsid":"215"},{"id":9,"status":201,"lmsid":"216"},{"id":6,"status":201,"lmsid":"217"},{"id":38,"status":201,"lmsid":"218"}]}],"unenrol":[{"id":59001,"course":[{"id":39,"status":404}]}]}}


## License
GPL2 - same as moodle
