// main jQuery functions for toggle options, forms etc
jQuery(function(){
	
	jQuery('#select_all').change(function() {
	    var checkboxes = jQuery("#options_management").find(':checkbox');
	    if(jQuery(this).is(':checked')) {
	        checkboxes.attr('checked', 'checked');
	    } else {
	        checkboxes.removeAttr('checked');
	    }
	});
	jQuery('.submenu').change(function() {					
	    var parent_item = jQuery(this).parents('.menu_group').find('.parentmenu:checkbox');
	    var siblings = jQuery(this).parents('.menu_group').find(':checkbox:checked').not('.parentmenu:checkbox');
	    if(jQuery(this).is(':checked')) {
	        parent_item.attr('checked', 'checked');
	    } else {
	    	if(siblings.length == 0) {
	    		parent_item.removeAttr('checked');
	    	}				        
	    }
	});
	jQuery('.parentmenu').change(function() {				    
	    var siblings = jQuery(this).parents('.menu_group').find(':checkbox:checked').not('.parentmenu:checkbox');			    
	    if(jQuery(this).is(':checked')) {
	        if(siblings.length == 0) {	        	
	        	var nnn = jQuery(this).parents('.menu_group').find(":checkbox").not('.parentmenu:checkbox').first().attr('checked', 'checked');
	        	console.log(nnn);
	        }
	    } else {
	    	if(siblings.length != 0) {
	    		removemsg();
	    		var removemsg = '<div class="removemsg" id="removemsg">please select all sub menu pages before removing the main menu item.</div>';
	    		jQuery(this).parents('.menu_group').append(removemsg);
	    		jQuery(this).attr('checked', 'checked');
	    		setTimeout("removemsg()", 3000);		    		
	    	}				        
	    }
	});
	
	jQuery("#options_management input[type=checkbox]").each(function(){
		jQuery(this).add(this.nextSibling).wrapAll('<label/>');
	});
	
});

function removemsg(){
		jQuery("#removemsg").remove();	
	}