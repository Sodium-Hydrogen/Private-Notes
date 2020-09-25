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
$key_size = 12;

$table_name = "secrets_notes";

$sql_user = "database user";
$sql_pass = "database password";
$sql_db = "database name";
	

if(strlen($framework_path) > 1){
	require_once($framework_path);

	$sql_user = $_SESSION["dbUser"];
	$sql_pass = $_SESSION["dbPass"];
	$sql_db = $_SESSION["db"];

	if(!isset($_SESSION["s_notes_max_size"])){
		create_config('s_notes_max_size', intval($max_size), 'INT', 'The maximum base64 character count of the encoded string');
		create_config('s_notes_timeout', intval($days_timeout), 'INT', 'The count in days that a note will be left in the database before being removed.');
		create_config('s_notes_id_size', intval($id_size), 'INT', 'Size of the id automatically generated.');
		create_config('s_notes_key_size', intval($key_size), 'INT', 'Size of the key automatically generated.');
		create_config('s_notes_table_name', $table_name, 'STRING', 'The table name for secretes to save info into.');
	}

	$max_size = $_SESSION["s_notes_max_size"];
	$days_timeout = $_SESSION["s_notes_timeout"];
	$id_size = $_SESSION["s_notes_id_size"];
	$key_size = $_SESSION["s_notes_key_size"];
	$table_name = $_SESSION["s_notes_table_name"];

	setcookie("PHPSESSID", "", 0);

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
	<title>Secure Notes</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel='stylesheet' type="text/css" href='/resources/secrets/style.css' integrity="sha256-8oSW4QhPKEbdBAh8SLtqvRj+hYn2nkqL69mRDjTXEJU=">
	<!-- <link rel='stylesheet' type="text/css" href='/resources/secrets/style.css' > !-->
	<script type="application/javascript" src="/resources/qrcodejs/qrcode.js" integrity="sha256-Puct6facZo+VZzY6k1jflVlguukADZ69ZkFGcPiOhzU="></script>
	<script type="application/javascript" src="/resources/lz-string/libs/lz-string.js" integrity="sha256-VKnqrEjU/F8ZC4hVDG+ULG96+VduFx/l3iTBag26gsM="></script>
	<script type="application/javascript" src="/resources/crypto-js/crypto-js.js" integrity="sha256-u605MhHOcevkqVw8DJ2q3X7kZTVTVXot4PjxIucLiMM="></script>
	<script type="application/javascript" src="/resources/secrets/script.js" integrity="sha256-NxmF615nq4cn2rDeC/G+a9iblJsOvLOrrgcM5Nseoa0="></script>
	<!--<script type="application/javascript" src="/resources/secrets/script.js" ></script> !-->
</head>
<body <?php if(isset($_GET["id"])){echo "onload='add_password_box()'";} ?>>
	<div class='header'>
		<div class='title'><a href="<?php echo $path;?>">Self Destructing Notes</a></div>
		<div class='menu'>
			<a href="<?php echo $path?>">New Note</a>
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
				<div id="op-box"><button class="proceed" onclick="fetch_note()">View note</button></div>
				<?php
			}else{
				http_response_code(404);
				echo "<div class='notice'>Unable to find note.</div>";
			}
		}else if(empty($_SERVER["QUERY_STRING"])){
			$id = secure_key($id_size);
			?>	
			<form onsubmit="return get_form(this, <?php echo $key_size ?>);">
				<textarea class="fill" name="note" placeholder="Enter secrets"></textarea>
				<input type="hidden" name="id" value="<?php echo $id ?>"/>
				<div class="row fill">
					<span>Optional Password: <input type="text" name="op" autocomplete='off' /></span>
					<input type="submit" value="Submit Note"/>
				</div>
			</form>
			<?php	
		}else if(isset($_GET["about"])){ ?>
			<div class="fill left-align text">
				<h2>About <?php echo $_SERVER["SERVER_NAME"] ?></h2>
				<p> This is an open source destructing notes website. Any note is destroyed after being read for the first time and all
				information is hidden from the server and will atomatically expire <?php echo $days_timeout ?> days after being created.</p>
				<!-- <h3>Is my data safe?</h3>
				<p>Mostly. There is no garuntee in complete security, but this site uses AES in CBC mode with a 256 bit HMAC encrypted key
				before being saved. Or in laymans terms, the data is encrypted with a NSA approved algorigthm that for worst case would take
				longer than the world has been around to guess the correct key to decrypt the message.</p>
				<p>As long as the code hasn't been modified what you send should be relatively safe. However since anyone can run this code
				a malisous server admin may write some code to steal the note before being saved and if you know how to program you can read
				the code just to make sure or send a test note and watch the network requests to see what information is sent.</p>
				<h3>How does it work?</h3>
				<p>The site encrypts the message using AES in the browser then sends only the encrypted message and a SHA256 fingerprint of
				the key and id combined so the server doesn't know what the decryption key is. The key is provided in the url as a hash value
				which is never sent to the server.</p>
				!-->
				<h3>Security</h3>
				<p>All notes are encrypted using AES in CBC mode with a 256 bit SHA HMAC key in the browser then a SHA-256 fingerprint is created
				from the id and passphrase combination then the encrypted message and fingerprint are sent to the server. When a note is requested
				from the server the server will only return the encrypted note if both the id and fingerprint match. Before the note is sent to the
				browser the encypted message is overwritten with random information then deleted. After the deletion the note is sent and decrypted
				in the browser and displayed.</p>
				<h3>One time view</h3>
				<p>Every note is destroyed from the filesystem before it is sent to the browser and if the note is ever requested again this site
				will return a 404 not found error.</p>
				<h3>Expiration</h3>
				<p>To ensure safety each note is tagged with an expiration date when the server saves it and after the expiration the note is
				destroyed and will no longer be available to view.</p>
				<p>The expiration is currently set to <?php echo $days_timeout ?> days.
				<h3>URL</h3>
				<p>Since each note needs a key to decypt it, when a note is using a generated key that key is provided as a hash value in the url.
				A hash value is never sent to the server and remains only in the browser to make sure the server will never have enough information
				to decrypt the note.</p>
				<h3>Why a fingerprint?</h3>
				<p>A SHA256 fingerprint is used because as of right now the only way to get the values out of a SHA256 hash is to know what it
				is, or to brute force every combination until you find what works. The fingerprint is used by the server to ensure that you 
				have all the correct information before sending the encrypted message to slow down an attacker and protect the message.</p>
				<h3>This site sends a cookie</h3>
				<p>To have the plug and play functionality of the framework that manages the database for this service it does set a cookie <em>PHPSESSID</em>. To ensure privacy this also sets the cookie to expire immediately so it is never sent back to the server in following requests.</p>
				<h3>Are there backdoors?</h3>
				<p>Not in the default source code. Make sure you trust the server or you can check the checksum values in the html head against the ones
				in the source code to make sure none of the external resources have been modified.</p>
				<br>
				<p>Additionally you should check the html code to make sure there are no &lt;script&gt;	tags with any javascript programmed inside.
				There should only be 4: CryptoJS, lz-string, qrcode, and the main script.</p>
			</div>
		<?php	}else{
			echo "Error ".$_SERVER["REDIRECT_STATUS"];
		}
	?>
	</div>
	<div class="footer">
		<div class="copyright">Copyright © <?php echo date(Y) ?> Michael Julander</div>
		<div class="source"><a href="https://github.com/Sodium-Hydrogen/Private-notes">Source Code</a></div>
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
