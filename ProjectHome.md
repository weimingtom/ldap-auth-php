Adding LDAP AuthN and AuthZ in PHP applications is a very easy thing with **LDAPAuthPHP**.

Features:

  * Load balancing and HA beetween multiple LDAP servers
  * HTML Standalone Page Protector (if you are lazy you don’t need to write a login form)
  * BasicAuth Standalone Page Protector (wanna LDAP-protect an RSS feed?)
  * Custom attribute fetching (for AuthZ)
  * Configurable  ldap2Session attribute mapping (LDAP Attr -> PHP Session Attr)
  * Access logging (to be improved and localized)

**An** usage example (there are a lot of different ways):

```
<?php
$pageURL = 'http';
if ($_SERVER["HTTPS"] == "on") {	
    $pageURL .= "s";	
}	
$pageURL .= "://";	
if ($_SERVER["SERVER_PORT"] != "80") {	
    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];	
} else {	
    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];	
}

session_name('MYAPP');	
session_start();	
if (!isset($_SESSION["user"])) {	
    include("LdapAuth.inc.php");	
    $ldap=new LdapAuth();	
    $ldap->setSessionAttr("user","uid");	
    $ldap->setSessionName("MYAPP");	
    $ldap->setRedirectPage($pageURL); //page where we get redirected after login (in this case self)
    include("LdapStandalonePageProtector.inc.php");
}	
else { 
  echo "Logged In As: ".$_SESSION["user"]."</hr>";
  //paste here the old page code (or write the new page to protect)
}
```


---


To configure LDAPAuthPHP go in **LDAPAuth.inc.php** and search for the following section:


```
 /* Configuration section: */
        private $serviceUser="cn=LdapAuthenticator,ou=Groups,dc=mydomain,dc=com";
        private $serviceSecret="serviceUSERpassword";
        /* Tip: a service user is required (keeping enabled anonymous access is a bad thing)
         * and you are supposed do write some ACL to limit the service user to read-only the cn 
         * and the uid attribute in the People tree 
         */
        private $BaseDn="ou=People,dc=mydomain,dc=com"; //where are the users in the tree?
        private $UIDAttributeName="uid"; // what attribute you wanna search for the search & bind login? 
//e.g. "mail" let users to login with their email address and password

        private $ServerList = Array(
            /* Multiple LDAP Servers: for load balancing/ HA redundancy mode, not for multi-ldap auth!!!!
             *  (Server MUST have some user tree synchronization mechanism e.g. OpenLDAP syncrepl ) */
            Array(
                                        "ip"=>"123.123.123.123",
                                        "name"=>"ldap-master",
                                        "sslport"=>636,
                                        "port"=>389
            ),
            Array(
                                        "ip"=>"ldap125.mydomain.com",
                                        "name"=>"ldap-replica",
                                        "sslport"=>636,
                                        "port"=>389
            )
            /* You can add or remove LDAP server entries (But this is not multi-ldap:
             *  servers MUST have the same user tree */
        );

        private $accessLogFile="ldap.access.log"; //file where access will be logged

        /* Optional parametes (keep it to empty or wrong string if you don't want AuhtZ attributes: */

         /*
          * Note: all attribute names MUST be written in lowercase e.g. givenName -> givenname
          */

        /* Optional*/ private $AuthorizativeAttrName="member";  //can be multi-value
        /* Optional*/ private $AuthorizativeJSONAttrName="x-garr-authoritativejsondata";  //single valued JSON String attribute  e.g. {"myappLevel":"admin","yourappLevel":"guest"}

        /*
         * Other configuration options can be set programmatically, check
         * for the setters methods of this class and call it before
         * calling the method authenticate() into a page to protect.
         */
    /* End configuration. */
```


---


**System Requirements**:

  * php5-ldap (there are .deb and .rpm package in standard main linux distro repositories) ( http://packages.debian.org/sid/php5-ldap )

  * If you are using LDAPS:// (And i suggest to use it always, a part when the LDAP is on the same machine of the PHP server) you will need to modify /etc/ldap/ldap.conf to set ldap server  certificate verification or you will get a blank page using LdapAuthPHP.

  * I found this php5-ldap certificate validation a little buggy then if you experience problems try to set **TLS\_REQCERT never** (in /etc/ldap/ldap.conf) for skipping the certificate validation but keeping all the benefits of the SSL encryption.

Project homepage: http://creativeprogramming.it/opensource/item/27-ldapauthphp