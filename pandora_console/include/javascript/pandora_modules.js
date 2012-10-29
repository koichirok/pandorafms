/* Modules ids to check types */
var id_modules_icmp = Array (6, 7);
var id_modules_tcp = Array (8, 9, 10, 11);
var id_modules_snmp = Array (15, 16, 17, 18);

function configure_modules_form () {
	$("#id_module_type").change (function () {
		if (id_modules_icmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2, tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").attr ("disabled", "1");
		} else if (id_modules_snmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").show ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").removeAttr ("disabled");
		} else if (id_modules_tcp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").hide ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").show ();
			$("#text-tcp_port").removeAttr ("disabled");
		}
	});
	
	$("#local_component_group").change (function () {
	
		var $select = $("#local_component").hide ();
		$("#component").hide ();
		if (this.value == 0) {
			reset_data_module_form();
			return;
		}
		$("#component_loading").show ();
		$(".error, #no_component").hide ();
		$("option[value!=0]", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_local_components" : 1,
			"id_module_component_group" : this.value,
			"id_module_component_type" : $("#hidden-id_module_component_type").attr ("value")
			},
			function (data, status) {
				if (data == false) {
					$("#component_loading").hide ();
					$("span#no_component").show ();
					return;
				}
				jQuery.each (data, function (i, val) {
					option = $("<option></option>")
						.attr ("value", val['id'])
						.append (val['name']);
					$select.append (option);
				});
				$("#component_loading").hide ();
				$select.show ();
				$("#component").show ();
			},
			"json"
		);
	
		}
	);
	
	function reset_data_module_form() {
			// Delete macro fields
			$('.macro_field').remove();
			
			// Hide show/hide configuration data switch
			$('#simple-show_configuration_data').hide();
			$('#simple-hide_configuration_data').hide();
			$('#configuration_data_legend').hide();
			
			$("#textarea_configuration_data").val('');
			$('#simple-configuration_data').show();

			$("#text-name").val('');
			$("#textarea_description").val('');
			$("#checkbox-history_data").check ();
			$("#text-max").attr ("value", "");
			$("#text-min").attr ("value", "");
			$("#text-min_warning").attr ("value", 0);
			$("#text-max_warning").attr ("value", 0);
			$("#text-str_warning").attr ("value", '');
			$("#text-min_critical").attr ("value", 0);
			$("#text-max_critical").attr ("value", 0);
			$("#text-str_critical").attr ("value", '');
			$("#text-ff_event").attr ("value", 0);
			$("#text-post_process").attr("value", 0);
			$("#text-unit").attr("value", '');
			$("#text-critical_inverse").attr ("value", 0);
			$("#text-warning_inverse").attr ("value", 0);
			$("#textarea_critical_instructions").attr ("value", '');
			$("#textarea_warning_instructions").attr ("value", '');
			$("#textarea_unknown_instructions").attr ("value", '');
			
	}
	
	$("#local_component").change (function () {
		if (this.value == 0) {
			reset_data_module_form();
			return;
		}
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_local_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				configuration_data = js_html_entity_decode (data['data']);
				$("#text-name").attr ("value", js_html_entity_decode (data["name"]));
				$("#textarea_description").attr ("value", js_html_entity_decode (data["description"]));
				$("#textarea_configuration_data").attr ("value", configuration_data);
				$("#component_loading").hide ();
				$("#id_module_type option[value="+data["type"]+"]").select (1);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				// Workaround to update the advanced select control from html and ajax
				if(typeof 'period_select_module_interval_update' == 'function') {
					period_select_module_interval_update(data["module_interval"]);
				}
				else {
					period_select_update('module_interval', data["module_interval"]);
				}				$("#id_module_group option[value="+data["id_module_group"]+"]").select (1);
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				$("#text-min_warning").attr ("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning").attr ("value", (data["max_warning"] == 0) ? 0 : data["max_warning"]);
				$("#text-str_warning").attr ("value", (data["str_warning"] == 0) ? 0 : data["str_warning"]);
				$("#text-min_critical").attr ("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical").attr ("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-str_critical").attr ("value", (data["str_critical"] == 0) ? 0 : data["str_critical"]);
				$("#text-ff_event").attr ("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#text-post_process").attr("value", (data["post_process"] == 0) ? 0 : data["post_process"]);
				$("#text-unit").attr("value", (data["unit"] == '') ? '' : data["unit"])
				$("#text-critical_inverse").attr ("value", (data["critical_inverse"] == 0) ? 0 : data["critical_inverse"]);
				$("#text-warning_inverse").attr ("value", (data["warning_inverse"] == 0) ? 0 : data["warning_inverse"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
			
				// Delete macro fields
				$('.macro_field').remove();
				
				$('#hidden-macros').val('');

				var legend = '';
				// If exist macros, load the fields
				if(data["macros"] != '') {
					$('#hidden-macros').val(Base64.encode(data["macros"]));
					
					var obj = jQuery.parseJSON(data["macros"]);
					$.each(obj, function(k,macro) {
						add_macro_field(macro, 'simple-macro');
						legend += macro['macro']+" = "+ macro['desc']+"<br>";
					});
					$('#configuration_data_legend').html(legend);

					$('#simple-show_configuration_data').show();
					$('#simple-hide_configuration_data').hide();
					$('#configuration_data_legend').show();
					$('#simple-configuration_data').hide();
				}
				else {
					$('#simple-show_configuration_data').hide();
					$('#simple-hide_configuration_data').hide();
					$('#configuration_data_legend').hide();
					$('#simple-configuration_data').show();
				}
			},
			"json"
		);
	});
	
	$("#network_component_group").change (function () {
		var $select = $("#network_component").hide ();
		$("#component").hide ();
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error, #no_component").hide ();
		$("option[value!=0]", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_components" : 1,
			"id_module_component_group" : this.value,
			"id_module_component_type" : $("#hidden-id_module_component_type").attr ("value")
			},
			function (data, status) {
				if (data == false) {
					$("#component_loading").hide ();
					$("span#no_component").show ();
					return;
				}
				jQuery.each (data, function (i, val) {
					option = $("<option></option>")
						.attr ("value", val['id_nc'])
						.append (val['name']);
					$select.append (option);
				});
				$("#component_loading").hide ();
				$select.show ();
				$("#component").show ();
			},
			"json"
		);
	});
	
	$("#network_component").change (function () {
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				$("#text-name").attr ("value", js_html_entity_decode (data["name"]));
				$("#textarea_description").attr ("value", js_html_entity_decode (data["description"]));
				$("#id_module_type option[value="+data["type"]+"]").select (1);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				// Workaround to update the advanced select control from html and ajax
				if(typeof 'period_select_module_interval_update' == 'function') {
					period_select_module_interval_update(data["module_interval"]);
				}
				else {
					period_select_update('module_interval', data["module_interval"]);
				}
				$("#text-tcp_port").attr ("value", data["tcp_port"]);
				$("#textarea_tcp_send").attr ("value", js_html_entity_decode (data["tcp_send"]));
				$("#textarea_tcp_rcv").attr ("value", js_html_entity_decode (data["tcp_rcv"]));
				$("#text-snmp_community").attr ("value", js_html_entity_decode (data["snmp_community"]));
				$("#text-snmp_oid").attr ("value", js_html_entity_decode (data["snmp_oid"])).show ();
				$("#oid, img#edit_oid").hide ();
				$("#id_module_group option[value="+data["id_module_group"]+"]").select (1);
				$("#max_timeout").attr ("value", data["max_timeout"]);
				$("#max_retries").attr ("value", data["max_retries"]);
				$("#id_plugin option[value="+data["id_plugin"]+"]").select (1);
				$("#id_plugin").trigger('change');
				$("#text-plugin_user").attr ("value", js_html_entity_decode (data["plugin_user"]));
				$("#password-plugin_pass").attr ("value", js_html_entity_decode (data["plugin_pass"]));
				$("#text-plugin_parameter").attr ("value", js_html_entity_decode (data["plugin_parameter"]));
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				$("#text-min_warning").attr ("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning").attr ("value", (data["max_warning"] == 0) ? 0 : data["max_warning"]);
				$("#text-str_warning").attr ("value", (data["str_warning"] == 0) ? 0 : data["str_warning"]);
				$("#text-min_critical").attr ("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical").attr ("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-str_critical").attr ("value", (data["str_critical"] == 0) ? 0 : data["str_critical"]);
				$("#text-ff_event").attr ("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#text-post_process").attr("value", (data["post_process"] == 0) ? 0 : data["post_process"]);
				$("#text-unit").attr("value", (data["unit"] == '') ? '' : data["unit"])
				$("#text-critical_inverse").attr ("value", (data["critical_inverse"] == 0) ? 0 : data["critical_inverse"]);
				$("#text-warning_inverse").attr ("value", (data["warning_inverse"] == 0) ? 0 : data["warning_inverse"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
				
				// Delete macro fields
				$('.macro_field').remove();
				
				$('#hidden-macros').val('');

				// If exist macros, load the fields
				if(data["macros"] != '') {
					$('#hidden-macros').val(Base64.encode(data["macros"]));
					
					var obj = jQuery.parseJSON(data["macros"]);
					$.each(obj, function(k,macro) {
						add_macro_field(macro, 'simple-macro');
					});
				}
				
				if (data["type"] >= 15 && data["type"] <= 18) {
					$("#snmp_version option[value="+data["tcp_send"]+"]").select(1);
					$("#text-snmp3_auth_user").val(data["plugin_user"]);
					$("#text-snmp3_auth_pass").val(data["plugin_pass"]);
					$("#snmp3_auth_method option[value="+data["plugin_parameter"]+"]").select(1);
					$("#snmp3_privacy_method option[value="+data["custom_string_1"]+"]").select(1);
					$("#text-snmp3_privacy_pass").val(data["custom_string_2"]);
					$("#snmp3_security_level option[value="+data["custom_string_3"]+"]").select(1);
					
					if (data["tcp_send"] == "3") {
						$("#simple-field_snmpv3_row1").attr("style", "");
						$("#simple-field_snmpv3_row2").attr("style", "");
						$("#simple-field_snmpv3_row3").attr("style", "");
						$("input[name=active_snmp_v3]").val(1);
					}
				}
			},
			"json"
		);
	});
	
	$("#text-ip_target").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#text-tcp_port").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#text-snmp_community").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#snmp_version").change(function () {
		$("#button-snmp_walk").enable ();
	});
	
	$("#text-snmp3_auth_user").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#text-snmp3_auth_pass").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#snmp3_privacy_method").change(function () {
		$("#button-snmp_walk").enable ();
	});
	
	$("#text-snmp3_privacy_pass").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		}
		else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#snmp3_auth_method").change(function () {
		$("#button-snmp_walk").enable ();
	});
	
	$("#snmp3_security_level").change(function () {
		$("#button-snmp_walk").enable ();
	});
	
	$("#button-snmp_walk").click (function () {
		$(this).disable ();
		$("#oid_loading").show ();
		$("span.error").hide ();
		$("#select_snmp_oid").empty ().hide ();
		$("#text-snmp_oid").hide ().attr ("value", "");
		$("span#oid").show ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"snmp_walk" : 1,
			"ip_target" : $("#text-ip_target").fieldValue (),
			"snmp_community" : $("#text-snmp_community").fieldValue (),
			"snmp_port" : $("#text-tcp_port").val(),
			"snmp_version": $('#snmp_version').val(),
			"snmp3_auth_user": $('input[name=snmp3_auth_user]').val(),
			"snmp3_security_level": $('#snmp3_security_level').val(),
			"snmp3_auth_method": $('#snmp3_auth_method').val(),
			"snmp3_auth_pass": $('input[name=snmp3_auth_pass]').val(),
			"snmp3_privacy_method": $('#snmp3_privacy_method').val(),
			"snmp3_privacy_pass": $('input[name=snmp3_privacy_pass]').val()
			},
			function (data, status) {
				if (data == false) {
					$("span#no_snmp").show ();
					$("#oid_loading").hide ();
					$("#edit_oid").hide ();
					$("#button-snmp_walk").enable ();
					return false;
				}
				jQuery.each (data, function (id, value) {
					opt = $("<option></option>").attr ("value", id).html (value);
					$("#select_snmp_oid").append (opt);
				});
				$("#select_snmp_oid").show ();
				$("#oid_loading").hide ();
				$("#edit_oid").show ();
				$("#button-snmp_walk").enable ();
			},
			"json"
		);
	});
	
	$("img#edit_oid").click (function () {
		$("#oid").hide ();
		$("#text-snmp_oid").show ()
			.attr ("value", $("#select_snmp_oid").fieldValue ());
		$(this).hide ();
	});
	
	$("form#module_form").submit (function () {
		if ($("#text-name").val () == "") {
			$("#text-name").focus ();
			$("#message").showMessage (no_name_lang);
			return false;
		}
		
		if ($("#id_plugin").attr ("value") == 0) {
			$("#id_plugin").focus ();
			$("#message").showMessage (no_plugin_lang);
			return false;
		}

		moduletype = $("#hidden-moduletype").val ();
		if (moduletype == 5) {
			if ($("#prediction_module").val () == null) {
				$("#prediction_module").focus ();
				$("#message").showMessage (no_prediction_module_lang);
				return false;
			}
		}
		
		module = $("#id_module_type").attr ("value");
		
		if (id_modules_icmp.in_array (module) || id_modules_tcp.in_array (module) || id_modules_snmp.in_array (module)) {
			/* Network module */
			if ($("#text-ip_target").val () == "") {
				$("#text-ip_target").focus ();
				$("#message").showMessage (no_target_lang);
				return false;
			}
		}
		
		if (id_modules_snmp.in_array (module)) {
			if ($("#text-snmp_oid").attr ("value") == "") {
				if ($("#select_snmp_oid").attr ("value") == "") {
					$("#message").showMessage (no_oid_lang);
					return false;
				}
			}
		}
		
		$("#message").hide ();
		return true;
	});
	
	if(typeof $("#prediction_id_group").pandoraSelectGroupAgent == 'function') {
		$("#prediction_id_group").pandoraSelectGroupAgent ({
			agentSelect: "select#prediction_id_agent",
			callbackBefore: function () {
				$("#module_loading").show ();
				$("#prediction_module option").remove ();
				return true;
			},
			callbackAfter: function (e) {
				if ($("#prediction_id_agent").children ().length == 0) {
					$("#module_loading").hide ();
					return;
				}
				$("#prediction_id_agent").change ();
			}
		});
	}
	
	if(typeof $("#prediction_id_agent").pandoraSelectAgentModule == 'function') {
		$("#prediction_id_agent").pandoraSelectAgentModule ({
			moduleSelect: "select#prediction_module"
		});
	}
}


