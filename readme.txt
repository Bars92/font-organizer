=== Font Organizer ===
Contributors: basaar,yardensade,hivewebstudios
Tags: fonts,google fonts,upload font,font,google,custom, free, download, style
Requires at least: 3.8
Tested up to: 4.7.5
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Font Organizer is the complete solution for font implementation including uploading custom fonts and using google fonts in WordPress websites.

== Description ==

**Note: WordPress 4.7.X has an [open issue](https://core.trac.wordpress.org/ticket/40175 "Offical open bug link") that fail some font format uploads.
To fix this issue you may download [this plugin](https://wordpress.org/plugins/disable-real-mime-check/ "Plugin fix for non-image uploads") until further notice.**

Font Organizer is the only complete & free solution for font implementation, including uploading custom fonts and using google fonts in WordPress websites.
No technical knowledge, no hassle, simply click and use.

Our plugin is intended for any user in order to easily add native, google and custom fonts - then assign
them to different elements in your website effortlessly.
The plugin is totally free, no "pro" features, all of the functions are totally available to you free of charge.

Want to find out more? go to [our website](https://hivewebstudios.com/ "HiveWebStudios website") and find out more from us.

Want to try us? Use Addendio's [Font Organizer live demo](https://addendio.com/try-plugin/?slug=font-organizer "live demo")!

Have any problems? Check out our new [Official Font Organizer page](http://hivewebstudios.com/font-organizer/ "Font Organizer page").

Key Features:

* Upload fonts to your website and apply it in few clicks.
* Multi upload allow you to upload more font formats to support more browsers at ease, we now support all font formats, which means full browser support!
* Add any google font in one click and apply them instantly.
* Apply fonts for generic elements or custom elements in your website easily.
* Select font weight for every font you use with known & custom elements.
* Edit and remove custom elements assigned quickly.
* Delete and remove fonts from your website in one click.
* Choose your fonts & font sizes from within the text editor (tinyMCE & tinyMCE Advanced).
* Our code is extremely clean, well written, commented, and optimized for maximum performance for your website.
* Full support for language translation, including RTL languages.
* Custom CSS for developers to quickly test and deploy changes to your fonts.
* It's awesome like its users.

Coming up soon: Tutorial video & DIVI support! Stay tuned.

== Installation ==

1. Just download & install the plugin from your wordpress site. Or Download the plugin from this page.
2. Upload the plugin files to the /wp-content/plugins/font-organizer directory of your WordPress website using FTP or the File Manager of your control panel via your host.
3. Activate the plugin through the "Plugins" screen in your WordPress admin dashboard.
4. Go to "Settings" -> "Font Settings" in order to configure the plugin.
5. Simply follow the steps with their explanations.

== Frequently Asked Questions ==

= When trying to upload a font I get the following error: "Error uploading the file: Sorry, this file type is not permitted for security reasons."? =

There is an open bug since WordPress 4.7.1 (it is not plugin related)
([https://core.trac.wordpress.org/ticket/39550](https://core.trac.wordpress.org/ticket/39550 "open ticket")) that blocks upload for
some non-image files. This bug should have been fixed in version 4.7.3, but it was not fully solved yet. Until the issue is fixed, you may use this plugin: [https://wordpress.org/plugins/disable-real-mime-check/](https://wordpress.org/plugins/disable-real-mime-check/ "open ticket")
Installing this plugin work around the bug and until further notice there is nothing else to be done.

= How do I create API key for the plugin? =

Browse to [Google Developer API](https://developers.google.com/fonts/docs/developer_api#identifying_your_application_to_google "google developer API").
Click on "GET A KEY". -> Select "Create a project".
Give the project a name, any name that makes sense to you is ok, then press OK.
Click on "Create credentials" > "API key".
There you go, google generated a brand new API key for you.
Copy the entire key and paste it on the designated place in the plugin settings.

In order to enable the API key go to API Manager dashboard or use the link: [https://console.developers.google.com/apis/dashboard](https://console.developers.google.com/apis/dashboard "google developer API console").
Press "ENABLE API", Then Choose "Web Fonts Developer API" under "Other popular APIs" section. **This step is a must**.

Congrats, your API key is now enabled. you may use it inside the plugin to get the full current font list.

= What the option "Show Font Family Preview" means? =

When selecting fonts in section 1, you will see a lot of different fonts, ticking this option will let you to actually
see example of the font you are about to use.
Due to the need of loading each font example from google, this option will slow down the performance of the plugin page.

= What the option "Access Settings Role" means? =

We wanted to give you the option to decide which roles in your wordpress website are allowed to use the plugin, so you may or may not allow other users with access to the website the ability to use the plugin.

= Why can't I see assigned fonts even after I have done everything correctly? =

Sometimes some browsers save a cached version of the page when browsing it in orderto display the pages faster.
If you can't see a new font you have assigned, make sure to "hard clean" the cache using Shift+F5 to clean the current page, or Ctrl+Shift+Delete to clean all. (Some websites might use cache plugin that must be refreshed before)

Sometimes it is a matter of font format compatibility with different browsers.
At the moment we are working on automatic font format conversion but until then you may use a service such as [Font Squirrel](https://www.fontsquirrel.com/tools/webfont-generator "font squirrel") or similar font conversion service online.

= I have some element in the website - and the font would not change for it, what do I do? =

In some cases some elements are assigned with fonts in a very specific ways via other plugins or themes, and thus you will need to target those elements using CSS id or class, in section 4 of the plugin "Custom Elements Settings".
If you have absolutley no knowledge of what is CSS, you might need help of a webmaster.

= I have bought a font and it came with many font weights, what is the best way to upload all of them using the plugin? =

Each upload process at the moment is used for 1 font weight only.
Uploading more then 1 font weight is done by doing the upload process several times, once for each font weight.
When uploading the font weight you should write its name (only!) in the font name, then choose in the "Font Weight" select menu its weight.

Example: Both "Arial Bold" or "Arial Italic" would get the "Font Name" of "Arial", so you could easily use them later in the plugin.

== Screenshots ==

1. Choosing Google & uploading fonts in just 1 step.
2. Assigning the fonts you have chosen to various elements in your website.
3. Assigning the fonts you have chosen to your own elements in your website & managing all your fonts.
4. General settings & role restictions.
5. Choosing added fonts in any post and page in one click.

== Changelog ==

= 2.1.1 =
* Fixed plugin issue causing admin to break for some users.
* Reduced plugin usage consumption when not needed.

= 2.1.0 =
* Added Black & Black italic to font weights.
* Added support for upload svg and eot font formats.
* Added new advanced section.
* Moved general settings to advanced tab.
* Added custom css area to advanced tab.
* Added uninstall option to remove everything from font organizer (database, fonts and files).
* Fixed plugin misbehave in multisite.
* Fixed plugin fail to load fonts on custom media folder.
* Changed some UI to better guide new users.

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