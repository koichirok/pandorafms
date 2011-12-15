<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Forecast
 */

/**
 * Create a prediction based on module data with least square method (linear regression) 
 *
 * @param int Module id.
 * @param int Period of the module data.
 * @param int Period of the prediction or false to use it in prediction_date function (see below). 
 * @param int Maximun value using this function for prediction_date.
 * @param int Minimun value using this function for prediction_date.
 * 
 * @return array Void array or prediction of the module data.
 */
function forecast_projection_graph($module_id, $period = 5184000, $prediction_period, $max_value = false, $min_value = false){
	global $config;

	$module_data=grafico_modulo_sparse ($module_id, $period, 0,
				300, 300 , '', null,
				false, 0, false,
				0, '', 0, 1, false,
				true, '', 1, true);

	if (empty($module_data)){
		return array();	
	}
	// Prevents bad behaviour over image error 
	else if (!is_array($module_data) and preg_match('/^<img(.)*$/', $module_data)){
		return;
	}		
			
	// Data initialization
	$sum_obs = 0;
	$sum_xi = 0;
	$sum_yi = 0;
	$sum_xi_yi = 0;
	$sum_xi2 = 0;
	$sum_yi2 = 0;
	$sum_diff_dates = 0;	
	$last_timestamp = get_system_time();
	$agent_interval = 300;
	$cont = 1;
	$data = array();
	$table->data = array();	

	// Creates data for calculation		
	foreach ($module_data as $utimestamp => $row) {
		if ($utimestamp == '') { continue; }	
		$data[0] = '';
		$data[1] = $cont;
		$data[2] = date('d M Y H:i:s', $utimestamp);
		$data[3] = $utimestamp;
		$data[4] = $row['sum'];
		$data[5] = $utimestamp * $row['sum'];
		$data[6] = $utimestamp * $utimestamp;
		$data[7] = $row['sum'] * $row['sum'];
		if ($cont == 1){
			$data[8] = 0;
		}else{	
			$data[8] = $utimestamp - $last_timestamp;
		}		
		
		$sum_obs = $sum_obs + $cont;
		$sum_xi = $sum_xi + $utimestamp;
		$sum_yi = $sum_yi + $row['sum'];
		$sum_xi_yi = $sum_xi_yi + $data[5];
		$sum_xi2 = $sum_xi2 + $data[6];
		$sum_yi2 = $sum_yi2 + $data[7];
		$sum_diff_dates = $sum_diff_dates + $data[8];		
		$last_timestamp = $utimestamp;	
		$cont++;
		
		array_push($table->data, $data);												
	}		

	$cont--;
	
	// Calculation over data above:
	// 1. Calculation of linear correlation coefficient...
	
	// 1.1 Average for X: Sum(Xi)/Obs 
	// 1.2 Average for Y: Sum(Yi)/Obs
	// 2. Covariance between vars
	// 3.1  Standard deviation for X: sqrt((Sum(Xi²)/Obs) - (avg X)²) 
	// 3.2 Standard deviation for Y: sqrt((Sum(Yi²)/Obs) - (avg Y)²) 
	// Linear correlation coefficient:
	
	$avg_x = $cont/$sum_xi;
	$avg_y = $cont/$sum_yi;
	$covariance = $sum_xi_yi/$cont;
	$dev_x = sqrt(($sum_xi2/$cont) - ($avg_x*$avg_x));
	$dev_y = sqrt(($sum_yi2/$cont) - ($avg_y*$avg_y));
	// Prevents division by zero
	if ($dev_x != 0 and $dev_y != 0){
		$linear_coef = $covariance / ($dev_x * $dev_y);	 
	}
	// Agent interval could be zero, 300 is the predefined
	($sum_obs == 0)? $agent_interval = 300 : $agent_interval =  $sum_diff_dates / $sum_obs; 			

	// Could be a inverse correlation coefficient
	// if $linear_coef < 0.0
	//	 if $linear_coef >= -1.0 and $linear_coef <= -0.8999
	// 		Function variables have an inverse linear relathionship!
	//   else 
	// 		Function variables don't have an inverse linear relathionship!	
	
	// Could be a direct correlation coefficient
	// else 
	//	 if ($linear_coef >= 0.8999 and $linear_coef <= 1.0) {
	//		Function variables have a direct linear relathionship!
	//   else 
	//		Function variables don't have a direct linear relathionship!
	
	// 2. Calculation of linear regresion...
	
	$b_num = (($cont * $sum_xi_yi) - ($sum_xi * $sum_yi));
	$b_den = (($cont * $sum_xi2) - ($sum_xi * $sum_xi));
	$b = $b_num / $b_den;

	$a_num = ($sum_yi) - ($b * $sum_xi);
	$a = $a_num / $cont;	

	// Data inicialization
	$output_data = array();
	if ($prediction_period != false){
		$limit_timestamp = $last_timestamp + $prediction_period;
	}
	$current_ts = $last_timestamp;
	$in_range = true;

	if ($period <= 3600) {
		$title_period = __('Last hour');
		$time_format = 'G:i:s';
	}
	elseif ($period <= 86400) {
		$title_period = __('Last day');
		$time_format = 'G:i';
	}
	elseif ($period <= 604800) {
		$title_period = __('Last week');
		$time_format = 'M j';
	}
	elseif ($period <= 2419200) {
		$title_period = __('Last month');
		$time_format = 'M j';
	} 
	else {
		$title_period = __('Last %s days', format_numeric (($period / (3600 * 24)), 2));
		$time_format = 'M j';
	}	
	
	// Aplying linear regression to module data in order to do the prediction	
	$output_data = array();
	// Create data in graph format like
	while ($in_range){	
		$timestamp_f = date($time_format, $current_ts);
		$output_data[$timestamp_f] = ($a + ($b * $current_ts));
		// Using this function for prediction_date
		if ($prediction_period == false){
			// This statements stop the prediction when interval is greater than 4 years
			if ($current_ts - $last_timestamp >= 126144000){
				return false;
			} 
			//html_debug_print(" Date " . $timestamp_f . " data: " . $output_data[$timestamp_f]);
			// Found it
			if ($max_value >= $output_data[$timestamp_f] and $min_value <= $output_data[$timestamp_f]){
				return $current_ts;
			}
		}else if ($current_ts > $limit_timestamp){
			$in_range = false;
		}
		$current_ts = $current_ts + $agent_interval;
	}	

	return $output_data;
}

/**
 * Return a date when the date interval is reached
 *
 * @param int Module id.
 * @param int Given data period to make the prediction
 * @param int Max value in the interval.
 * @param int Min value in the interval. 
 * 
 * @return mixed timestamp with the prediction date or false
 */
function forecast_prediction_date ($module_id, $period = 5184000, $max_value = 0, $min_value = 0){
	// Checks interval
	if ($min_value > $max_value){
		return false;
	}	
	
	return forecast_projection_graph($module_id, $period, false, $max_value, $min_value);
}
