var correct_install_progress = true;

function form_upload () {
	//Thanks to: http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/
	var ul = $('#form-offline_update ul');
	
	$('#form-offline_update div')
		.prop("id", "drop_file");
	$('#drop_file')
		.html(drop_the_package_here_or +
			'&nbsp;&nbsp;&nbsp;<a>' + browse_it +'</a>' +
			'<input name="upfile" type="file" id="file-upfile" accept=".oum" class="sub file" />');
	$('#drop_file a').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$(this).parent().find('input').click();
	});
	
	// Initialize the jQuery File Upload plugin
	$('#form-offline_update').fileupload({
		
		url: 'ajax.php?page=include/ajax/update_manager.ajax&upload_file=true',
		
		// This element will accept file drag/drop uploading
		dropZone: $('#drop_file'),
		
		// This function is called when a file is added to the queue;
		// either via the browse button, or via drag/drop:
		add: function (e, data) {
			$('#drop_file').slideUp();
			
			var tpl = $('<li>' +
					'<input type="text" id="input-progress" ' +
						'value="0" data-width="55" data-height="55" '+
						'data-fgColor="#FF9933" data-readOnly="1" ' +
						'data-bgColor="#3e4043" />' +
					'<p></p><span></span>' +
				'</li>');
			
			// Append the file name and file size
			tpl.find('p').text(data.files[0].name)
						.append('<i>' + formatFileSize(data.files[0].size) + '</i>');
			
			// Add the HTML to the UL element
			ul.html("");
			data.context = tpl.appendTo(ul);
			
			// Initialize the knob plugin
			tpl.find('input').val(0);
			tpl.find('input').knob({
				'draw' : function () {
					$(this.i).val(this.cv + '%')
				}
			});
			
			// Listen for clicks on the cancel icon
			tpl.find('span').click(function() {
				
				if (tpl.hasClass('working') && typeof jqXHR != 'undefined') {
					jqXHR.abort();
				}
				
				tpl.fadeOut(function() {
					tpl.remove();
					$('#drop_file').slideDown();
				});
				
			});
			
			// Automatically upload the file once it is added to the queue
			data.context.addClass('working');
			var jqXHR = data.submit();
		},
		
		progress: function(e, data) {
			
			// Calculate the completion percentage of the upload
			var progress = parseInt(data.loaded / data.total * 100, 10);
			
			// Update the hidden input field and trigger a change
			// so that the jQuery knob plugin knows to update the dial
			data.context.find('input').val(progress).change();
			
			if (progress == 100) {
				data.context.removeClass('working');
				// Class loading while the zip is extracted
				data.context.addClass('loading');
			}
		},
		
		fail: function(e, data) {
			// Something has gone wrong!
			data.context.removeClass('working');
			data.context.removeClass('loading');
			data.context.addClass('error');
		},
		
		done: function (e, data) {
			
			var res = JSON.parse(data.result);
			
			if (res.status == "success") {
				data.context.removeClass('loading');
				data.context.addClass('suc');
				
				ul.find('li').find('span').unbind("click");
				
				// Transform the file input zone to show messages
				$('#drop_file').prop('id', 'log_zone');
				
				// Success messages
				$('#log_zone').html("<div>" + the_package_has_been_uploaded_successfully + "</div>");
				$('#log_zone').append("<div>" + remember_that_this_package_will + "</div>");
				$('#log_zone').append("<div>" + click_on_the_file_below_to_begin + "</div>");
				
				// Show messages
				$('#log_zone').slideDown(400, function() {
					$('#log_zone').height(75);
					$('#log_zone').css("overflow", "auto");
				});
				
				// Bind the the begin of the installation to the package li
				ul.find('li').css("cursor", "pointer");
				ul.find('li').click(function () {
					
					ul.find('li').unbind("click");
					ul.find('li').css("cursor", "default");
					
					// Change the log zone to show the copied files
					$('#log_zone').html("");
					$('#log_zone').slideUp(200, function() {
						$('#log_zone').slideDown(200, function() {
							$('#log_zone').height(200);
							$('#log_zone').css("overflow", "auto");
						});
					});
					
					// Changed the data that shows the file li
					data.context.find('p').text(updating + "...");
					data.context.find('input').val(0).change();
					
					// Begin the installation
					install_package(res.package, 'filename');
				});
			}
			else {
				// Something has gone wrong!
				data.context.removeClass('loading');
				data.context.addClass('error');
				ul.find('li').find('span').click(
					function() { window.location.reload(); });
				
				// Transform the file input zone to show messages
				$('#drop_file').prop('id', 'log_zone');
				
				// Error messages
				$('#log_zone').html("<div>"+res.message+"</div>");
				
				// Show error messages
				$('#log_zone').slideDown(400, function() {
					$('#log_zone').height(75);
					$('#log_zone').css("overflow", "auto");
				});
			}
		}
		
	});
	
	// Prevent the default action when a file is dropped on the window
	$(document).on('drop_file dragover', function (e) {
		e.preventDefault();
	});
}

// Helper function that formats the file sizes
function formatFileSize(bytes) {
	if (typeof bytes !== 'number') {
		return '';
	}
	
	if (bytes >= 1000000000) {
		return (bytes / 1000000000).toFixed(2) + ' GB';
	}
	
	if (bytes >= 1000000) {
		return (bytes / 1000000).toFixed(2) + ' MB';
	}
	
	return (bytes / 1000).toFixed(2) + ' KB';
}

