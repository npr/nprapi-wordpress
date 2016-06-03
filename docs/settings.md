# Plugin Settings

## Before your start

To pull content from the NPR API you'll need an API Key which you can get by [registering for an NPR account](http://www.npr.org/account/signup). Once your account is approved by NPR you can [sign in](http://www.npr.org/account/login) and copy your NPR API key from the developer section at the bottom of the page:

![NPR account page showing the API key in the developer section of the page](/assets/npr-sccount-page.png)

If you are planning to push content to the NPR API you'll also need an NPR API Org ID. If you are an NPR station or affiliated producer you can find your ORG ID at [NPR StationConnect](https://stationconnect.org/login?redirect=%2F). 

If you don't have an Org ID or don't intend to push content into the NPR API you can still pull content from the API, but be mindful of the [Terms of Use](http://www.npr.org/about-npr/179876898/terms-of-use).

## Configure Your Settings

With your API Key in hand, visit the **Settings > NPR API** settings screen in your WordPress dashboard. Enter your API Key, and if available your Org ID. 

For the producation API, the value for both Pull URL and Push URL is `http://api.npr.org`. If you wish to use the sandbox version of the API for testing, the URL for both Push and Pull is `https://api-s1.npr.org`.

For NPR Pull Post Type you can leave the default or pick a custom post type. You can pull stories into the custom post type to keep them organized separately from your site's regular posts. For example if you select an NPR Pull Post Type of `npr_story_post` you will find all pulled stories in a new dashboard link labelled **NPR Stories**, just below the dashboard link for **Posts**.

For NPR Push Post Type you can leave the default or set it to another post type, like `post`. 

![NPR API plugin settings page with values filled in as described above](/assets/npr-api-wp-plugin-settings.png)