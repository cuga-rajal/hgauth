HGAuth

An Opensim authentication module that can enforce a Web Form submission before allowing 
inbound HG teleport

Version 1.0, August 25, 2022

-----
**Summary**

This is a re-write of Project Sasha which has not been developed since 2018.

It is a set of PHP scripts that provide a way to enforce inbound HG teleporting
avatars (from other grids) to agree to terms presented on a web page, before they
are allowed to enter. 

Although I am not aware of any issues, please use it at your own risk. 

-----
**How it works**

Avatars attempting to HG teleport to a grid with this package installed, will
receive a rejection dialog in the Viewer with a customizable message and an
external link. Clicking that link will take them to an external web page with an
on-page message and a form. The web form pre-fills their avatar address so they
can not enter it manually. They are asked to confirm or reject the agreement.
Clicking the Confirm/Yes button on the form will authorize them for future
inbound HG teleports.

The package prevents the avatar name from being altered and prevents submitting
avatar names other than the one actually used in the viewer.

The verbiage on the web page can be changed or adapted to suit your needs. 
Project Sasha was originally developed to enforce legal requirements of GDPR for
residents of the EU. However the form can be used to enforce TOS or other needs.

-----
**Changes from Project Sasha**

- Eliminated transmitting the username and UUID in the clear in the URL namespace
- Eliminated vulnerability of the form processor that could result in database misuse
- Properly handle form submission through jQuery and AJAX
- Fix PHP code not compatible with PHP 8.x 
- Consolidation of PHP templates to simplify UI changes
- Added developer information, to report bugs and feature requests

-----
**How to install**

The file hgauth.sql will give you the table structure you need
import it into your mysql server, into the database you choose.

The file authconfig.php contains database credentials and configurations for the
other scripts. It is used only as an include file so it could be placed outside
the document root.

The file hgauth.php is expected to be used to receive HG teleport authorization
requests from an Opensim Authorization Service Connector via HTTP, and to send
responses back to that service. This file also includes a message that appears
in the viewer's dialog box when the initial inbound teleport request is
rejected. You may wish to update the message.

The file index.php is expected to be used to present an authorization form on a
web browser to a user who has clicked the link in the viewer's HG TP rejection
dialog. You may wish to change the on-screen message to suit your needs.

After the files are configured and placed in appropriate web server directories,
you need to make some changes to your Opensim configuration files for these to
take effect.

in file:
config-include/GridCommon.ini (Grid Mode)  
or  
config-include/StandaloneCommon.ini (Standalone Mode)  

1) Add the following in the [Modules] section:

   AuthorizationServices = "RemoteAuthorizationServicesConnector"

2) Add the following in the [AuthorizationService] section:
	
   AuthorizationServerURI = "http://yourwebserver/path/to/hgauth.php"


For security it is recommended to restrict web access to these files. A sample
htaccess.txt file for Apache is included (apache 2.4 syntax). This should be
renamed .htaccess and placed in the directory containing the PHP scripts. Apache
may need to be configured to read the .htaccess file.


- authconfig.php - should not be accessed directly, it is meant to be an include file only
- hgauth.php - access should be restricted to the IP of the Opensim server's inbound HTTP connection
- index.php - unrestricted web page
 
Though not critical, make sure your date.timezone is set in your php.ini.
Failure to do so may result in database records containing a mix of local and
GMT times. Creation time is written by the internal clock of mySQL while
confirmation time is written by PHP.

-----
**Requirements**

- Webserver, tested on Apache 2.4.x
- PHP, tested and developed on PHP 8.x
- mySQL server, should work on all flavors of mySQL >= 7.x or MariaDB >= 10.x.

-----
**Credits**

Portions of the code are taken from Project Sasha. The following people (avatars)
are credited from Project Sasha: Foto50, Hack13, FreakyTech and Leighton Marjoram.

-----

This is a work in progress. Please notify me of bugs or feature requests.

Cuga Rajal (Second Life and OSGrid)

cuga@rajal.org