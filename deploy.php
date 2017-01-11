<?php
	// initial script based on https://gist.github.com/1809044 by oodavid
	// prepare to run, verify params/request body
	$CONFIG_FILE = 'config.ini';
	$HEADERS = apache_request_headers();

	// don't attempt checks if server doesn't have configs set up
	if (file_exists($CONFIG_FILE)) {
		$config = parse_ini_file($CONFIG_FILE);
		$request_body = file_get_contents('php://input');
		if ($request_body !== '') {
			$secure_hash = 'sha1=' . hash_hmac('sha1', $request_body, $config['REALMEYE-API_SECRET']);
			if (!isset($HEADERS['X-Hub-Signature']) || !hash_equals($secure_hash, $HEADERS['X-Hub-Signature'])) {
				exit("Non-GitHub content, no pull initiated.");
			}
			$request = json_decode($request_body, true);
			if ($request['ref'] !== 'refs/heads/master') {
				exit("Push event came from non-master branch, no pull initiated.");
			}
		} else {
			if (!in_array($_SERVER['REMOTE_ADDR'], $config['allowed_manual_deploy_ips'])) {
				exit("Your IP does not have permission to trigger manual deployments.");
			}
		}
	} else {
		error_log('WARNING: Cannot read config.ini, file may be read-protected or missing; skipping deploy checks.');
	}

	// The commands
	$commands = array(
		'echo $PWD',
		'git pull',
		'git status',
		'git log -1 --oneline',
		'git checkout HEAD -- "$(git rev-parse --show-toplevel)"',
		'grep \'Id\' index.php',
	);

	// Run the commands for output
	$output = '';
	foreach($commands AS $command){
		// Run it
		$tmp = shell_exec($command);
		// Output
		$output .= "<span style=\"color: #6be234;\">\$</span> <span style=\"color: #729fcf;\">{$command}\n</span>";
		$output .= htmlentities(trim($tmp)) . "\n";
	}
	// Make it pretty for manual user access
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta charset="utf-8">
	<title>GitHub Deployment Script</title>
</head>
<body style="background-color: #000; color: #fff; font-weight: bold; padding: 0 10px;">
<pre>
 .  ____  .
 |/      \|
[| <span style="color: #f00;">&hearts;    &hearts;</span> |]
 |___==___|


<?php echo $output; ?>
</pre>
</body>
</html>
