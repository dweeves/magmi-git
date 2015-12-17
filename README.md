magmi-git 0.7.23
=========

Magmi GitHub

This is the Official GitHub home for magmi  project (Magento Mass Importer), the original Git is hosted on sourceforge but this github repo will
be kept in sync.


Magmi Wiki is still hosted at sourceforge.

Magento CE 1.8.x  & 1.9.x Support
===================================

Magmi needs you to test it against Magento CE 1.8.x. & Magento CE 1.9.x

It should work for most of it, and maybe it's already working well.

But devil is in the details and i don't have much time doing an extensive test session.

So , if you are using Magento CE 1.8.x or CE 1.9.x , please provide bug reports for any found defects or any incompatibility you might have noticed.

Authentication
==================

Following previous issues with the mis-use of Magmi in an insecure way, Magmi now contains built-in authentication.

Once you have provided DB details and Magmi can connect to the DB, you will need to login using a set of Magento admin credentials to use Magmi. If Magmi has not yet been configured to connect, then the username and password are both 'magmi'

Authentication with PHP-CGI/FPM
-------------------------------

php-cgi/fpm under Apache does not pass HTTP Basic user/pass to PHP by default
     
Add these lines to an .htaccess file:

     RewriteEngine On
     RewriteCond %{HTTP:Authorization} ^(.+)$
     RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
