# Pulling Stories from the NPR API

Once you have the [WP NPR API Plugin configured](/docs/settings.md), you have several ways of pulling content from the API.

## Pull By Individual Story

In the WordPress Dashboard under **Posts** you will now see a new link for **Get DS NPR Stories**:

![Get DS NPR Stories link in the WordPress Dashboard](/assets/get-npr-stories-link.png)

This opens a screen with a single field where you can paste a Story ID:

![Story ID in the field to pull a story](/assets/get-npr-stories.png)

Alternatively, you can copy the full URL of a story from the NPR website:

![Story on NPR showing the URL being copied](/assets/npr-story.png)

Then paste the URL into this field:

![Story URL in the field to pull a story](/assets/get-npr-story-by-url.png)

You then have the choice to create a draft post of the story, or publish the story immediately. Either way you can edit the story like any other WordPress post:

![NPR story post in the WordPress post edit screen](/assets/npr-story-draft.png)

## NPR Story Custom Fields

Stories in the NPR Story API contain lots of metadata. When you pull a story from the API, WordPress will store this metadata in custom fields. This may include audio files, bylines, image captions and credits, and many other values including the story ID:

![NPR story custom fields WordPress post edit screen](/assets/npr-story-custom-fields.png)

You might use this for customizing your WordPress theme to display content in these custom fields.

## Pull Multiple Stories By Custom API Query

In the WordPress Dashboard go to **Settings > NPR API Get Multi**. This screen offers several "Query String" fields where you can enter a query string for the NPR API. You can enter multiple queries to pull content for different subjects, programs, etc., and set how often the queries will run. Once you save one or more queries, WordPress will continue to run them against the NPR API and return fresh stories as either WordPress Posts or Drafts.

### How to Create a Query String

You can easily create query strings for API content by visiting the [NPR API Query Generator](http://www.npr.org/api/queryGenerator.php). The Query Generator provides a graphical user interface to filter queries by topic, blogs, program, series, stations, and other values in the NPR API. Note that the **Control** tab of the Query Generator adds filtering by date or date range, search terms, and content type. You can also specify the number of results to return for a given query. 

After creating a query in the Query Generator, click the button to **Create API Call**:

![Creating a query in the NPR API Query Generator](/assets/npr-api-query-generator.png)

After you click the button to create your API call, you'll find the full URL of the query in the **Generated API Call** window:

![a query URL in the NPR API Query Generator](/assets/npr-api-query-url.png)

Now copy the API call string up to the last segment `&apiKey=demo`. Do not include this in copying the query string. _(That segment would only be useful if you were going to run the query in the Query Generator itself, which we're not doing here.)_

Now that you've created and copied a query string, return to your **WordPress Dashboard > Settings > NPR API Git Multi** and paste the string into one of the Query String fields. Use the dropdown menu to set whether stories returned from the API should be saved as Drafts or Posts:

![a query URL entered in the Query String field in WordPress](/assets/npr-api-multiple-get-settings.png)

When you click **Save Changes**, WordPress will begin pulling NPR API content. Note that it may take up to an hour before stories begin showing up as Posts or Drafts.

You can add more queries any time. If you run out of Query String fields, just increase the **Number of things to get** setting to add more fields.