// Functions to add and remove dynamic fields for macros
function delete_macro(prefix) {
	var next_number = parseInt($('#next_macro').html());
	// Is not possible delete first macro
	if(next_number == 3) {
		$('#delete_macro_button').hide();
	}
	var next_row = parseInt($('#next_row').html());
	$('#next_macro').html(next_number-1);
	$('#next_row').html(next_row-2);

	var nrow1 = next_row - 2;
	var nrow2 = next_row - 1;
	
	var $row1 = $('#'+prefix+nrow1).remove();
	var $row2 = $('#'+prefix+nrow2).remove();
}

function new_macro(prefix) {
	$('#delete_macro_button').show();

	var next_row = parseInt($('#next_row').html());

	$('#next_row').html(next_row+2);
	var nrow1 = next_row - 2;
	var nrow2 = next_row - 1;
	var nrow3 = next_row;
	var nrow4 = next_row + 1;
	
	var next_number = parseInt($('#next_macro').html());
	$('#next_macro').html(next_number+1);
	var current_number = next_number - 1;
	
	// Clone two last rows
	var $row1 = $('#'+prefix+nrow1).clone(true);
	var $row2 = $('#'+prefix+nrow2).clone(true);

	// Change the tr ID
	$row1.attr('id',prefix+(nrow3));
	$row2.attr('id',prefix+(nrow4));
	// Change the td ID
	$row1.find('td').attr('id', changeTdId);
	$row2.find('td').attr('id', changeTdId);
	
	// Insert after last field
	$row2.insertAfter('#'+prefix+nrow2);
	$row1.insertAfter('#'+prefix+nrow2);
	
	// Change labels
	for(i=0;i<=3;i++) {
		var label1 = $("#"+prefix+nrow3+"-"+i).html();
		var exp_reg = new RegExp('field'+current_number, 'g'); 
		label1 = label1.replace(exp_reg,'field'+next_number);
		$("#"+prefix+nrow3+"-"+i).html(label1);
	}
	
	for(i=0;i<=1;i++) {
		var label2 = $("#"+prefix+nrow4+"-"+i).html();
		var exp_reg = new RegExp('field'+current_number, 'g'); 
		label2 = label2.replace(exp_reg,'field'+next_number);
		$("#"+prefix+nrow4+"-"+i).html(label2);
	}
	
	// Empty the text inputs
	$('#text-field'+next_number+'_desc').val('');
	$('#text-field'+next_number+'_help').val('');
	$('#text-field'+next_number+'_value').val('');
	
	function changeTdId() {
		switch(this.id) {
			case prefix+(nrow1)+'-0':
				return prefix+(nrow3)+'-0';
				break;
			case prefix+(nrow1)+'-1':
				return prefix+(nrow3)+'-1';
				break;
			case prefix+(nrow1)+'-2':
				return prefix+(nrow3)+'-2';
				break;
			case prefix+(nrow1)+'-3':
				return prefix+(nrow3)+'-3';
				break;
			case prefix+(nrow2)+'-0':
				return prefix+(nrow4)+'-0';
				break;
			case prefix+(nrow2)+'-1':
				return prefix+(nrow4)+'-1';
				break;
			case prefix+(nrow2)+'-2':
				return prefix+(nrow4)+'-2';
				break;
			case prefix+(nrow2)+'-3':
				return prefix+(nrow4)+'-3';
				break;
		}
	}
	
}

