<?php

/* ------------------- call the function ------------------- */
function wscompletioncall($token, $functionname, $params) {
    require_once('./curl.php');
    $serverurl = "http://cliniciansacademy.avide.server.dev/webservice/rest/server.php?wstoken=$token&wsfunction=$functionname&moodlewsrestformat=json";
    $resp = (new curl)->post($serverurl, $params);
    return $resp;
}

header('Content-Type: text/plain');

$data = wscompletioncall('96d7dacb5eca0056b558ab55c2f06b78', 'local_wsca_helloworld', array("welcomemessage" => "Hi there, "));
echo "Shows the name of the token user: " . print_r($data, true) . "\n\n\n";

$data = wscompletioncall('25ba47e25081f7a73846ed7102d527ae', 'local_wsca_userscompletions', array("userids" => "3,5,6,7,9"));
echo "User completions: " . print_r($data, true) . "\n\n\n";

$data = wscompletioncall('aac336f31d6cba40b0d34037763db8e1', 'local_wsca_coursescompletions', array("courseids" => "2,9,12,13"));
echo "Course completions: " . print_r($data, true) . "\n\n\n";
