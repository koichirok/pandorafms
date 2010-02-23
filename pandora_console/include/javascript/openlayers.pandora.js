// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

var map; // Map global var object is use in multiple places.

/**
 * Inicialize the map in the browser and the object map.
 * 
 * @param string id_div The id of div to draw the map.
 * @param integer initial_zoom The initial zoom to show the map.
 * @param integer num_levels_zoom The numbers of zoom levels.
 * @param float center_latitude The coord of latitude for center.
 * @param float center_longitude The coord of longitude for center.
 * @param array objBaseLayers The array of baselayers with number index, and the baselayer is another asociative array that content 'type', 'name' and 'url'.
 * @param array arrayControls The array of enabled controls, the controls is: 'Navigation', 'MousePosition', 'OverviewMap', 'PanZoom', 'PanZoomBar', 'ScaleLine', 'Scale'
 * 
 * @return None
 */
function js_printMap(id_div, initial_zoom, num_levels_zoom, center_latitude, center_longitude, objBaseLayers, arrayControls) {
	$(document).ready (
		function () {
			
			controlsList = [];
				
			for (var controlIndex in arrayControls) {
				if (isInt(controlIndex)) {
					switch (arrayControls[controlIndex]) {
						case 'Navigation':
							controlsList.push(new OpenLayers.Control.Navigation());
							break;
						case 'MousePosition':
							controlsList.push(new OpenLayers.Control.MousePosition());
							break;
						case 'OverviewMap':
							controlsList.push(new OpenLayers.Control.OverviewMap());
							break;
						case 'PanZoom':
							controlsList.push(new OpenLayers.Control.PanZoom());
							break;
						case 'PanZoomBar':
							controlsList.push(new OpenLayers.Control.PanZoomBar());
							break;
						case 'ScaleLine':
							controlsList.push(new OpenLayers.Control.ScaleLine());
							break;
						case 'Scale':
							controlsList.push(new OpenLayers.Control.Scale());
							break;
					}
				}
			}
				
			map = new OpenLayers.Map (id_div, {
				controls: controlsList,
				maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
				maxResolution: 156543.0399,
				numZoomLevels: num_levels_zoom,
				units: 'm', //metros
				projection: new OpenLayers.Projection("EPSG:900913"),
				displayProjection: new OpenLayers.Projection("EPSG:4326")
			});

			//Define the maps layer
			for (var baselayerIndex in objBaseLayers) {
				if (isInt(baselayerIndex)) {
					switch (objBaseLayers[baselayerIndex]['type']) {
						case 'OSM':
							var baseLayer = new OpenLayers.Layer.OSM(objBaseLayers[baselayerIndex]['name'], 
									objBaseLayers[baselayerIndex]['url'], {numZoomLevels: num_levels_zoom});
							map.addLayer(baseLayer);
							break;
					}
				}
			}

			if( ! map.getCenter() ){
				var lonLat = new OpenLayers.LonLat(center_longitude, center_latitude)
					.transform(map.displayProjection, map.getProjectionObject());
				map.setCenter (lonLat, initial_zoom);
			}
		}
	);
}

/**
 * Change the refresh time for the map.
 * 
 * @param int time seconds
 * @return none
 */
function changeRefreshTime(time) {
	refreshAjaxIntervalSeconds = time * 1000;
}

/**
 * Make the layer in the map.
 * 
 * @param string name The name of layer, it's show in the toolbar of layer.
 * @param boolean visible Set visible the layer.
 * @param array dot It's a asociative array that have 'url' of icon image, 'width' in pixeles and 'height' in pixeles. 
 * 
 * @return object The layer created.
 */
