<?php

require 'cors.php';
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['entries']) || empty($data['studentNumber'])) {
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

$stmt = $conn->prepare('INSERT INTO session (ipAddress, studentNumber) VALUES (:ipAddress, :studentNumber) RETURNING id');
$stmt->execute(array(':ipAddress' => $_SERVER['REMOTE_ADDR'], ':studentNumber' => $data['studentNumber']));
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