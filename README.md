magmi-git 0.7.23
===

This is the official GitHub home for the Magmi project: the original "Magento Mass Importer".

The primary source repository is now here ,the  [SourceForge repo](https://sourceforge.net/projects/magmi/) is now secondary and will be kept in sync.

The [official Magmi Wiki](http://wiki.magmi.org/) is still hosted at SourceForge.

### Magento CE 1.8 and 1.9 Support

The Magmi project needs your help!

While Magmi is being used on Magento Community Edition 1.8 and 1.9 installations with very little or no issues, additional testing is required to make sure it's stable and ready for production use. Developers using Magento CE 1.8.x or CE 1.9.x are encouraged to provide bug reports for any issues or incompatibilities that have been discovered.

### Authentication

Magmi now features shared Magento authentication out of the box.

Upon installing Magmi and visiting the web panel for the first time, the default username and password are both set to "magmi". Once successfully logged in, configure Magmi with the Magento database credentials (under Configure Global Parameters) and then save the settings. Afterwards, one can simply use their Magento administrative (backend) credentials to login to Magmi.

#### Apache and PHP-CGI/FPM Auth Issues

By default, PHP-CGI/FPM under Apache does not pass HTTP authentication credentials to PHP processes for authorization. If one is unable to login to Magmi, a few minor changes to Apache's configuration may be required.

First, create a `.htaccess` file inside the `magmi/web` folder and then add the following lines:

     RewriteEngine On
     RewriteCond %{HTTP:Authorization} ^(.+)$
     RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

A sample .htaccess file has been provided under the same folder. Simply copy `.htaccess-sample-php_cgi_fpm` to `.htaccess`.

Additionally, the following line might be needed in your Apache VirtualHost configuration (or .htccess) if using Apache's mod_proxy_fcgi:

     SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
