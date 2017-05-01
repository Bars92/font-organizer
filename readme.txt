=== Font Organizer ===
Contributors: hivewebstudios,basaar,yardensade
Tags: fonts,google fonts,upload font,font,google
Requires at least: 3.8
Tested up to: 4.7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Font Organizer is the complete solution for font implementation including uploading custom fonts and using google fonts in WordPress websites.

== Description ==

**Note: WordPress 4.7 has an [open issue](https://core.trac.wordpress.org/ticket/40175 "Offical open bug link") that fail otf font format uploads.
To fix this issue you may download [this plugin](https://wordpress.org/plugins/disable-real-mime-check/ "Plugin fix for non-image uploads") until further notice.**

Font Organizer is the complete solution for font implementation including uploading custom fonts and using google fonts in WordPress websites.

The plugin is intended for both developers and regular users in order to easily add native, google and custom fonts then assign
them to different elements in your website effortlessly.

Want to find out more? go to [our website](https://hivewebstudios.com/ "HiveWebStudios website") and find out more from us.

Want to try us? Use Addendio's [Font Organizer live demo](https://addendio.com/try-plugin/?slug=font-organizer "live demo")!

Have any problems? Check out our new [Official Font Organizer page](http://hivewebstudios.com/font-organizer/ "Font Organizer page").

Key Features:

* Upload fonts to your website and apply it on any element.
* Multi upload allow you to upload more font formats to support more browsers.
* Add any google fonts in one click and apply them on any element.
* Apply fonts for known elements or custom elements of your choosing easily.
* Select font weight for every font you use with known & custom elements.
* Edit and remove custom elements assigned quickly.
* Delete and remove fonts from your website in one click.
* Choose your fonts & font sizes in the editor (tinyMCE & tinyMCE Advanced).
* Our code is extremely clean, well written, commented, and optimized for maximum performance for your website.
* Full support for language translation, including RTL languages.
* It's awesome like its users.

Coming up soon: ! Stay tuned.

== Installation ==

Just download & install the plugin from your wordpress site. Or

1. Download the plugin from here.

2. Upload the plugin files to the `/wp-content/plugins/font-organizer` directory.

3. Activate the plugin through the 'Plugins' screen in your WordPress site.

4. Use the Settings->Font Settings screen to configure the plugin and follow the steps.

== Frequently Asked Questions ==

Q: What is API key, and why do I need it?

A: API key is a special key made by google in order to let users view their full list of fonts and use them in the plugin.
Without it the entire function of google fonts will not work.

Q: How do I create API key for the plugin?

A: Inside the plugin click on "HERE" at Settings->Font Settings->General Settings->Google API Key.
Open the Credentials page link. Select "Create a project".
Give the project a name, any name that makes sense to you is ok, then press OK.
Click on "Create credentials" > "API key".
There you go, google generated a brand new API key for you.
Copy the entire key and paste it on the designated place in the plugin settings.

In order to enable the API key go to API Manager dashboard or use the link: "https://console.developers.google.com/apis/dashboard"
Press "ENABLE API", Then Choose "Web Fonts Developer API" under "Other popular APIs" section. - This step is a must.

Congrats, your API key is now enabled.

Q: What the option "Show Font Family Preview" means?

A: When selecting fonts in section 1, you will see a lot of different fonts, ticking this option will let you to actually
see example of the font you are about to use.
Due to the need of loading each font example from google, this option will slow down the performance of the plugin page.

Q: What the option "Access Settings Role" means?

A: We wanted to give you the option to decide which roles in your wordpress website are allowed to use the plugin, so you may or may not allow other users with access to the website the ability to use the plugin.

Q: Why can't I see assigned fonts even after I have done everything correctly?

A: Sometimes some browsers save a cached version of the page when browsing it to display it faster.
If you can't see a new font you have assigned, make sure to clean the cache using Shift+F5 to clean the current page, or Ctrl+Shift+Delete to clean all. (Some websites might use cache plugin that must be refreshed before)

Q: I have some element in the website - and the font would not change for it, what do I do?

A: In some cases some elements are assigned with fonts in a very specific ways via other plugins or themes, and thus you will need to target those elements using CSS id or class, in section 4 of the plugin "Custom Elements Settings".
If you have absolutley no knowledge of what is CSS, you might need help of a webmaster.

Q: I have bought a font and it came with many font weights, what is the best way to upload all of them using the plugin?

A: Each upload process at the moment is used for 1 font weight only.
Uploading more then 1 font weight is done by doing the upload process several times, once for each font weight.
When uploading the font weight you should write its weight in the font name.
Example: "Arial Bold" or "Arial Italic", so you could easily use them later in the plugin.

== Screenshots ==

1. Choosing Google & uploading fonts in just 1 step.
2. Assigning the fonts you have chosen to various elements in your website.
3. Assigning the fonts you have chosen to your own elements in your website & managing all your fonts.
4. General settings & role restictions.
5. Choosing added fonts in any post and page in one click.

== Changelog ==

= 2.1.0 =
* Added Black & Black italic to font weights.
* Added new advanced section.
* Moved general settings to advanced tab.
* Added custom css area to advanced tab.
* Added uninstall option to remove everything from font organizer (database, fonts and files).
* Fixed plugin misbehave in multisite.

= 2.0.1 =
* Fixed new installs errors.
* Changed some text to be clearer.
* Fixed minor bugs and UI issues.

= 2.0.0 =
* Added font weight system to known elements, custom elements and custom fonts.
* Added font preview for avaiable fonts in side menu.
* Fixed otf font format did not work in all supported browsers.
* Fixed font family and style is not shown in editor.
* Fixed upload sometimes displays a PHP warning.
* Fixed Upload fails with "Sorry, for security reasons this type of file is not allowed.".
* Fixed delete font permission error.
* Fixed early access fonts not loading in https.
* Fixed multi upload error.
* Fixed absolute urls cause errors when changing site url.

= 1.3.2 =
* Added static google fonts list when not using an API.
* Fixed PHP 5.2 not supported.
* Fixed google API errors.
* Fixed multiple uploads bug.
* Fixed few minor bugs.

= 1.3.1 =
* Added a warning when trying to upload the same font file format in one upload.
* Fixed PHP 5.3 not supported.

= 1.3.0 =
* Added quick editing in custom elements table on section 5.
* Added better error message when Google API key not working.
* Added facebook page like box in the settings.
* Fixed HTTPS website could not load the css.
* Fixed timeout issues with google fonts request.
* Fixed tinyMCE and tinyMCE advanced font families preview not working sometimes.
* Fixed a few minor bugs.

= 1.2.1 =
* Fixed elements rules sometimes not loading.
* Fixed back to top button not appearing.
* Fixed warnings when not setting Google API key.
* Added Google API key indicator.

= 1.2.0 =
* Added fonts in your website and font sizes selection in the editor (tinyMCE & tinyMCE Advanced).
* Added option to delete of fonts in your website in section 5.
* Added validation in required fields.
* Added link to FAQs in Google API key field.
* Added support Wordpress 4.7 version.
* Fixed PHP 7 requirment.
* Added more FAQs.
* Fixed a few minor bugs.
* Better usability and general guiding.

= 1.1.0 =
* Added Multi-upload to upload more formats per font to support more browsers.
* Added back to top button.
* Added a few early access google fonts.
* Fixed a few minor bugs.

= 1.0.0 =
* Plugin publish initial release.

== Upgrade Notice ==