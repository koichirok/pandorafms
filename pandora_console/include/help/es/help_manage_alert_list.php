<?php
/**
 * @package Include/help/es
 */
?>
<h1>Alerts</h1>

<p>
Las Alertas en Pandora FMS reacionan a un valor "fuera de rango" de un módulo. La alerta consiste en enviar un e-mail o un SMS al administrador, enviando un trap SNMP, escribir el indcidenete en el log del sistema en el fichero de log de Pandora FMS, etc. Basicamente, una alerta puede ser cualquier cosa que pueda ser disparada por un script configurado en el Sistema Operativo donde los servidores de Pandora FMS se ejecutan.
</p>

<p>
Cuando una alerta es creada los siguiente campos deben de rellenarse:
</p>

<ul>
	<li>Agent name: El nombre del agente asociado a la alarma.</li>
	<li>Module: La alerta recogerá el valor del módulo y comprobará si está "fuera de rango". En caso afirmativo creará un evento (sending, e-mail, etc.).</li>
	<li>Template: Alertas con todos los parámetros predefinidos. Son usadas para hacer más sencilla la gestión de las alertas porel administrador.</li>
	<li>Action: Permite elegir entre todas las alertas que están configuradas. La acción seleccionada será añadida a la acción definida por el template.</li>
</ul>
