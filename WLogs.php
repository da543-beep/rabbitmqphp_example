<?php
require_once __DIR__ .'/SdLogs.php';
$paths = explode (':', getenv('W_Logs') ?: '');
$files = [];
foreach ($paths as $path) {
	if ($path === ''){
		continue;
	}
	if (!is_readable($path)){
		error_log("Cannot read log: $path");
		continue;
	}
	$files[$path] = filesize($path);
}

if (!$files) {
	die("No files reable\n");
}
echo "Monitoring" . count ($files) . "log files\n";
while (true) {
	foreach ($files as $path => $offset) {
		clearstatcache(true, $path);
	}
	$size = filesize($path);
	if ($size < $offset) {
		$offset = 0;
	}
	if ($size <= $offset){
		continue;
	}
	$handle = fopen($path, 'r');
	if (!$handle) {
		continue;
	}
	fseek($handle, $offset);
	while(($line = fgets($handle)) !== false) {
		$newOffset = ftell($handle);

		if (!str_ends_with($line, "\n")){
			break;
		}
		$line = trim($line);
		if ($line != ''){
			$level = preg_match( '/error|failed|fatal|exception/', $line) ? 'ERROR' : 'INFO';
			if (!SdLog($line, basename($path), $level)){
				break;
			}
		}
		$offset = $newOffset;
	}
	fclose($handle);
	$files[$path] = $offset;
}

 
?>
