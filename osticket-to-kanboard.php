<?php

$debug = true;

$config_ot = array(
    'url' => 'https://osticket.mydomain.com/api/',
    'tasks' => 'tasks.json?flags=1&dept_id=4',
    'task' => 'task.json',
    'threads' => 'threads.json?object_id=',
    'thread' => 'thread.json',
    'key'=>'osTicket API key' //osTicket API key
);
$config_kb = array(
    'url' => 'https://kanboard.mydomain.com/jsonrpc.php',
    'user' => 'jsonrpc',
    'key' => 'Kanboard API key', //Kanboard API key
    'project_id' => 3,
    'ticket_url' => 'https://osticket.mydomain.com/scp/tickets.php?id='
);

if ($debug) {
    print_r($data);
}

#pre-checks
function_exists('curl_version') or die('CURL support required');
function_exists('json_encode') or die('JSON support required');
function_exists('json_decode') or die('JSON support required');

#set timeout
set_time_limit(30);

$tasks = call_ot($config_ot['tasks']);

foreach ($tasks as $task) {

    //Not process task without ticket
    if ($task->object_id == 0)
        continue;

    $reference = 'destek-' . $task->id;
    $kbtask = getTaskByReferenceFromKB($reference);

    if ($kbtask->result == null) {
        $title = getTaskTitleFromOT($task->id);
        if ($debug)
            echo $title . PHP_EOL;

        $body = '';

        $threads = call_ot($config_ot['threads'] . $task->id);

        foreach ($threads as $thread) {
            $entries = call_ot($config_ot['thread'] . '/' . $thread->id . '/entries');

            foreach ($entries as $entry) {
                $body .= '```' . PHP_EOL . strip_tags($entry->body) . PHP_EOL . '```' . PHP_EOL;
            }
        }

        $id = createTaskToKB($title, $body, $reference);
        createTaskLinkToKB($id, $task->object_id);
        exit;
    }


}

function getTaskTitleFromOT($id)
{
    global $config_ot;

    $title = call_ot($config_ot['task'] . '/' . $id . '/title');
    return $title->title;
}

function call_ot($path)
{
    global $debug;
    global $config_ot;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config_ot['url'] . $path);
    curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.10.x');
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'X-API-Key: ' . $config_ot['key']));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code != 200)
        die('Unable to request ' . $config_ot['url'] . $path . ' url: ' . $result);

    if ($debug)
        echo $result . PHP_EOL;

    $jsonobj = json_decode($result);

    if ($debug) {
        echo 'ot ' . $path . PHP_EOL . '----------------------' . PHP_EOL;
        print_r($jsonobj);
    }

    return $jsonobj;
}

function getTaskByReferenceFromKB($reference)
{
    global $config_kb;

    $data = array(
        'jsonrpc' => '2.0',
        'id' => 'null',
        'method' => 'getTaskByReference',
        'params' => array(
            'project_id' => $config_kb['project_id'],
            'reference' => $reference
        )
    );

    return call_kb($data);

}

function createTaskToKB($title, $body, $reference)
{
    global $config_kb;

    $data = array(
        'jsonrpc' => '2.0',
        'id' => 'null',
        'method' => 'createTask',
        'params' => array(
            'title' => $title,
            'description' => $body,
            'project_id' => $config_kb['project_id'],
            'reference' => $reference
        )
    );

    $result = call_kb($data);

    if (!property_exists($result, 'result'))
        die('Unable to create createTask on KB!');

    return $result->result;
}

function createTaskLinkToKB($task_id, $ticket_id)
{
    global $config_kb;

    $data = array(
        'jsonrpc' => '2.0',
        'id' => 'null',
        'method' => 'createExternalTaskLink',
        'params' => array(
            'task_id' => $task_id,
            'url' => $config_kb['ticket_url'] . $ticket_id,
            'dependency' => 'related',
            'type' => 'weblink',
            'title' => 'My osTicket System - Ticket #' . $ticket_id
        )
    );

    $result = call_kb($data);

    if ($result->result == false)
        die('Unable to create createExternalTaskLink on KB!');

    return $result->result;
}

function call_kb($data)
{
    global $debug;
    global $config_kb;

    if ($debug) {
        echo 'kb data' . PHP_EOL . '--------' . PHP_EOL;
        print_r($data);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config_kb['url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Kanboard API Client v1.2.3');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/xml'));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_USERPWD, $config_kb['user'] . ':' . $config_kb['key']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code != 200)
        die('Unable to request ' . $config_kb['url'] . ' url: ' . $result);

    if ($debug)
        echo $result . PHP_EOL;

    $jsonobj = json_decode($result);

    if ($debug) {
        echo 'kb ' . $data['method'] . PHP_EOL . '--------' . PHP_EOL;
        print_r($jsonobj);
    }

    return $jsonobj;
}

?>