function js_makeLayer(name, visible, dot) {
	if (dot == null) {
		dot = Array();
		dot['url'] = 'images/dot_green.png';
		dot['width'] = 20; //11;
		dot['height'] = 20; //11;
	}

	//Set the style in layer
	var style = new OpenLayers.StyleMap(
		{fontColor: "#ff0000",
			labelYOffset: - dot['height'],
			graphicHeight: dot['height'], 
			graphicWidth: dot['width'], 
			externalGraphic: dot['url'],
			label:"${nombre}"
		}
	);
			
	//Make the layer as type vector
	var layer = new OpenLayers.Layer.Vector(name, {styleMap: style});

    layer.setVisibility(visible);
	map.addLayer(layer);
	
	//Disable for WIP
			/////
//			layer.events.on({
//	                "beforefeaturemodified": test,
//	                "featuremodified": test,
//	                "afterfeaturemodified": test,
//	                "vertexmodified": test,
//	                "sketchmodified": test,
//	                "sketchstarted": test,
//	                "sketchcomplete": test
//	            });
			/////


//            layer.events.on({
//                "featureselected": function(e) {
//                	if (e.feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
//                    	var feature = e.feature;
//                		var featureData = feature.data;
//                		var long_lat = featureData.long_lat;
//
//						var popup;
//						
//        	            popup = new OpenLayers.Popup.FramedCloud('cloud00',
//            	            	long_lat,
//								null,
//								'<div class="cloudContent' + featureData.id + '" style="text-align: center;"><img src="images/spinner.gif" /></div>',
//								null,
//								true,
//								function () { popup.destroy(); });
//								feature.popup = popup;
//								map.addPopup(popup);
//
//                		jQuery.ajax ({
//                    		data: "page=operation/gis_maps/ajax&opt="+featureData.type+"&id="  + featureData.id,
//                    		type: "GET",
//                    		dataType: 'json',
//                    		url: "ajax.php",
//                    		timeout: 10000,
//                    		success: function (data) {                 		
//                    			if (data.correct) {
//                    				$('.cloudContent' + featureData.id).css('text-align', 'left');
//									$('.cloudContent' + featureData.id).html(data.content);
//									popup.updateSize();
//                    			}
//                			}
//                		});
//                	}
//                }
//            });
			
	return layer;
}

/**
 * Active and set callbacks of events.
 * 
 * @param callbackFunClick Function to call when the user make single click in the map. 
 * 
 * @return None
 */
function js_activateEvents(callbackFunClick) {
	/**
	 * Pandora click openlayers object.
	 */
	OpenLayers.Control.PandoraClick = OpenLayers.Class(OpenLayers.Control, {                
	    defaultHandlerOptions: {
	        'single': true,
	        'double': false,
	        'pixelTolerance': 0,
	        'stopSingle': false,
	        'stopDouble': false
	    },
	    initialize: function(options) {
	    	this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
	    	OpenLayers.Control.prototype.initialize.apply(this, arguments); 
	    	this.handler = new OpenLayers.Handler.Click(this, {'click': options.callbackFunctionClick}, this.handlerOptions);
	    }
	});
	
	var click = new OpenLayers.Control.PandoraClick({callbackFunctionClick: callbackFunClick});
    
	map.addControl(click);
    click.activate();
}

/**
 * Test the value is a int.
 * 
 * @param mixed X The val that test to if is int.
 * 
 * @return Boolean True if it's int.
 */
function isInt(x) {
	var y=parseInt(x);
	if (isNaN(y)) return false;
	return x==y && x.toString()==y.toString();
}

/**
 * Set the visibility of a layer
 * 
 * @param string name The name of layer.
 * @param boolean action True or false
 */
function showHideLayer(name, action) {
	var layer = map.getLayersByName(name);

	layer[0].setVisibility(action);
}

/**
 * Add a point with the default icon in the map.
 * 
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * 
 * @return Object The point.
 */
function js_addPoint(layerName, pointName, lon, lat, id, type_string) {
	var point = new OpenLayers.Geometry.Point(lon, lat)
		.transform(map.displayProjection, map.getProjectionObject());

	var layer = map.getLayersByName(layerName);
	layer = layer[0];

	feature = new OpenLayers.Feature.Vector(point,{nombre: pointName, id: id, type: type_string, long_lat: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject()) });
	
	layer.addFeatures(feature);
	
	return feature;
}

