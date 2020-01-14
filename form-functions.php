<?php

// SEE --> allow users to see transaction table
function see ($ucid, $account) {

  global $db; //variable from parent program

  // select from accounts table
  $s_acct = "select * from accounts where ucid='$ucid' and account='$account'";
  print "<br>SQL select statement for account information: $s_acct<br>";

  ($t_acct = mysqli_query($db, $s_acct)) or die (mysqli_error($db));
  $r_acct = mysqli_fetch_array($t_acct, MYSQLI_ASSOC);

  $u = $r_acct["ucid"];
  $acct = $r_acct["account"];
  $balance = $r_acct["balance"];


  // select from transactions table
  $s = "select * from transactions where ucid='$ucid' and account='$account'";
  print "<br>SQL select statement for transactions: $s<br>";

  ($t = mysqli_query($db, $s)) or die (mysqli_error($db));

  //indicate num rows retrieved
  $num = mysqli_num_rows($t);
  print "<br>Number of rows retrieved: $num <br>";

  print "<br><strong>Account Information:</strong></br>";
  print "UCID: $u || Account No: $acct || Current Balance: $balance";


  if ($num == 0) {
    print "<br><strong>No recent transactions.</strong>";
  } else {
    print "<br><strong>Recent transactions:</strong>";
  }


  // while loop --> loop through & print the rows in db table
  while ( $r = mysqli_fetch_array ($t, MYSQLI_ASSOC)){

    $amount = $r["amount"];
    $timestamp = $r["timestamp"];
    $mail = $r["mail"];

    if ($amount[0] == "-") {
    $no_neg_sign = substr($amount, 1);
    print "<br>UCID: $u || Account No.: $account || Amount: -\$$no_neg_sign || Timestamp: $timestamp || Mail: $mail";
  } else {
    print "<br>UCID: $u || Account No: $account || Amount: \$$amount || Timestamp: $timestamp || Mail: $mail";
  }
  };

}

// TRANSACT
function transact ($ucid, $account, $amount) {

  global $db;

  //add or subtract amount from accounts table
  $s_acct = "update accounts set balance = balance + '$amount'
            where ucid = '$ucid'
            and account = '$account'
            and balance + '$amount' >= 0.00";

  print "<br>SQL update statement: $s_acct<br>";

  ($t_acct = mysqli_query($db, $s_acct)) or die (mysqli_error($db));
  $num_acct = mysqli_affected_rows($db);
  print "<br>Number of rows affected on accounts table: $num_acct<br>";

  // Overdraft attempt --> Prevents negative balance
  if ($num_acct == 0) {
    print "<br><strong>Overdraft attempt was rejected.</strong><br> <hr>";
    see($ucid, $account);
    return;
  }

  // Update transactions table
  $s_transact = "insert into transactions
                values ('$ucid', '$account', '$amount', NOW(), 'N')";

  ($t_transact= mysqli_query($db, $s_transact)) or die (mysqli_error($db));
  $num_transact = mysqli_affected_rows($db);
  print "<br>Number of rows affected on transactions table: $num_transact<br> <hr>";

  see($ucid, $account);

}

// CLEAR

function clear ($ucid, $account) {

  global $db;

  // Update and set balance to 0.00 in user account
  $s_acct = "update accounts
            set balance = 0.00
            where ucid = '$ucid'
            and account = '$account'";
  print "<br>SQL Reset Statement: $s_acct</br>";
  ($t_acct = mysqli_query($db, $s_acct)) or die (mysqli_error($db));

  // Remove rows from transactions table
  $s_transact = "delete from transactions
                where ucid = '$ucid'
                and account = '$account'";
  print "SQL Delete Statement: $s_transact</br> <hr>";
  ($t_acct = mysqli_query($db, $s_transact)) or die (mysqli_error($db));


  see($ucid, $account);


}

// ALTER