function add_macro_field(macro, row_model_id) {
	var macro_desc = macro['desc'];
	var macro_help = macro['help'];
	var macro_macro = macro['macro'];
	var macro_value = macro['value'];
		
	var row_id = row_model_id + macro_macro;
	
	var $macro_field = $('#'+ row_model_id +'_field').clone(true);
	
	// Change attributes to be unique and with identificable class
	$macro_field.attr('id',row_id);
	$macro_field.attr('class','macro_field');
		
	// Get the number of fields already printed
	var fields = $('.macro_field').size();
	
	// If is the first, we insert it after model row
	if(fields == 0) {
		$macro_field.insertAfter('#'+ row_model_id +'_field');
	}
	// If there are more fields, we insert it after the last one
	else {
		$macro_field.insertAfter('#'+ $('.macro_field').eq(fields-1).attr('id'));
	}
	
	// Change the label
	if(macro_help == '') {
		$('#'+row_id).children().eq(0).html(macro_desc);
	}
	else {
		var field_desc = $('#'+row_id).children().eq(0).html();
		field_desc = field_desc.replace('macro_desc',macro_desc);
		field_desc = field_desc.replace('macro_help',macro_help);
		$('#'+row_id).children().eq(0).html(field_desc);
	}
	
	// Change the text box id and value
	$('#'+row_id).children().eq(1).children().attr('id','text-'+macro_macro);
	$('#'+row_id).children().eq(1).children().attr('name',macro_macro);
	$('#'+row_id).children().eq(1).children().val(macro_value);

		
	$('#'+row_id).show();
}

function load_plugin_macros_fields(row_model_id) {
	// Get plugin macros when selected and load macros fields
	var id_plugin = $('#id_plugin').val();
	
	var params = [];
	params.push("page=include/ajax/module");
	params.push("get_plugin_macros=1");
	params.push("id_plugin="+id_plugin);
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: false,
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			// Delete all the macro fields
			$('.macro_field').remove();

			if(data['array'] != null) {
				$('#hidden-macros').val(data['base64']);
				jQuery.each (data['array'], function (i, macro) {
					if(macro['desc'] != '') {
						add_macro_field(macro, row_model_id);
					}
				});
			}
		}
	});	
}

