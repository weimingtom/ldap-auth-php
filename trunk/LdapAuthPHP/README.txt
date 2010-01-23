IN QUESTO ARCHIVIO:

	core/: contiene la classe principale della libreria, l'unica necessaria al funzionamento della libreria

	dist/: contiene tutto il necessario per implementare un autenticazione standalone (senza dover riscrivere il modulo di login)

	demo/ : piccole demo di autenticazione standalone o integrata ad altri sistemi


GUIDA ALL'USO:

https://twiki.garr.it/wiki/bin/view/Development/LdapAuthenticator

-----

Requirements

    * PHP >= 4
    * modulo php-ldap
    * openssl
    * configurare il file /etc/ldap/ldap.conf (client ldap) in questo modo: 

 
   # per ora skippiamo la validazione del certificato SSL:
   TLS_REQCERT never  

   # anziche'
   # TLS_CACERT  /etc/openldap/cert/CAcert.pem  

   # ATTENZIONE pero': dopo una prima fase di test per essere sicuri che nessuno faccia spoofing dell'LDAP 
   # server (il che vorrebbe dire: accedere alle applicazioni protette!)  potete richedere a me o a Cristiano
   # il CA file del certificato generato sul server LDAP ed usare quello sulla vostra macchina. 
   # Pero ora, Vi sto consigliando questo "TLS_REQCERT never" solo per velocizzare le fasi di test 
   # ed iniziare a provare la classe senza dipendere da nessuno.
   

Come funziona?

Data la scarsita' di documentazione sull'argomento, la parte piu' difficile del lavoro svolto per creare questa classe e' stata proprio capire come funziona l' autenticazione via LDAP.

Negli RFC infatti, si parla molto bene di come e' implementata l'autenticazione su LDAP, ma non di come integrarla con sistemi etereogenei: RFC2829 -- Authentication Methods for LDAP

In pratica sappiamo che per autenticarsi su LDAP con il metodo simple password dobbiamo fornire un DN (Distinguished Name) che identifica l'utente, ed una password. Semplice.

Ma, il problema che si pone nell'integrazione con sistemi etereogenei, e' che il DN e' un valore che rappresenta una foglia del'albero LDAP, cioe' un valore del tipo: _ "cn=Stefano Gargiulo,ou=Sw Dev,ou=Groups,dc=dir,dc=garr,dc=it"_, In pratica questo valore rappresenta il DN che dovrei usare per loggarmi su LDAP (effettuare il bind), cioe' la mia "username" LDAP, ovvero cio' che mi identifica nell'albero distinguendomi dagli altri.

Quindi per fare un bind su LDAP con la mia password dovrei fornire questo valore come "username".

Ora immaginate di aver configurato PAM-LDAP come metodo per il login su una macchina linux, non credo qualcuno trovi opportuno (compreso il sistema), il dover digitare una cosa del genere:

  ssh 'cn=Stefano Gargiulo,ou=Sw Dev,ou=Groups,dc=dir,dc=garr,dc=it'@miamacchina.dir.garr.it
  

infatti PAM non prova ad effettuare direttamente il bind con l'username fornita ma effettua l'autenticazione in questo modo: -1 riceve un username tradizionale es. "gargiulo" -1 si collega in modalita' anonymous (o con un'account di sola lettura creato appositamente) al server LDAP -1 effettua una ricerca della username "gargiulo" negli attributi delle foglie dell'albero LDAP (partendo da una BASEDN fissata a priori) -1 quando trova l'utente in questione ottiene il DN completo dell'utente e prova a fare il bind con la password fornita

Per approfondire potete leggere questo tutorial, di cui, qui di seguito, riporto la parte relativa a quanto appena detto:

