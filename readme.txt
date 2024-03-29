=== oik-unloader ===
Contributors: bobbingwide, vsgloik
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags: oik, plugin, unloader
Requires at least: 5.8.1
Tested up to: 6.4-RC2
Stable tag: 0.3.1

WordPress Must Use plugin to unload unnecessary plugins on demand.

== Description ==
Use the oik-unloader plugin to unload unnecessary plugins on demand.

Many websites have lots of plugins activated because the functionality is sometimes needed.
But quite a few of these plugins:
- think they're needed all the time,
- do a lot of processing for each incoming request,
- only to find that there was no need for the work that they'd done.

These slow the server side processing of the site. 
This can increase the server response time; time to first byte.
The plugins can also slow the front end of the site; delivering scripts, CSS and even HTML and images that are not actually needed.

Dynamically unloading a subset of plugins for certain requests could improve the overall performance of the site.
This could help improve the Core Web Vitals which may lead to better rankings in Search Engine Results Pages ( SERPs ). 

== Installation ==
1. Upload the contents of the oik-unloader plugin to the `/wp-content/plugins/oik-unloader' directory
1. Activate the oik-unloader plugin through the 'Plugins' menu in WordPress
1. Visit the oik-unloader admin page
1. Use the admin interface to select which plugins to deactivate for selected URLs
1. Click on the link to activate/update the Must Use ( MU ) plugin
1. Disable the MU logic using the Deactivate link

Note: In a WordPress Multi Site installation
- There will only be one version of the Must Use plugin ( oik-unloader-mu.php )
- There will be multiple unloader files; one per site.


== Frequently Asked Questions ==

= What is this plugin for? =
Performance tweaking.

- It helps to reduce the number of activated plugins for a particular request.
- It dynamically removes the unwanted plugins from the list of plugins to be activated.
- The plugin temporarily deactivates plugins for the specific URL.
- It doesn't unload them, it prevents the plugins from being loaded.

= Which plugins can I deactivate? =

For certain URLs you should be able to deactivate a whole host of plugins that aren't actually required for the front end. 
You may find that some plugins are extremely well behaved and it won't make the slightest difference if they're left activated.

= Why deactivate rather than activate? =

By targetting specific URLs you can deactivate plugins that you're certain are not needed.
For example, you may have a number of posts that display charts. 
For these you will need to ensure that the chart plugin is activated.
For any other post, the plugin that displays the charts doesn't need to be activated.
If it improves the performance, then you may consider deactivating the chart plugin for these other posts.
The tweaking is intended to get the best performance for specific URLs.
Concentrate on the most visited pages first and deactivate only those plugins that you know you can safely deactivate.  


It's primarily the front end experience that we want to improve.

When using the WordPress admin interface you'll expect each of the plugins to operate correctly.
Therefore you would expect the plugins to be activated.
The REST API and AJAX requests should also produce the expected results.

== Screenshots ==
1. oik-loader admin page - plugins
2. oik-loader admin page - oik-loader-mu not activated
3. oik-loader admin page - oik-loader-mu activated

== Upgrade Notice ==
= 0.3.1 = 
Upgrade for support for PHP 8.1 and PHP 8.2

= 0.3.0 = 
Upgrade for an improved active-plugins block and some bug fixes.

= 0.2.0 = 
Upgrade for the active-plugins block to load/unload plugins from the front-end.

= 0.1.1 =
Upgrade to avoid problems that may arise at shutdown.

= 0.1.0 = 
Upgrade for WordPress Multi Site support.

= 0.0.1 =
Upgrade plugin selection using checkboxes.

= 0.0.0 =
Prototype version developed as part of a performance improvement project.

== Changelog ==
= 0.3.1 = 
* Changed: Support PHP 8.1 and PHP 8.2 #14
* Tested: With WordPress 6.4-RC2 
* Tested: With PHP 8.1 and PHP 8.2
* Tested: With PHPUnit 9.6

= 0.3.0 =
* Fixed: Cater for post query arg array #13
* Fixed: Require bobbforms before calling bw_form() #12
* Changed: Reconcile shared library update
* Fixed: Avoid warning when query parms are an array #11
* Changed: Only display checkboxes for authorized users #7
* Tested: With WordPress 6.2.2 
* Tested: With PHP 8.0

= 0.2.0 = 
* Added: oik-unloader/active-plugins block #7
* Added: [active_plugins] shortcode #7
* Changed: Improve oik-unloader admin form #1
* Changed: Cater for when oik-loader is not available #7
* Changed: Prevent NextGEN Gallery from activating itself #6
* Tested: With WordPress 5.9.2
* Tested: With PHP 8.0
* Tested: With/without oik-loader v1.4.0

= 0.1.1 =
* Fixed: Deactivates unloading logic at shutdown. Fixes #6 
* Tested: With WordPress 5.9

= 0.1.0 = 
* Changed: Support deactivation of Network Activated plugins in WordPress Multi Site #4

= 0.0.1 =
* Changed: Plugin selection list implemented using checkboxes
* Changed: Create mu-plugins folder if required
* Changed: Respect WPMU_PLUGIN_DIR value locating mu-plugins folder

= 0.0.0 =
* Added: Brand new plugin. includes/oik-unloader-mu.php will only be installed in mu-plugins if the folder exists.
* Tested: With WordPress 5.8.1
* Tested: With Gutenberg 11.7.0
* Tested: With PHP 8.0

== Further reading ==
See also [oik-loader](https://github.com/bobbingwide/oik-loader) which adds plugins to the list of plugins to activate.

If you want to read more about oik plugins and themes then please visit
[oik-plugins](https://www.oik-plugins.com/)