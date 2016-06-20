=== NPR Story API ===
Contributors: nprds, David Moore, Jeremy Dunn, kgrantNPR, Kevin Moylan, inn_nerds
Donate link: http://www.npr.org/series/750002/support-public-radio
Tags: npr, news, public radio, api
Requires at least: 3.8.14
Tested up to: 4.5.2
Stable tag: 1.5.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A collection of tools for reusing content from NPR.org supplied by Digital Services.

== Description ==

The NPR Story API Plugin provides push and pull functionality with the NPR API along with a user-friendly administrative interface. 

NPR's API is a content API, which essentially provides a structured way for other computer applications to get NPR stories in a predictable, flexible and powerful way. The content that is available includes audio from most NPR programs dating back to 1995 as well as text, images and other web-only content from NPR and NPR member stations. This archive consists of over 250,000 stories that are grouped into more than 5,000 different aggregations.

Access to the NPR API requires an API Key which you can get by [registering for an NPR account](http://www.npr.org/account/signup).

The WordPress plugin is being developed as an Open Source plugin by NPR Digital Services. If you would like to suggest features or bug fixes, or better yet if you would like to contribute new features or bug fixes please contact Digital Services through our support page at [http://info.ds.npr.org/support.html](http://info.ds.npr.org/support.html).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->NPR API screen to configure the plugin. Begin by entering your API Key, then add your Push URL and Org ID. 


== Frequently Asked Questions ==

= Can anyone get an NPR API Key? =

That's up to NPR but you can [register for an account here](http://www.npr.org/account/signup).

= Can anyone push content into the NPR API using this plugin? =

Push requires an Organization ID in the NPR API, which is typically given out to only NPR stations and approved content providers. If that's you, you probably already have an Organization ID.

= Where can I find NPR's documentation on the NPR API? =

The best resource for general information about the NPR API is here: [https://nprsupport.desk.com/customer/en/portal/topics/783619-api/articles](https://nprsupport.desk.com/customer/en/portal/topics/783619-api/articles).

There's also the documentation in the NPR API site: [http://www.npr.org/api/index.php](http://www.npr.org/api/index.php).

= Is there an easy way to directly query the NPR API? =

You bet, just visit the NPR Query Generator: [http://www.npr.org/api/queryGenerator.php](http://www.npr.org/api/queryGenerator.php)

== Screenshots ==

NPR API Plugin Settings screen

![NPR API Plugin Settings screen](docs/assets/img/npr-api-wp-plugin-settings.png)

NPR API multiple get settings

![NPR API multiple get settings](docs/assets/img/npr-api-multiple-get-settings.png)

Get NPR Stories link in the dashboard

![Get NPR Stories link in the dashboard](docs/assets/img/get-npr-stories-link.png)

Getting an NPR Story by Story ID

![Getting NPR Stories by Story ID](docs/assets/img/get-npr-stories-link.png)

NPR Stories having got gotten

![NPR Stories having got gotten](docs/assets/img/npr-stories.png)


== Changelog ==

= V1.5.2 =

* Adding support for enclosures created as metadata by the PowerPress plugin.
* Added NPR One checkbox that makes pushed stories available to the NPR One app (unchecked by default).
* Users were getting a whitescreen when attempting to push posts. The default byline element -- when used with no mapping or co-authors plugin -- was being sent without a tag name, causing a fatal error.)
* General clean up; small doc tweaks; bug fixes.

= V1.5.1 =

* Multiple Bylines for pulled stories -  When an API story has multiple bylines, we will now populate a meta field `npr_multi_byline` with a pipe (|) deliminated list of names.  If the author also has a link for thier byline then there'll be a tilde (~) separating the author's name and the link.
  - An example of data for multiple bylines:

		Ari Shapiro~http://www-s2.npr.org/people/2101154/ari-shapiro|kmoylan@npr.org|Tommy Writer

  - Here there are three authors for this story, Ari Shapiro (and his bio link), then "kmoylan@npr.org" and a third author "Tommy Writer". Single author stories will not be changed. (This fix came from Ryan Tainter at KERA. Thanks, Ryan!)

* Do not re-pull stories that are already in draft status - The get multi process would repleatedly re-pull stories into draft mode if there is already a draft copy. This fix will check for `status=any` when checking to see if a story already exists in the WordPress database.

= V1.5 =

* Reversed the order of images and audio that are being pushed to the NPR API.  We're now sending them in the order the file was created.
* Mapping of media credit and agency - If you have a field that you are storing image credit and agency, you can now send these to the NPR API. In making this change we needed to expose meta fields that begin with an underscore.  So you may see more meta fields visable on the mapping page.
* Multiple bylines are now being pushed to the NPR API if you use the plugin co-author-plus (<http://wordpress.org/plugins/co-authors-plus/>) We insert a byline row for each of the co-authors of a post when we push. You can still use a custom byline field if you want to use that along with the co-authors plugin.
* Retrieved story dates -  Now when you retrieve a story from the NPR API the published date for WordPress will be the date from the <storyDate> field in the NPR API. This will better allow stories to fall into their natural order when publishing from the Get Multi page. It's always possible to edit the date by hand when pulling single stories from the NPR API. Do remember though that you are contractually obligated to show the original date when rendering a story from the NPR API.
* Retrieved story order -  When you retrieve stories from the API, if you do not include a `sort` parameter in your query, we will insure that the order of the stories is in reverse cron for any stories that have the same storyDate in the API. Often some aggregate groups in the API will publish stories with the same storyDate. Shows like Morning Edition do this often, and prior to this fix stories would appear to be published in reverse order from what the API shows.  Now if you do not include `sort` in your query, the stories with the same time will be published in the same order as the API shows them.
* Configurable Cron interval -  When you use the Get Multi page, which fires off of wp-cron, you can now set the interval for the wp-cron to fire. This is on the Get Multi configuration page, and allows you to enter a number of minutes you want as your wp-cron interval. If you enter any thing that is not a number, or a number less than 1, we will resolve that to 60 minutes...because, well, that's a resonable interval, and the wp-cron doesn't understand 'every now and then' as an interval.
* Images from crops - In earlier versions of the plugin we would pull down the image from the URL in `<image src=>`. Alas, a lot of those images were small, with ?s=12 in the URL, or thumbnails. So now we're bringing down any bigger versions that we can find. We'll look for the following elements under <image> tags. In order `<enlargement>`, `<crop type=enlargement>`, `<crop type=standard>`, and then get `<image src=>` if those other elements do not exist. This should help with getting bigger images. If you need a smaller image, just theme the bigger one.
* When pushing a story to the NPR API, we will now be checking to see if images exist in the content, if so, add the query string parameter `origin=body` to the image url so CorePublisher will like it. This is only something the Core Publisher uses, but it will help greatly for stories published in Wordpress and retrieved into Core Publisher.
* Adding meta data as meta fields to each pulled image.  The following meta fields will be attached to the posts for pulled images:

		npr_image_credit =>$api_image->producer
		npr_image_agency =>$api_image->provider
		npr_image_caption =>$api_image->caption

* When pushing a story with Audio, the audio description will be pushed as well, provided there is one.

= V1.4 =

* Filters for Shortcodes - We've now implemented a hook to a filter that will be used to alter any short codes that a plugin may own in a post before pushing that post to the NPR API. The filter (`npr_ds_shortcode_filter`) will fire before we remove shortcodes when we're pushing the post. Any plugin that has shortcodes should alter those shortcodes via this filter to something that's more HTML-friendly, so that other clients can access that information. As an example of what needs to be done in a plugin to take advantage of this new filter please see the branch for the following plugin: <https://github.com/argoproject/navis-documentcloud/tree/kmoylan-filter> What we've done here is write a function `my_documentCloud_filter` that is linked to the filter `npr_ds_shortcode_filter` (all near the bottom of the PHP file).  This function will turn any shortcode created by this plugin into an HTML `<a>` tag to link to what was an embedded document. As with any other filter in WordPress, nothing will happen if you do not have any plugins installed that have implemented the filter. It will be up to any other plugin's maintainer to implement the filter correctly if they wish to take advantage of this functionality.
* Bulk Push - From the post list page for you NPR Push post type you can now select multiple posts and using the bulk operation dropdown on that page, push the selected posts to the NPR API. This should helpful for posts that have been living on a site before the NPR API plugin was installed. Note that this will only push a maximum 20 posts at one time.   
* Publish or Draft for Get Multi -  It's now possible for an admin to select Draft or Publish for the posts that come from a query on the get multi page. This way, the return from each query can be reviewed before it's published to a site.
* Run Get Multi on Demand -  An admin can now select a checkbox if they would like the get multi queries to run when the page is saved. This will allow admins to immediately check queries instead of having to wait for the cron to run.

= V1.3 =

* Permissions - If you have created any NPR API Permissions Groups you can select which (if any) group you would like to use as your default group when pushing content to the NPR API.  Learn more about NPR API Permissions Groups here: <http://digitalservices.npr.org/post/npr-api-content-permissions-control>
* Multi-Site - Cron set up and other activation tasks will now happen on every site in a multi-site configuration.
* Cron queries - We won't try to query for any of the configured queries on the Get Multi page if the field isn't filled in with data.
* Content and short codes - Instead of blindly removing all shortcodes from a story's content, we are now trying to let any plugins replace the shortcodes with HTML, and then if there are any leftover shortcodes (we delete those?).
* Byline URL - We are now saving any links to the author of a story's bio page in the meta field `npr_byline_link`. This should allow you to supply a link for any author who has a biography registered with the NPR API.

= V1.2 =

* Enhance error messages when there are connectivity or permissions issues with your NPR API work. We have also included the ability for admins to map custom Meta fields to certain Post fields that are pushed to the NPR API. And finally, we've add the ability to retrieve transcripts for stories that have transcripts associated with them. Along with these changes we've added the the ability to save urls for the .m3u files attached to NPR stories.


= V1.1 =

This version will allow admins to configure their WordPress site to retrieve multiple NPR API stories that will be automatically published (via cron) to their site.

* From the Admin -> Settings -> NPR API Get Multi page (wp-admin/options-general.php?page=ds_npr_api_get_multi_settings) an admin can add a number of queries.
* These query entries can contain an API ID for a single story, or an ID for a specific category, program, topic, etc.
* The query can also contain the full query string can be created from the NPR API Query Generator: <http://www.npr.org/api/queryGenerator.php>
* You can also enter the URL for a story you found on npr.org.
* The entered queries will be executed via the Wordpress cron functionality, hourly.
* Any new stories that are available will be automatically published.  You can find a list of query filters at the npr.org's API documentation page: <http://www.npr.org/api/inputReference.php>
* Stories retrieved from the NPR API will be created as Posts in WordPress.  Each post will have a number of meta fields associated with the post.  These meta fields will all begin with `npr_` and can be viewed on a post edit screen with Custom Fields option enabled through Screen Options. A story from the API that has a primary image defined will have that image set as the featured image of the Wordpress post.  Any bylines for the NPR Story will be stored in the meta field `npr_byline`. The list of npr_ meta fields is:

		npr_api_link
    	npr_byline
    	npr_html_link
    	npr_last_modified_date
    	npr_pub_date
    	npr_retrieved_story
    	npr_story_content
    	npr_story_id

* On the All Posts admin screen we've made a couple of modification/additions to help you manage your NPR API content.
  - There is a new Bulk Action available, 'Update NPR Story'.  When one or many NPR API Stories are selected and the Update NPR Story action is applied, this plugin will re-query the NPR API for those stories, and if the story's content has changed it will update the post in WordPress.
  - There is also a new column added to this page titled "Update Story".  For any Post retrieved from the NPR API there will be a link to "Update" the story. Pressing this link will bring the user to the query NPR API page with the story ID pre-filled. Pressing "Publish" on this screen will re-query the NPR API for the story and update the Post with any changes that may have taken place on the NPR API for that story.
* Update and Delete of pushed stories - A story that was written in your Wordpress site and pushed to the NPR API will be automatically be updated in the API when your story is updated in Wordpress.  When your Post is moved to the Trash in Wordpress, your story will be deleted from the NPR API.  If a Trashed Post is resorted, your story will be made available to the NPR API again.

= V1.0 =
As not a lot of users have installed the V1.0 of the NPR API Plugin, there are a couple of things to keep in mind.

* On the NPR API settings page (wp-admin/options-general.php?page=ds_npr_api) there are 4 fields.
  - API KEY - This is your NPR API Key that you can get from NPR.  If you wish to push stories to the NPR API you'll need to have your key configured by NPR Digital Services.  Please contact Digital Services with a support request at <http://info.ds.npr.org//support.html>
  - Pull URL - This is the root url for retrieving stories.  For testing purposes, you should configure this to be `http://api-s1.npr.org`. NOTE: this url should not contain a trailing slash.
  - Push URL - Much like the pull url, this url is used to pushing stories to the NPR API. Again, for testing purposes, you can utilize NPR's staging server  at `http://api-s1.npr.org`. If you do not wish to push your content, or your NPR API has not been authorized, you should leave this field empty and the WordPress plugin will not attempt to push you content to the NPR API.
  - Org ID - This is your organization's ID assigned by NPR.  If you don't know your Org ID, please contact Digital Services at: <http://info.ds.npr.org/support.html>
* You can pull stories one at a time from the NPR API by using the Get NPR Stories page under admin Posts menu (wp-admin/edit.php?page=get-npr-stories). This can be story ID from the API, or the URL for the story from npr.org. For help in finding possible query options, please use the Query Generator at <http://www.npr.org/api/queryGenerator.php> Documentation is at: <http://www.npr.org/api/inputReference.php>

== Upgrade Notice ==

= 1.5.2 =

This version adds export functionality for the NPR One mobile app, in addition to assorted bug fixes. 
