<?php

/**
 * A short server-sent event stream for the shared connection test. Each
 * request sends two events, numbered on from `Last-Event-ID` or the
 * `lastEventId` parameter, and ends; the browser reconnects after `retry`.
 * The built-in server serves one request at a time, so a stream may not stay open.
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
$last = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? $_GET['lastEventId'] ?? 0);
echo "retry: 300\n\n";
for ($i = 1; $i <= 2; $i++) {
	$id = $last + $i;
	echo "id: $id\ndata: {\"n\":$id}\n\n";
	flush();
	usleep(50000);
}
