<?php
	// based on oodavid's initial script: https://gist.github.com/1809044

	// Executes the passed (shell) command
	// Returns the trimmed string output
	function proc_exec($command) {
		$handle = popen($command, 'r');
		$handle_response = '';
		while (!feof($handle)) {
			$handle_response .= fread($handle, 8192);
		}
		pclose($handle);
		return trim($handle_response);
	}

	// set up params verifying request body and configs
	$CONFIG_FILE = 'config.ini';
	$HEADERS = apache_request_headers();
	$REQUEST_BODY = file_get_contents('php://input');
	$REQUEST_JSON = json_decode($REQUEST_BODY, true);
	$local_branch_script = 'localbranch.sh';
	$checkout_script = 'checkout.sh';
	$LOCAL_BRANCH = proc_exec('./' . $local_branch_script);

	// don't attempt access checks if server doesn't have configs set up
	if (file_exists($CONFIG_FILE) && is_readable($CONFIG_FILE)) {
		$config = parse_ini_file($CONFIG_FILE);
		// confirm the request came from GitHub before processing request body
		if ($REQUEST_BODY !== '') {
			$secure_hash = 'sha1=' . hash_hmac('sha1', $REQUEST_BODY, $config['REALMEYE-API_SECRET']);
			if (!isset($HEADERS['X-Hub-Signature']) || !hash_equals($secure_hash, $HEADERS['X-Hub-Signature'])) {
				exit('Non-GitHub content, no pull initiated.');
			// don't pull if the delivered branch doesn't match the local repo's branch
			} else if ($REQUEST_JSON['ref'] !== ('refs/heads/' . $LOCAL_BRANCH)) {
				exit('Push event came from branch that did not match local branch: ' . $LOCAL_BRANCH . '; no pull initiated.');
			}
			$pull_branch = str_replace('refs/heads/', '', $REQUEST_JSON['ref']);
		// only allow empty-body requests (page hits) from whitelisted IPs
		} else if (!in_array($_SERVER['REMOTE_ADDR'], $config['allowed_manual_deploy_ips'])) {
			exit('Your IP does not have permission to trigger manual deployments.');
		}
	} else {
		error_log('WARNING: Cannot read ' . $CONFIG_FILE . ', file may be read-protected or missing; skipping access checks.');
	}

	if (empty($pull_branch)) {
		$pull_branch = $LOCAL_BRANCH;
	}
	// execute git clean / checkout
	$shell_script_call = './' . $checkout_script . ' ' . $pull_branch;
	$checkout_response = proc_exec($shell_script_call);

	// construct output string
	$output = file_get_contents($checkout_script);
	$output = preg_replace('/^(.+)$/m', '<span class="cli-anchor">$</span> <span class="cli-output">$1</span>', $output);
	$output .= "\n" . $checkout_response;
// make it pretty for manual user access
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<link rel="stylesheet" href="deploy.css">
	<meta charset="utf-8">
	<title>GitHub Deployment Script</title>
</head>
<body>
<pre>
 .  ____  .
 |/      \|
[| <span class="eyes">&hearts;    &hearts;</span> |]
 |___==___|


<?php echo $output; ?>
</pre>
</body>
</html>
