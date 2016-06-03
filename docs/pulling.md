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

Stories in the NPR Story API contain lots of metadata. When you pull a story from the API it will store this metadata in WordPress custom fields. This may include audio files, bylines, image captions and credits, and many other values including the story ID:

![NPR story custom fields WordPress post edit screen](/assets/npr-story-custom-fields.png)

You might use this for customizing your WordPress theme to display content in these custom fields.

## Pull By Custom API Query

