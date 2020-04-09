<?php
// Set this path to load.php of the framework.
// If you don't want to use the framework then set
// framework_path to an empty string and set all the config
// variables to what you would like
$framework_path = "../PHP-Framework/resources/phpScripts/load.php";

// If use_framework is an empty string these values will be over written
$max_size = 4096;
$padding = true;
$days_timeout = 7;
$id_size = 10;
$key_size = 8;

$table_name = "secrets_notes";

$sql_user = "database user";
$sql_pass = "database password";
$sql_db = "database name";
	

if(strlen($framework_path) > 1){
	require_once($framework_path);

	$sql_user = $_SESSION["dbUser"];
	$sql_pass = $_SESSION["dbPass"];
	$sql_db = $_SESSION["db"];

	if(!isset($_SESSION["secrets_max_size"])){
		create_config('secrets_max_size', intval($max_size), 'INT', 'The maximum base64 character count of the encoded string');
		create_config('secrets_timeout', intval($days_timeout), 'INT', 'The count in days that a note will be left in the database before being removed.');
		create_config('secrets_id_size', intval($id_size), 'INT', 'Size of the id automatically generated.');
		create_config('secrets_key_size', intval($key_size), 'INT', 'Size of the key automatically generated.');
		create_config('secrets_table_name', $table_name, 'STRING', 'The table name for secretes to save info into.');
	}

	$max_size = $_SESSION["secrets_max_size"];
	$days_timeout = $_SESSION["secrets_timeout"];
	$id_size = $_SESSION["secrets_id_size"];
	$key_size = $_SESSION["secrets_key_size"];
	$table_name = $_SESSION["secrets_table_name"];

}else{
	
	// Functions
	function secure_key($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
		$pieces = [];
		$max = strlen($keyspace) - 1;
		for ($i = 0; $i < $length; ++$i) {
			$pieces []= $keyspace[random_int(0, $max)];
		}
		$key = implode('', $pieces);
		return $key;
	}
}

$path = explode("?", $_SERVER["REQUEST_URI"])[0];

//Page
if($_SERVER["REQUEST_METHOD"] == "GET"){
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Secure Notes</title>
	<link rel='stylesheet' href='/resources/secrets/style.css'>
	<script src="/resources/qrcodejs/qrcode.js"></script>
	<script src="/resources/lz-string/libs/lz-string.js"></script>
	<script src="/resources/crypto-js/crypto-js.js"></script>
	<script src="/resources/secrets/script.js"></script>
</head>
<body style="background-color: black;color: white">
	<div class='header'>
		<div class='title'><a href="<?php echo $path;?>">Self Destructing Notes</a></div>
		<div class='menu'>
			<a href="<?php echo $path?>?about">About</a>
		</div>
	</div>
	<div id="content">
	<?php
		if(isset($_GET["id"])){
			$db = mysqli_connect("localhost", $sql_user, $sql_pass, $sql_db);
			$id = $db->real_escape_string($_GET["id"]);
			$command = "SELECT id FROM $table_name WHERE id = '$id'";
			$res = $db->query($command);
			$db->close();
			if($res->num_rows != 0){?>
				<h3>Once you proceed, the note will be shown to you and destroyed from the server.</h3>
				<h3>This cannot be undone.</h3>
				<button class="proceed" onclick="fetch_note()">View note</button>
				<?php
			}else{
				http_response_code(404);
				echo "<div class='notice'>Unable to find note.</div>";
			}
		}else{
			$id = secure_key($id_size);
			?>	
			<form onsubmit="return get_form(this, <?php echo $key_size ?>);">
				<textarea name='note' placeholder="Enter secrets"></textarea>
				<input type="hidden" name="id" value="<?php echo $id ?>"/>
				<input type="submit" />
			</form>
			<?php	
		}
	?>
	</div>
	<div class='footer'>
		<div class='copyright'>Copyright © <?php echo date(Y) ?> Michael Julander</div>
		<div class='source'><a href='https://github.com/Sodium-Hydrogen/Private-notes'>Source Code</a></div>
	</div>
</body>
</html>
<?php
}else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["api"])){
	header("Content-Type: Application/json");
	$db = mysqli_connect("localhost", $sql_user, $sql_pass, $sql_db);

	if(!$db){
		http_response_code(500);
		echo "{\"message\": \"Unable to access filesystem\"}";
	}else{
		$command = "SHOW TABLES $sql_db WHERE $table_name";
		$res = $db->query($command);

		if( $res->num_rows == 0){
			$command = "CREATE TABLE $table_name (id VARCHAR(50) PRIMARY KEY, msg LONGBLOB NOT NULL, expiration BIGINT NOT NULL, fingerprint VARCHAR(45) NOT NULL)";
			$db->query($command);
		}

		if(!isset($_POST['action'])){
			http_response_code(400);
			echo "{\"message\": \"Malformed expression.\"}";
		}else if($_POST['action'] == "save" && isset($_POST["msg"]) && isset($_POST["id"]) && isset($_POST["fingerprint"]) && strlen($_POST["id"]) == $id_size){
			$id = $db->real_escape_string($_POST["id"]);
			$command = "SELECT id FROM $table_name WHERE id = '$id'";
			$res = $db->query($command);
			if( $res->num_rows == 0){
				$msg = $db->real_escape_string($_POST["msg"]);
				$fingerprint = $db->real_escape_string($_POST["fingerprint"]);
				$expiration = time() + ($days_timeout * 24 * 60 * 60);
				$command = "INSERT INTO $table_name (id, msg, fingerprint, expiration) VALUES ('$id', '$msg', '$fingerprint', $expiration)";
				$db->query($command);
				echo "{\"message\": \"Saved Successfully\"}";
			}else{
				http_response_code(400);
				echo "{\"message\": \"Unable to save data.\"}";
			}
		}else if($_POST['action'] == "fetch" && isset($_POST["id"]) && isset($_POST["fingerprint"])){
			$id = $db->real_escape_string($_POST["id"]);
			$fingerprint = $db->real_escape_string($_POST["fingerprint"]);

			$command = "SELECT msg FROM $table_name WHERE id = '$id' AND fingerprint = '$fingerprint'";

			$res = $db->query($command);

			if($res->num_rows == 0){
				http_response_code(400);
				echo "{\"message\": \"Unable to find matching note.\"}";
			}else{
				$msg = $res->fetch_assoc()['msg'];
				$randomdata = secure_key(strlen($msg));
				$command = "UPDATE $table_name SET msg = '$randomdata WHERE id = '$id' AND fingerprint = '$fingerprint'";
				$db->query($command);
				$command = "DELETE FROM $table_name WHERE id = '$id' AND fingerprint = '$fingerprint'";
				$db->query($command);
				echo "{\"note\": \"$msg\"}";
			}
			
		}else{
			http_response_code(400);
			echo "{\"message\": \"Malformed expression.\"}";
		}

		$command = "DELETE FROM $table_name WHERE expiration < ".intval(time());
		$db->query($command);

		$db->close();
	}
}

?>