/**
 * Add a point and set the icon in the map.
 * 
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string icon Url of icon image. 
 * @param integer width The width of icon.
 * @param integer height The height of icon.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * 
 * @return Object The point.
 */
function js_addPointExtent(layerName, pointName, lon, lat, icon, width, height, id, type_string) {
	var point = new OpenLayers.Geometry.Point(lon, lat)
	.transform(map.displayProjection, map.getProjectionObject());

	var layer = map.getLayersByName(layerName);
	layer = layer[0];
	
	feature = new OpenLayers.Feature.Vector(point,{id: id, type: type_string, long_lat: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject()) }, {fontWeight: "bolder", fontColor: "#00014F", labelYOffset: -height, graphicHeight: width, graphicWidth: height, externalGraphic: icon, label: pointName});
	
	layer.addFeatures(feature);
	
	return feature;
}

/**
 * 
 * @param string layerName The name of layer to put the point path.
 * @param float lon The coord of latitude for point path.
 * @param float lat The coord of longitude for point path.
 * @param string color The color of point path in rrggbb format.
 * @param boolean manual The type of point path, if it's manual, the point is same a donut.
 * @param string id The id of point path.
 * 
 * @return None
 */
function js_addPointPath(layerName, lon, lat, color, manual, id) {
	var point = new OpenLayers.Geometry.Point(lon, lat)
		.transform(map.displayProjection, map.getProjectionObject());
	
	var layer = map.getLayersByName(layerName);
	layer = layer[0];

	var pointRadiusNormal = 4;
	var strokeWidth = 2;
	var pointRadiusManual = pointRadiusNormal - (strokeWidth / 2); 
	
	if (manual) {
		point = new OpenLayers.Feature.Vector(point,{estado: "ok", id: id, type: "point_path_info", 
			long_lat: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject())},
			{fillColor: "#ffffff", pointRadius: pointRadiusManual, stroke: 1, strokeColor: color, strokeWidth: strokeWidth, cursor: "pointer"}
		);
	}
	else {
		point = new OpenLayers.Feature.Vector(point,{estado: "ok", id: id, type: "point_path_info",
			long_lat: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject())},
				{fillColor: color, pointRadius: pointRadiusNormal, cursor: "pointer"}
		);
	}

	layer.addFeatures(point);
}

/**
 * Draw the lineString.
 * 
 * @param string layerName The name of layer to put the point path.
 * @param Array points The array have content the points, but the point as lonlat openlayers object without transformation.
 * @param string color The color of point path in rrggbb format.
 * 
 * @return None
 */
function js_addLineString(layerName, points, color) {
	var mapPoints = new Array(points.length);
	var layer = map.getLayersByName(layerName);

	layer = layer[0];
	
	for (var i = 0; i < points.length; i++) {
		mapPoints[i] = points[i].transform(map.displayProjection, map.getProjectionObject());
	}
	
	var lineString = new OpenLayers.Feature.Vector(
		new OpenLayers.Geometry.LineString(mapPoints),
		null,
		{ strokeWidth: 2, fillOpacity: 0.2, fillColor: color, strokeColor: color}
	);

	layer.addFeatures(lineString);
}

/**
 * Return feature object for a id agent passed.
 * 
 * @param interger id The agent id.
 * @return mixed Return the feature object, if it didn't found then return null.
 */
function searchPointAgentById(id) {
	for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
		layer = map.layers[layerIndex];

		if (layer.features != undefined) {
			for (featureIndex = 0; featureIndex < layer.features.length; featureIndex++) {
				feature = layer.features[featureIndex];
				if (feature.data.id == id) {
					return feature;
				}
			}
		}
	}

	return null;
}