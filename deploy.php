<?php
/*
Deploy script/endpoint for GitHub deployment hooks
Based on oodavid's initial script: https://gist.github.com/1809044
*/
declare(strict_types=1);

require_once('classes/RealmEyeAPIUtils.php');
new RealmEyeAPIUtils();
$config = RealmEyeAPIUtils::$config;
$logger = RealmEyeAPIUtils::$logger;

// Executes the passed (shell) command
// Returns the trimmed string output
function proc_exec(string $command): string {
	$handle = popen($command, 'r');
	$handle_response = '';
	while (!feof($handle)) {
		$handle_response .= fread($handle, 8192);
	}
	pclose($handle);
	return trim($handle_response);
}

// Accepts an IP address string and an array of IP strings with asterisks
// permitted as wildcards
// Returns a boolean representing whether the given IP address is present
// in the passed allowed IPs array. It will return true if it is present, or
// if it matches an IP including a wildcard string.
// (eg. '127.*' will match '127.0.0.1')
function ip_allowed_to_deploy(string $ip, array $allowed_ips): bool {
	foreach ($allowed_ips as $allowed_ip_string) {
		if (strpos($allowed_ip_string, '*') === false) {
			$GLOBALS['logger']->debug(
				'Checking IP string equivalence for $allowed_ip entry ' .
				$allowed_ip_string
			);
			if ($ip === $allowed_ip_string) {
				return true;
			}
		} else {
			$GLOBALS['logger']->debug(
				'Checking IP string wildcard filter for $allowed_ip entry ' .
				$allowed_ip_string
			);
			// Replace '.' with '\.' and '*' with '.*'
			// Also surround newly-formed regex string with /^ ... $/
			$allowed_ip_regex = str_replace('.', '\.', $allowed_ip_string);
			$allowed_ip_regex = str_replace('*', '.*', $allowed_ip_regex);
			$allowed_ip_regex = '/^' . $allowed_ip_regex . '$/';
			$GLOBALS['logger']->trace('allowed_ip_regex: ' . $allowed_ip_regex);
			if (preg_match($allowed_ip_regex, $ip)) {
				return true;
			}
		}
	}
	return false;
}

// set up params verifying request body and configs
$REQUEST_HEADERS = apache_request_headers();
$REQUEST_BODY = file_get_contents('php://input');
$REQUEST_JSON = json_decode($REQUEST_BODY, true);
$local_branch_script = 'localbranch.sh';
$checkout_script = 'checkout.sh';
$LOCAL_BRANCH = proc_exec('./' . $local_branch_script);

// don't attempt access checks if server doesn't have configs set up
if ($config) {
	// confirm the request came from GitHub before processing request body
	if ($REQUEST_BODY !== '') {
		$secure_hash = 'sha1=' .
		               hash_hmac(
		                   'sha1',
		                   $REQUEST_BODY, $config['REALMEYE-API_SECRET']
		               );
		if (
			!(
				isset($REQUEST_HEADERS['X-Hub-Signature']) &&
				hash_equals($secure_hash, $REQUEST_HEADERS['X-Hub-Signature'])
			)
		) {
			$logger->warn(
				'GitHub-forged push request attempted from ' .
				$_SERVER['REMOTE_ADDR'] . '! Push body:' . "\n" . $REQUEST_BODY
			);
			exit();
		// don't pull if the delivered branch isn't the local repo's branch
		} else if ($REQUEST_JSON['ref'] !== ('refs/heads/' . $LOCAL_BRANCH)) {
			$logger->debug(
				'Local branch ' . $LOCAL_BRANCH . ' =/= pushed branch ' .
				$REQUEST_JSON['ref'] . '; no pull initiated.'
			);
			exit();
		}
		$pull_branch = str_replace('refs/heads/', '', $REQUEST_JSON['ref']);
	// only allow empty-body requests (page hits) from whitelisted IPs
	} else if (
		!ip_allowed_to_deploy(
			$_SERVER['REMOTE_ADDR'],
			$config['allowed_manual_deploy_ips']
		)
	) {
		$logger->info($_SERVER['REMOTE_ADDR'] . ' denied deploy access.');
		exit();
	}
} else {
	$logger->warn('Skipping access checks due to empty configuration.');
}

if (empty($pull_branch)) {
	$pull_branch = $LOCAL_BRANCH;
}
// execute git clean / checkout
$shell_script_call = './' . $checkout_script . ' ' . $pull_branch;
$checkout_response = proc_exec($shell_script_call);

// construct output string
$output = file_get_contents($checkout_script);
$output = preg_replace(
	'/^(.+)$/m',
	'<span class="cli-anchor">$</span> <span class="cli-output">$1</span>',
	$output
);
$output .= "\n" . $checkout_response;

// make HTML output pretty for manual user access
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
