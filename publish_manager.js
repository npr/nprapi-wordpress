
jQuery(document).ready(function($) {

	$( ".blacklist_story" ).click(function() {
		var idtoblock = $(this).attr('data-storyid');
		var postObject = new Object();
			postObject.action = "blacklist";
			postObject.storyId = idtoblock;

		jQuery.post(ajax_object.ajax_url, postObject, function(response) {
			/* console.log(response); */
		});

	});
	$( ".whitelist_Btn" ).on( "click", function() {
		var idtoRemove = $("#nprStoryId").val();
		var postObject = new Object();
		postObject.action = "whitelist";
		postObject.storyId = idtoRemove;
		jQuery.post(ajax_object.ajax_url, postObject, function(response) {
			if (response>0){
				$( "<div class='updated ajax-notifcation' style='display:inline-block;'>" + idtoRemove + " has been removed from the blacklist.  <button class='closeAjax'>X</button></div>" ).appendTo( "#whitelistUI" );
			}
			else {
				$( "<div class='error ajax-notifcation' style='display:inline-block;'>" + idtoRemove + " was not found. <button class='closeAjax'>X</button></div>" ).appendTo( "#whitelistUI" );
			}
		/* add listener for notification close button  */
			$( ".closeAjax" ).on( "click", function() {
				$(this).parent().remove();
			});
		});
	});
	
});