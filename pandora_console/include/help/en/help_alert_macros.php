<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert macros</h1>

<p>
Besides the defined module macros, the following macros are available:
</p>
<ul>
<li>_field1_: User defined field 1.</li>
<li>_field2_: User defined field 2.</li>
<li>_field3_: User defined field 3.</li>
<li>_field4_: User defined field 4.</li>
<li>_field5_: User defined field 5.</li>
<li>_field6_: User defined field 6.</li>
<li>_field7_: User defined field 7.</li>
<li>_field8_: User defined field 8.</li>
<li>_field9_: User defined field 9.</li>
<li>_field10_: User defined field 10.</li>
<li>_agent_: Name of the agent that fired the alert.</li>
<li>_agentcustomfield_<i>n</i>_: Agent custom field number <i>n</i> (eg. _agentcustomfield_9_).</li>
<li>_agentcustomid_: Agent custom ID.</li>
<li>_agentdescription_: Description of the agent who fired alert.</li>
<li>_agentgroup_: Agent group name.</li>
<li>_agentstatus_: Current status of the agent.</li>
<li>_address_: Address of the agent that fired the alert.</li>
<li>_timestamp_: Time when the alert was fired (yy-mm-dd hh:mm:ss).</li>
<li>_timezone_: Timezone name that _timestamp_ represents in.</li>
<li>_data_: Module data that caused the alert to fire.</li>
<li>_alert_description_: Alert description.</li>
<li>_alert_threshold_: Alert threshold.</li>
<li>_alert_times_fired_: Number of times the alert has been fired.</li>
<li>_module_: Module name.</li>
<li>_modulecustomid_: Module custom ID.</li>
<li>_modulegroup_: Module group name.</li>
<li>_moduledescription_: Description of the module who fired the alert.</li>
<li>_modulestatus_: Status of the module.</li>
<li>_alert_name_: Alert name.</li>
<li>_alert_priority_: Numerical alert priority.</li>
<li>_alert_text_severity_: Text alert severity (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_text_severity_: (Only event alerts) Text event (who fire the alert) severity (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_id_: (Only event alerts) Id of the event that fired the alert.</li>
<li>_id_agent_: Id of agent, useful to build direct URL to redirect to a Pandora FMS console webpage.</li>
<li>_id_alert_: Numerical ID of the alert (unique), used to correlate on third party software.</li>
<li>_policy_: Name of the policy the module belongs to (if applies).</li>
<li>_interval_: Execution interval of the module </li>
<li>_target_ip_: IP address of the target of the module.</li>
<li>_target_port_: Port number of the target of the module.</li>
<li>_plugin_parameters_: Plug-in Parameters of the module.</li>
<li>_groupcontact_: Group contact information. Configured when the group is created.</li>
<li>_groupcustomid_: Group custom ID.</li>
<li>_groupother_: Other information about the group. Configured when the group is created.</li>
<li>_name_tag_: Names of the tags associated to the module.</li>
<li>_email_tag_: Emails associated to the module tags.</li>
<li>_phone_tag_: Phone numbers associated to the module tags.</li>
<li>_moduletags_: URLs associated to the module tags.</li>
<li>_alert_critical_instructions_: Instructions for the CRITICAL status contained in the module.</li>
<li>_alert_warning_instructions_: Instructions for the WARNING status contained in the module.</li>
<li>_modulegraph_<i>n</i>h_: (>=6.0) (Only for alerts that use the command <i>eMail</i>)
Returns an image codified in base64 of a module graph with a period of <i>n</i> hours (eg. _modulegraph_24h_).
A correct setup of the connection between the server and the console's api is required.
This setup is done into the server's configuration file.</li>
</ul>

<p>
Example: Agent _agent_ has fired alert _alert_ with data _data_
</p>


