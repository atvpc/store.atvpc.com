<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
  <label for="password">Password</label>
  <input type="password" name="password" required>
  <input type="submit" value="Submit">
</form>

<pre>
<?php
if (isset($_POST['password'])) {
  $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

  echo "Length:  " . strlen($_POST['password']) . "\n";
  echo "MD5:     " . hash('md5', $_POST['password']) . "\n";
  echo "SHA256:  " . hash('sha256', $_POST['password']) . "\n";
  echo str_repeat('-', 73) . "\n";

  echo "Hash:    " . $hash . "\n";

  if (password_verify($_POST['password'], $hash)) {
    echo "Verify:  PASS";
  }
  else {
    echo "Verify:  FAILED";
  }
}
?>
</pre>