<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors' , 1);

include ("form-functions.php");

// connect to database
include (  "account.php"     ) ;
$db = mysqli_connect($hostname, $username, $password, $project);
if (mysqli_connect_errno())
  {
	  echo "<strong>Failed to connect to MySQL: </strong>" . mysqli_connect_error();
	  exit();
  }
print "<strong>Successfully connected to MySQL.</strong><br><br><hr>";
mysqli_select_db( $db, $project );

// initialize variables
$dataOK = True;
$state = -2;
$warnings = "";

// get function for ucid, password, account num
$ucid = get("ucid", $dataOK);
$pwd = get("password", $dataOK);
$account = get("account", $dataOK);
print "<br><br><hr>";

$choice = get("choice", $dataOK);

//$amount = $_GET["amount"];

if (!$dataOK) {
  $state = -1;
} else {
  $state = -2;
}

$column = $_GET ["col"];
$newEmail = $_GET ["newemail"];
$newPassword = $_GET ["newpwd"];

$newpin = $_GET ["pin"];


if ($column == "e") {
  $value = $newEmail;
}

if ($column == "pass") {
  $value = $newPassword;
}



if ($dataOK && super_auth($ucid, $pwd, $state, $newpin)) {
  // defined function from form-functions.php
  if ($choice == "see") {
    see($ucid, $account);
  } elseif ($choice == "transact") {
    // get function for amount
    $amount = get("amount", $dataOK);
    transact($ucid, $account, $amount);
  } elseif ($choice == "clear") {
    clear($ucid, $account);
  } elseif ($choice == "alter") {
    alter($column, $value, $ucid, $pwd);
  }
  exit(); // if authentication is valid, sticky form disappears
} // defined function from form-functions.php




?>


<?php
// Print error messages based on $state

if ($state == -1) {
  echo "<br><strong>Bad input data:</strong></br>$warnings";
}
?>

<?php
if ($state == 0) {
  echo "<br><strong>Not authenticated!</strong>";
}
?>


<?php if ($state == 1) {
  echo "<br><strong>Enter new pin: $newpin</strong><br>";
}
?>


<!DOCTYPE html>

<!-- This is the sticky form -->

<style>
  #transact {
    display: none;
    border: solid red 2px;
    width: 200px;
}

  #alter {
    display: none;
    border: solid red 2px;
    width: 200px;
}
  }

  #enterpin {
    border: solid red 2px;
  }


</style>

<form action="main.php">

<br>

<?php if ($state == 1) : ?>
  <label for="enterpin">Enter PIN: </label>
  <input type"text" name="pin" id="enterpin"><br><br>
<?php endif; ?>

<label for="ucid">Enter UCID: </label>
<input type="text" name="ucid" value="<?php print $ucid; ?>" id="ucid" required placeholder="Enter UCID" autocomplete="of"><br><br>

<label for="pwd">Enter Password: </label>
<input type"text" name="password" value="<?php print $pwd; ?>" id="pwd" required placeholder="Enter Password" autocomplete="off"><br><br>

<label for="acct">Enter Account Number: </label>
<input type"text" name="account" value="<?php print $account; ?>" id="acct" placeholder="Enter Account Number" autocomplete="off"><br><br>

<select name="choice" id="choice">
  <option value="0">choose</option>
  <option value="see">see</option>
  <option value="transact">transact</option>
  <option value="alter">alter</option>
  <option value="clear">clear</option>
</select>

<div id="transact">
  <label for="amt">Amount: </label>
  <input type="text" name="amount" id="amt" placeholder="9999.99">
</div>

<div id="alter">

  <label for="col">Column: </label>
  <input type="text" name="col" id="col" placeholder="e or pass">

  <br>

  <label for="newe">New Email: </label>
  <input type="text" name="newemail" id="newe" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2, 3}$">

  <br>

  <label for="newpass">New Password: </label>
  <input type="text" name="newpwd" id="newpass">

</div>

<button type="submit" id="submit">Submit Query</button>

<br>
</form>

<script>
var menu = document.getElementById("choice");
var transact = document.getElementById("transact");
var alter = document.getElementById("alter");
var enterpin = document.getElementById("enterpin");



menu.addEventListener("change", choice_f);

function choice_f() {
  var menu_val = menu.value; // value of selected element of menu
  if (menu_val == "transact") {
    transact.style.display = "block";
  } else {
    transact.style.display = "none";
  }

  if (menu_val == "alter") {
    alter.style.display = "block";
  } else {
    alter.style.display = "none";
  }


}




</script>

</html>
