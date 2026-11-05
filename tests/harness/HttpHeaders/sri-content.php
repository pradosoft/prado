<?php
/**
 * Shared body of the SRI test asset.
 *
 * Both the asset endpoint (sri-asset.php) and the page that pins its integrity
 * (SriPage) include this file, so the hash the page computes always matches the
 * exact bytes the asset serves.
 *
 * The script records its own execution on the document element; the functional
 * test asserts the marker is present when the integrity matches and absent when
 * it is tampered (the browser blocks execution).
 */

return "document.documentElement.dataset.sriRan = '1';\n";