...
..By running this LDAP configuration, I can track what the PAM LDAP module
does.  I typed "su boris" in a command window and supplied the plain-text
password "password.for.boris".  The PAM module first does a SEARCH to check
that the user exists and to get his details.  The result includes the
encrypted password("encrypted.string"), but the PAM module ignores that.  It
does a BIND as that user, supplying the plain-text password that I gave it.
The LDAP server checks the password before allowing the BIND, so if it
succeeds, the password is correct.  The BIND is just a stunt to check the
password, so the PAM module does an UNBIND immediately..
...

Concludendo, per i motivi di usabilita' e integrazione suddetti, la tecnica di autenticazione che e' stata scelta nell'implementazione di LdapAuth? e' la stessa usata dal modulo PAM-LDAP e da tanti altri moduli di integrazione con LDAP:

search + bind.

Ovviamente il tutto viaggia su sessioni SSL e le password sono Crittate.

Cosa fa?

La classe consente di:

   1. proteggere una semplice pagina PHP in pochi secondi
   2. proteggere un piccolo sito web PHP in pochi secondi
   3. offrire un servizio di autenticazione e preparare gli attribui che poi serviranno all'autorizazzione per siti e applicazioni che necessitano di controlli piu' granulari 

Come usarla?

Vi sono vari modi per usare questa classe, tutto sta alla fantasia del programmatore, ecco alcuni esempi che mostrano alcune delle alternative possibili:

