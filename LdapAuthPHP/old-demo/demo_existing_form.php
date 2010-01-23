<? include("LdapAuth.inc.php");
 
    $ldap=new LdapAuth(); //<- obbligatorio
 
    //inizio customizzazione:
 
    $ldap->setSessionAttr("username","uid");  // verra' creato un attributo $_SESSION["username"] che conterra' il campo uid preso da LDAP
 
    $ldap->setSessionAttr("group","ou");      // verra' creato un attributo $_SESSION["group"] che conterra' il campo ou preso da LDAP

    $ldap->setSessionAttr("nome_reale","gecos");

    $ldap->setSessionAttr("indirizzo email","mail");

    $ldap->setSessionAttr("title","title");
	    
    $ldap->setSessionAttr("shell","loginShell");
 
    $ldap->setRedirectPage("welcome.php");    // la pagina verso la quale vogliamo far redirigere l'utente che effettua il login con successo (pagina che ovviamente si protegge controllando la session)
 
    $ldap->setRedirectErrorPage($_SERVER['SCRIPT_NAME']); //pagina di errore da mostrare in caso di login fallito (in questo caso se stessa, questa pagina ricevera'un parametro ?error=<errore login> in modo tale da dare la possibilita'di stampare un messaggio di errore )
    
    include("LdapLoginReceiver.inc.php"); // riceve la richiesta di login
 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Login test page for LDAPAuth</title>
</head>
<body>
<? 
//in caso di errore LdapAuth manda un parametro chiamato error
if (isset($_GET['error'])) { 
	echo "Errore durante il login ldap: " .$_GET['error'] ."<p>";
}
?>
<h2>Ldap Login Demo</h2><br>
<form method="POST" action="<?echo $_SERVER['script_name']?>"/>
Nome utente: <input type="text" name="username"/>
Password: <input type="password" name="password"/>
<input type="submit" value="log in!"/>
</form>
</body>
</html>

