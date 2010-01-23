<?
session_name('LDAPAuthSession');
session_start();

if(isset($_GET['logout'])) {
	// Resetta tutte le variabili di sessione
	$_SESSION = array();

	//Distrugge i cookie di sessione
	if (isset($_COOKIE[session_name()])) {
  	  setcookie(session_name(), '', time()-42000, '/');
	}
	//disturgge la session
	session_unset();
	session_destroy();
	echo "logout effettuato!";

}

if (!isset($_SESSION['group'])){ die('solo i membri che effettuano il login possono accedere a questo contenuto protetto'); }?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Login avvenuto con successo</title>
</head>
<body>
<?  
	echo "Benvenuto " .$_SESSION['username'] ."<p> Ecco cosa c'e' nella tua session:";
         foreach ($_SESSION as $key=>$value){
		echo "<p>SESSION['".$key."'] => ";
		print_r($value);
         }
	

?>

<form method="get" action="welcome.php">
<input type="submit" name="logout" value="effettua il log out.."/>
</form>
</html>