function alter ($column, $value, $ucid, $pwd) {

  global $db, $newEmail, $newPassword, $value;

  $s_update = "update users
              set $column = '$value'
              where ucid = '$ucid'";
  print "<br>SQL Update Statement: $s_update</br> <hr>";
  ($t_update = mysqli_query($db, $s_update)) or die (mysqli_error($db));

  $s_select = "select * from users
              where ucid = '$ucid'";
  ($t_select = mysqli_query($db, $s_select)) or die (mysqli_error($db));

  print "<br><strong>Revised Users Table:</strong> <br><br>";

  while ( $r = mysqli_fetch_array ($t_select, MYSQLI_ASSOC)){

    $ucid = $r["ucid"];
    $pass = $r["pass"];
    $e= $r["e"];

    print "<br>UCID: $ucid || Password: $pass || Email: $e <br>";

  };

}

// GET

function get($fieldname, &$dataOK) {
  global $db, $warnings;

  $inputdata = $_GET[$fieldname];
  $inputdata = trim($inputdata);
  $inputdata = mysqli_real_escape_string($db, $inputdata);

  if ( ($inputdata == "") && ($fieldname == "ucid") ) {
    $warnings .= "<br>Empty UCID field.";
    $dataOK = false;
  }

  if ( ($inputdata =="") && ($fieldname == "password") ) {
    $warnings .= "<br>Empty password field.";
    $dataOK = false;
  }

  if ( ($inputdata != "") && ($fieldname == "amount") && (!is_numeric($inputdata))) {
    $warnings .= "<br>Entered a non-numeric amount.";
    $dataOK = false;
  }

  print "<br> The $fieldname entered is $inputdata";

  return $inputdata;

}



// AUTHENTICATE using ucid and password from users table
function authenticate ($ucid, $pwd, &$DBpin) {
  // $DBpin is an output value

  global $db; //variable from parent program

  $s = "select * from users where ucid='$ucid' and pass='$pwd'";
  // print SQL statement
  print "<br>SQL credentials select statement: $s<br>";


    ($t = mysqli_query($db, $s)) or die (mysqli_error($db));
    $num = mysqli_num_rows($t);
    print "<br>Number of rows retrieved: $num <br> <hr>";

    if ($num == 0) {
      return False;
    }

    // get DBpin from db row
    $r = mysqli_fetch_array ($t, MYSQLI_ASSOC);
    $DBpin = $r["pin"];

    return True;
}


// TWO-FACTOR AUTHENTICATION with random generated pin
function super_auth($ucid, $pwd, &$state, &$newpin) {

  global $db;

  if (! authenticate($ucid, $pwd, $DBpin)) {

    $state = 0;
    return False;

  } else {
    if ( !isset($_GET["pin"]) || ($_GET["pin"] == 0) || ($DBpin != $_GET["pin"])) {

      $newpin = randomPIN();
      mymail($newpin); //regenerate pin & mail new pin
      $state = 1;
      return False;

    } else {
      // reset users pin to 0: SQL update
      $s = "update users set pin='0'
            where ucid = '$ucid'
            and pass = '$pwd'";
      print "<br><strong>Pin is reset to 0.</strong> SQL Statement is: $s<br> <hr>";
      ($t = mysqli_query($db, $s)) or die (mysqli_error($db));
      $state = -2;
      return True;
    }
  }


}


// generate random PIN for authentication
function randomPIN() {


  global $ucid, $db; //variable from parent program

  $newpin = mt_rand(1001, 9999);

  // insert pin into users DB
  $s = "update users set pin = '$newpin' where ucid = '$ucid'";
  echo "<br>SQL generate new pin statement: $s </br>";
  ($t = mysqli_query($db, $s)) or die (mysqli_error($db));

  return $newpin;
}



function mymail($newpin) {

  $to = "aam235@njit.edu";
  $subj = "New PIN";
  $msg = $newpin;

  mail($to, $subj, $msg);
}

?>
