(function($){
	"use strict";
	
	// create the alert message
	$.fn.gdlr_confirm = function(options){
	
        var settings = $.extend({
			text: 'Are you sure you want to do this ???',
			success:  function(){}
        }, options);
		
		$(this).each(function(){

			var confirm_button = $('<span class="gdlr-button confirm-yes">Yes</span>');
			var decline_button = $('<span class="gdlr-button confirm-no">No</span>');
		
			var confirm_box = $('<div class="gdlr-confirm-wrapper"></div>');
			
			confirm_box.append('<span class="head">' + settings.text + '</span>');			
			confirm_box.append(confirm_button);
			confirm_box.append(decline_button);

			$(this).append(confirm_box);
			
			// center the alert box position
			confirm_box.css({
				'margin-left': -(confirm_box.outerWidth() / 2),
				'margin-top': -(confirm_box.outerHeight() / 2)
			});
					
			// animate the alert box
			confirm_box.animate({opacity:1});
			
			confirm_button.click(function(){
				if(typeof(settings.success) == 'function'){ 
					settings.success();
				}
				confirm_box.fadeOut(function(){
					$(this).remove();
				});
			});
			decline_button.click(function(){
				confirm_box.fadeOut(function(){
					$(this).remove();
				});
			});
			
		});
	};	
	
	$('document').ready(function(){
		
		/* datepicker */
		$('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true
		});

		/* description box */
		$('.transaction-open-detail').click(function(){
			$(this).siblings('.transaction-description-wrapper').fadeIn(200);
			return false;
		});
		$('.transaction-description-wrapper .close-transaction-description').click(function(){
			$(this).parents('.transaction-description-wrapper').fadeOut(200);
			return false;
		});
		
		/* bulk select */
		$('#bulk-select').change(function(){
			$('form.gdlr-transaction-table').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
		});
		$('.transaction-bulk-read').click(function(){
			$(this).closest('form').children('input[name="transaction-type"]').val('read');
			$(this).closest('form').submit();
		});
		$('.transaction-bulk-unread').click(function(){
			$(this).closest('form').children('input[name="transaction-type"]').val('unread');
			$(this).closest('form').submit();
		});
		$('.transaction-bulk-cancel').click(function(){
			var cancel_btn = $(this);
			
			$('body').gdlr_confirm({
				text: 'You\'re cancelling transaction. Please note that, you need to manually refund your customers since this system does not support refunding and this option cannot be undone. Are you sure you want to do this ?',
				success: function(){
					cancel_btn.closest('form').children('input[name="transaction-type"]').val('cancel');
					cancel_btn.closest('form').submit();
				}
			});
			
		});
		
		/* single mark as read */
		$('.gdlr-mark-as-read').click(function(){
			$(this).closest('tr').siblings().find('input[type="checkbox"]').prop('checked', false);
			$(this).closest('tr').find('input[type="checkbox"]').prop('checked', true);
			$(this).closest('form').children('input[name="transaction-type"]').val('read');
			$(this).closest('form').submit();

			return false;
		});
		$('.gdlr-mark-as-unread').click(function(){
			$(this).closest('tr').siblings().find('input[type="checkbox"]').prop('checked', false);
			$(this).closest('tr').find('input[type="checkbox"]').prop('checked', true);
			$(this).closest('form').children('input[name="transaction-type"]').val('unread');
			$(this).closest('form').submit();

			return false;
		});		
		
		/* single mark as paid */
		$('.gdlr-mark-as-paid').click(function(){
			$(this).closest('tr').siblings().find('input[type="checkbox"]').prop('checked', false);
			$(this).closest('tr').find('input[type="checkbox"]').prop('checked', true);
			$(this).closest('form').children('input[name="transaction-type"]').val('paid');
			$(this).closest('form').submit();

			return false;
		});
		$('.gdlr-mark-as-booking').click(function(){
			$(this).closest('tr').siblings().find('input[type="checkbox"]').prop('checked', false);
			$(this).closest('tr').find('input[type="checkbox"]').prop('checked', true);
			$(this).closest('form').children('input[name="transaction-type"]').val('booking');
			$(this).closest('form').submit();

			return false;
		});	

		$('.gdlr-mark-as-remove').click(function(){
			var remove_btn = $(this);

			$('body').gdlr_confirm({
				text: 'You\'re cancelling transaction. Please note that, you need to manually refund your customers since this system does not support refunding and this option cannot be undone. Are you sure you want to do this ?',
				success: function(){
					remove_btn.closest('tr').siblings().find('input[type="checkbox"]').prop('checked', false);
					remove_btn.closest('tr').find('input[type="checkbox"]').prop('checked', true);
					remove_btn.closest('form').children('input[name="transaction-type"]').val('cancel');
					remove_btn.closest('form').submit();
				}
			});

			return false;
		});		
		
	});

})(jQuery);