<?php

require 'cors.php';
require 'config.php';

try {
    $conn = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('Database connection failed!');
}

function query($sql, $params) {
    global $conn;
    $smnt = $conn->prepare($sql);
    $smnt->execute($params);
    return $smnt;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!empty($data['entries'], $data['studentNumber'])) {
    exit('hv');
}

$conn->beginTransaction();

// insert session

$ip = $_SERVER['REMOTE_ADDR'];
$student_number = $data['studentNumber'];

$res = query('INSERT INTO session (ipAddress, studentNumber) VALUES (:ipAddress, :studentNumber) RETURNING id',
    array(':ipAddress' => $_SERVER['REMOTE_ADDR'], ':studentNumber' => $student_number));

$session_id = $res->fetchColumn();

// insert log entries to session

foreach ($data['entries'] as $entry) {
    if (!empty($entry['timestamp'], $entry['content'])) {
        $conn->rollback();
        exit('hv');
    }

    query('INSERT INTO entry (sessionId, time, content) VALUES (:sessionId, to_timestamp(:time), :content)',
        array(':sessionId' => $session_id, ':time' => $entry['timestamp'], ':content' => $entry['content']));
}

$conn->commit();