function install_package(package) {
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['install_package'] = 1;
	parameters['package'] = package;
	
	jQuery.post(
		"ajax.php",
		parameters,
		function (data) {
			if (data["status"] == "success") {
				install_package_step2(package);
			}
			else {
				$("#box_online .loading").hide();
				$("#box_online .content").html(data['message']);
				stop_check_progress = 1;
			}
		},
		"json"
	);
}

function install_package (package) {
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['install_package'] = 1;
	parameters['package'] = package;
	
	$('#form-offline_update ul').find('li').removeClass('suc');
	$('#form-offline_update ul').find('li').addClass('loading');
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: parameters,
		dataType: "json",
		success: function (data) {
			$('#form-offline_update ul').find('li').removeClass('loading');
			if (data.status == "success") {
				$('#form-offline_update ul').find('li').addClass('suc');
				$('#form-offline_update ul').find('li').find('p').html(package_updated_successfully)
					.append("<i>" + if_there_are_any_database_change + "</i>");
			}
			else {
				$('#form-offline_update ul').find('li').addClass('error');
				$('#form-offline_update ul').find('li').find('p').html(package_not_updated)
					.append("<i>"+data.message+"</i>");
			}
			$('#form-offline_update ul').find('li').css("cursor", "pointer");
			$('#form-offline_update ul').find('li').click(function() {
				window.location.reload();
			});
		}
	});
	
	// Check the status of the update
	check_install_package(package);
}

function check_install_package(package) {
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_install_package'] = 1;
	parameters['package'] = package;
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: parameters,
		dataType: "json",
		success: function(data) {
			// Print the updated files and take the scroll to the bottom
			$("#log_zone").html(data.info);
			$("#log_zone").scrollTop($("#log_zone").prop("scrollHeight"));
			
			// Change the progress bar
			if ($('#form-offline_update ul').find('li').hasClass('suc')) {
				$('#form-offline_update').find('ul').find('li').find('input').val(100).trigger('change');
			} else {
				$('#form-offline_update').find('ul').find('li').find('input').val(data['progress']).trigger('change');
			}
			
			// The class loading is present until the update ends
			var isInstalling = $('#form-offline_update ul').find('li').hasClass('loading');
			if (data.progress < 100 && isInstalling) {
				// Recursive call to check the update status
				check_install_package(package);
			}
		}
	})
}

function check_online_free_packages() {
	$("#box_online .checking_package").show();
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_online_free_packages'] = 1;
	
	jQuery.post(
		"ajax.php",
		parameters,
		function (data) {
			$("#box_online .checking_package").hide();
			
			$("#box_online .loading").hide();
			$("#box_online .content").html(data);
		},
		"html"
	);
}

function update_last_package(package, version) {
	version_update = version;
	
	$("#box_online .content").html("");
	$("#box_online .loading").show();
	$("#box_online .download_package").show();
	
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['update_last_free_package'] = 1;
	parameters['package'] = package;
	parameters['version'] = version;
	
	jQuery.post(
		"ajax.php",
		parameters,
		function (data) {
			if (data['in_progress']) {
				$("#box_online .loading").hide();
				$("#box_online .download_package").hide();
				
				$("#box_online .content").html(data['message']);
				
				install_free_package(package,version);
				setTimeout(check_progress_update, 1000);
			}
			else {
				$("#box_online .content").html(data['message']);
			}
		},
		"json"
	);
}

function check_progress_update() {
	if (stop_check_progress) {
		return;
	}
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_update_free_package'] = 1;
	
	jQuery.post(
		"ajax.php",
		parameters,
		function (data) {
			if (stop_check_progress) {
				return;
			}
			
			if (data['correct']) {
				if (data['end']) {
					//$("#box_online .content").html(data['message']);
				}
				else {
					$("#box_online .progressbar").show();
					
					$("#box_online .progressbar .progressbar_img").attr('src',
						data['progressbar']);
					
					setTimeout(check_progress_update, 1000);
				}
			}
			else {
				correct_install_progress = false;
				$("#box_online .content").html(data['message']);
			}
		},
		"json"
	);
}

function install_free_package(package,version) {
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['install_free_package'] = 1;
	parameters['package'] = package;
	parameters['version'] = version;
	
	jQuery.ajax ({
		data: parameters,
		type: 'POST',
		url: "ajax.php",
		timeout: 600000,
		dataType: "json",
		error: function(data) {
			correct_install_progress = false;
			stop_check_progress = 1;
			
			$("#box_online .loading").hide();
					$("#box_online .progressbar").hide();
			$("#box_online .content").html(unknown_error_update_manager);
		},
		success: function (data) {
			if (correct_install_progress) {
				if (data["status"] == "success") {
					$("#box_online .loading").hide();
					$("#box_online .progressbar").hide();
					$("#box_online .content").html(data['message']);
					stop_check_progress = 1;
				}
				else {
					$("#box_online .loading").hide();
					$("#box_online .progressbar").hide();
					$("#box_online .content").html(data['message']);
					stop_check_progress = 1;
				}
			}
			else {
				stop_check_progress = 1;
			}
		}
	});
}