(function($) {
	$.fn.check = function () {
		return this.each (function () {
			this.checked = true;
		});};
	
	$.fn.uncheck = function () {
		return this.each (function () {
			this.checked = false;
		});};
	
	$.fn.enable = function () {
		return $(this).removeAttr ("disabled");
		};
	
	$.fn.disable = function () {
		return $(this).attr ("disabled", "disabled");
		};
	
	$.fn.pulsate = function () {
		var i = 0;
		for (i = 0; i <= 2; i++) {
			$(this).fadeOut ("slow").fadeIn ("slow");
		}
	};
	
	$.fn.showMessage = function (msg) {
		return $(this).hide ().empty ()
				.text (msg)
				.slideDown ();
		};
}) (jQuery);

$(document).ready (function () {
	$("a#show_messages_dialog").click (function () {
		jQuery.get ("ajax.php",
			{"page": "operation/messages/message_list"},
			function (data, status) {
				$("#dialog_messages").hide ()
					.empty ()
					.append (data)
					.dialog ({
						title: $("a#show_messages_dialog").attr ("title"),
						resizable: false,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						bgiframe: jQuery.browser.msie,
						width: 700,
						height: 300
					})
					.show ();
			},
			"html"
		);
		
		return false;
	});
	
	$("a#show_systemalert_dialog").click (function () {
		jQuery.get ("ajax.php",
			{"page": "operation/system_alert"},
			function (data, status) {
				$("#alert_messages").hide ()
					.empty ()
					.append (data)
					.dialog ({
						title: $("a#show_systemalert_dialog").attr ("title"),
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						bgiframe: jQuery.browser.msie,
						width: 700,
						height: 300
					})
					.show ();
			},
			"html"
		);	
		return false;
	});
	
	if ($('#license_error_msg_dialog').length) {

		$( "#license_error_msg_dialog" ).dialog({
					resizable: true,
					draggable: true,
					modal: true,
					height: 280,
					width: 600,
					overlay: {
								opacity: 0.5,
								background: "black"
							},
					bgiframe: jQuery.browser.msie
		});
		
		$("#submit-hide-license-error-msg").click (function () {
			$("#license_error_msg_dialog" ).dialog('close')
		});
	
	}
	
	
	$("a#dialog_license_info").click (function () {
		jQuery.get ("ajax.php",
			{"page": "extensions/update_manager",
			 "get_license_info": "1"},
			function (data, status) {
				$("#dialog_show_license").hide ()
					.empty ()
					.append (data)
					.dialog ({
						title: $("a#dialog_license_info").attr ("title"),
						resizable: false,
                        draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						bgiframe: jQuery.browser.msie,
						width: 500,
						height: 190
					})
					.show ();
			},
			"html"
		);	
		return false;
	});
	
	if ($('#msg_change_password').length) {	
	
		$( "#msg_change_password" ).dialog({
					resizable: true,
					draggable: true,
					modal: true,
					height: 260,
					width: 590,
					overlay: {
								opacity: 0.5,
								background: "black"
							},
					bgiframe: jQuery.browser.msie
		});
	
	}
	
	if ($('#login_blocked').length) {
	
		$( "#login_blocked" ).dialog({
					resizable: true,
					draggable: true,
					modal: true,
					height: 180,
					width: 400,
					overlay: {
								opacity: 0.5,
								background: "black"
							},
					bgiframe: jQuery.browser.msie
		});

	}

});
