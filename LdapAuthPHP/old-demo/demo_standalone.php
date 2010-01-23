<? include("LdapAuth.inc.php");
 
     $ldap=new LdapAuth(); //<- obbligatorio
     
     //inizio customizzazione:
     $ldap->setSessionAttr("group","ou");
     $ldap->setSessionAttr("parent","_parentNode");
     //cookie della durata di dieci minuti.. prova a scommentarlo e il login sara' memorizzato
     //$ldap->useCookies(true,10); //cookie della durata di 10 minuti 
     session_name("LdapAuthSession");
     session_start();
     if (!isset($_SESSION["username"])){ //non chiede piu' il login se l'username e' nella session
     include("LdapStandalonePageProtector.inc.php"); // protegge il contenuto sottostante come un semplice htaccess
     } 

//qui aggiungere un altro controllo personalizzato ad esempio
// if ($_SESSION["group"]!="Sw Dev") {die('Non Autorizzato');}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Login test page for LDAPAuth</title>
</head>
<body>

questo e' un.. <p>
contenuto top secret
<p>
Bravo <? 
echo $_SESSION["username"]; ?>
 :P il tuo nodo padre e' : <?echo $_SESSION['parent'];?>

</body>
</html>

