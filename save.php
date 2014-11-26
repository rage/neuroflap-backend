<?php

require 'cors.php';
require 'config.php';

function is_number($num) {
    return is_int($num) || is_float($num);
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['entries']) || empty($data['studentNumber']) || !isset($data['actualSession']) || !is_number($data['flying']) || !is_number($data['reactions'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('hv');
}

try {
    $conn = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('Database connection failed!');
}

$conn->beginTransaction();

// insert session

$stmt = $conn->prepare('INSERT INTO session (ipAddress, studentNumber, flyingScore, reactionScore, actualSession) VALUES (:ipAddress, :studentNumber, :flyingScore, :reactionScore, :actualSession) RETURNING id');
$stmt->execute(array(':ipAddress' => $_SERVER['REMOTE_ADDR'], ':studentNumber' => $data['studentNumber'], ':flyingScore' => $data['flying'], ':reactionScore' => $data['reactions'], ':actualSession' => $data['actualSession'] ? '1' : '0'));
$session_id = $stmt->fetchColumn();

// insert log entries to session

$stmt = $conn->prepare('INSERT INTO entry (sessionId, time, content) VALUES (:sessionId, to_timestamp(:time), :content)');

foreach ($data['entries'] as $entry) {
    if (empty($entry['time']) || empty($entry['event'])) {
        $conn->rollback();
        header('HTTP/1.1 400 Bad Request');
        exit('hv');
    }

    $stmt->execute(array(':sessionId' => $session_id, ':time' => $entry['time'], ':content' => $entry['event']));
}

$conn->commit();