the easy way (proteggo una pagina usando la form di login gia' pronta):

<? include("LdapAuth.inc.php");
 
     $ldap=new LdapAuth(); //<- obbligatorio
     include("LdapStandalonePageProtector.inc.php"); // protegge il contenuto sottostante mostrando una form
 di login universale
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
...
pagina PHP da proteggere
...
</html>

in questo modo abbiamo solo aggiunto un controllo sulla pagina, che verra' visualizzata solo dopo l'inserimento di username e password corretti, ma possiamo anche aggiungere un controllo sulla session che faccia in modo che la form di login sia mostrata solo una volta per sessione autenticata e non ogni qualvolta si riaccede alla pagina:

<?
     session_start("LdapAuthSession");
     if (!isset($_SESSION["group"]) || !(in_array($gruppi_autorizzati,$_SESSION["group"])){ //se il login ancora non 
//e' avvenuto o il gruppo non e' autorizzato mostrera' la form di login
         $ldap=new LdapAuth(); //<- obbligatorio
         //inizio customizzazione:
         $ldap->setSessionAttr("group","ou"); //sto dicendo alla classe LDAP di crearmi un'attributo group nella session
 prendendolo dal campo LDAP ou dell'utente (solo se il login avra' successo)
          $ldap->setRedirectPage($_SERVER["script_name"]); //a login avvenuto ricaricera' questa pagina
          session_name("LdapAuthSession"); //imposto il nome con il quale la session sara' inizializzata (session_name)
          include("LdapStandalonePageProtector.inc.php"); // protegge il contenuto sottostante mostrando una
 form di login universale
     }
     else{
?>
          ...qui il codice della pagina html/php..
<?
     }
?>

copiando questo codice in tutte le pagine del sito abbiamo creato un sistema di autenticazione-autorizzazione per l'intero sito

oppure potremmo creare un header.inc.php contenente:

<?
     session_start("LdapAuthSession");
     if (!isset($_SESSION["group"]) || !(in_array($gruppi_autorizzati,$_SESSION["group"])){ //se il login ancora non e' 
avvenuto o il gruppo non e' autorizzato mostrera' la form di login
         $ldap=new LdapAuth(); //<- obbligatorio
         //inizio customizzazione:
         $ldap->setSessionAttr("group","ou"); //sto dicendo alla classe LDAP di crearmi un'attributo
 //group nella session prendendolo dal campo LDAP ou (solo se il login avra' successo
          $ldap->setRedirectPage($_SERVER["script_name"]); //a login avvenuto ricaricera' questa pagina
          session_name("LdapAuthSession"); //imposto il nome con il quale la session sara' inizializzata (session_name)
          include("LdapStandalonePageProtector.inc.php"); // protegge il contenuto sottostante mostrando una 
 //form di login universale
          die(); //muore proteggendo i contenuti sottostanti
     }

?>

in quest'ultimo caso ci bastera fare un include(header.inc.php); in testa ad ogni pagina del sito per implementare il sistema.

Questi esempi usavano LdapStandalonePageProtector? , cioe' il modulo di login fornito con la classe, che serve a semplificare la vita a chi non a voglia di scriversi una form di login, ma in applicazioni piu' grandi dove si vuol conservare una form di login gia' esistente, ed eventuali metodi di autorizzazione LdapAuth? puo' essere usata , ad esempio, in questo modo:

login.php:

<form action="dologin.php" method="POST">
<input name="username" ...
<input name="password" ...
...
</form>

dologin.php:

  $ldap=newLdapAuth(); //<- obbligatorio

  //inizio customizzazione:
   $ldap->setSessionName("MYSESSION");
   $ldap->setSessionAttr("username","uid");  // verra' creato un attributo 
//$_SESSION["username"] che conterra' il campo uid preso da LDAP
   $ldap->setSessionAttr("group","ou");      // verra' creato un attributo
//$_SESSION["group"] che conterra' il campo ou preso da LDAP
   $ldap->setSessionAttr("realname","gecos");      
   $ldap->setRedirectPage("welcome.php");    // la pagina verso la quale vogliamo 
//far redirigere l'utente che effettua il login con successo (pagina che ovviamente si protegge controllando la session)
   $ldap->setErrorPage($_SERVER['SCRIPT_NAME']); //pagina di errore da mostrare
// in caso di login fallito (in questo caso se stessa, questa pagina ricevera'un 
// parametro ?error=<errore login> in modo tale da dare la possibilita'di stampare il messaggio di errore )
   
   include("LdapLoginReceiver.inc.php"); // riceve la richiesta di login e chiama il metodo Ldap->Authenticate

in welcome.php e tutte le altre pagine da proteggere basta includere questo header (o qualcosa di simile) authcheck.header.inc.php:

   session_name("MYSESSION");
   session_start();
   if ( isset($_SESSION["group"]) && in_array($_SESSION["group"],$gruppi_autorizzati))
   {
       echo "Logged in as: ".$_SESSION["realname"];
   }
   else{
       die("Sorry, you are not authorized to view this page");
   }
   ...
   pagina da proteggere
   ...

un altro modo per usare la classe, quello piu' diretto, e' invocarne direttamente il metodo di autenticazione , come abbiamo fatto con Giovanni su GINS:

function auth_by_LDAP($user,$password)
{
   require_once("LdapAuth.inc.php");
   $ldap=new LdapAuth();
   $ldap->setCredentials($user,$password);
   $ldap->ExportAttr("myapp_user","uid");
   $ldap->ExportAttr("myapp_group","ou");
   $ldap->useLocalStorage(true); 

   $result=$ldap->Authenticate();

   if ($result==$ldap->AUTH_SUCCESSFULL){
       /* se si desidera si possono sovrascrivere gli attributi della session 
(che a questo punto e' gia' stata creata dal metodo Authenicate) o aggiungere controlli in questo modo:
       if ($ldap->getLocalAttr('myapp_group')=="Noc"){
                 $_SESSION['myapp_group']="noc_user";
       }
       else { 
          $_SESSION['myapp_group']="limited_user"; 
       }
       */
       return true;
   }
   else return false;
}

ecc. ecc.

come vedete possiamo usare questa classe in molti modi diversi, per capire quanti e come crearne di nuovi, vi rimando al phpdoc: http://193.206.158.192/LdapAuth/docs/LdapAuthenticator/LdapAuth.html dove potete la descrizione di tutti i metodi offerti dalla classe, inoltre la lettura del sorgente della classe stessa (LdapAuth? .inc.php) che trovate nell'archivo scaricabile da questa pagina e' abbastanza esplicativa. 