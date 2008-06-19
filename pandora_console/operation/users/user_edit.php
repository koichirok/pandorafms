<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnol�gicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {
	
	$view_mode = 0;
	$id_usuario = $_SESSION["id_usuario"];
	
	if (isset ($_GET["ver"])){ // Only view mode, 
		$id = $_GET["ver"]; // ID given as parameter
		if ($id_usuario == $id)
			$view_mode = 0;
		else
			$view_mode = 1;
	}

	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["id_usuario"];
	
	// Get user ID to modify data of current user.

	if (isset ($_GET["modificado"])){
		// Se realiza la modificaci�n
		if (isset ($_POST["pass1"])){
			if ( isset($_POST["nombre"]) && ($_POST["nombre"] != $_SESSION["id_usuario"])) {
				audit_db($_SESSION["id_usuario"],$REMOTE_ADDR,"Security Alert. Trying to modify another user: (".$_POST['nombre'].") ","Security Alert");
				no_permission;
			}
				
			// $nombre = $_POST["nombre"]; // Don't allow change name !!
			$pass1 = entrada_limpia($_POST["pass1"]);
			$pass2 = entrada_limpia($_POST["pass2"]);
			$direccion = entrada_limpia($_POST["direccion"]);
			$telefono = entrada_limpia($_POST["telefono"]);
			$nombre_real = entrada_limpia($_POST["nombre_real"]);
			if ($pass1 != $pass2) {
				echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
			}
			else {echo "<h3 class='suc'>".$lang_label["update_user_ok"]."</h3>";}
			//echo "<br>DEBUG for ".$nombre;
			//echo "<br>Comments:".$comentarios;	
			$comentarios = entrada_limpia($_POST["comentarios"]);
			if (get_user_password($nombre)!=$pass1){
				// Only when change password
				$pass1=md5($pass1);
				$sql = "UPDATE tusuario SET nombre_real = '".$nombre_real."', password = '".$pass1."', telefono ='".$telefono."', direccion ='".$direccion." ', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre."'";
			}
			else 
				$sql = "UPDATE tusuario SET nombre_real = '".$nombre_real."', telefono ='".$telefono."', direccion ='".$direccion." ', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre."'";
			$resq2=mysql_query($sql);
			
			// Ahora volvemos a leer el registro para mostrar la info modificada
			// $id is well known yet
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];			
		}
		else {
			echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
		}
	} 
		echo "<h2>".$lang_label["users_"]." &gt; ";
		echo $lang_label["user_edit_title"]."</h2>";

	// Si no se obtiene la variable "modificado" es que se esta visualizando la informacion y
	// preparandola para su modificacion, no se almacenan los datos
	
	$nombre=$rowdup["id_usuario"];
	if ($view_mode == 0)
		$password=$rowdup["password"];
	else 	
		$password="This is not a good idea :-)";
	
	$comentarios=$rowdup["comentarios"];
	$direccion=$rowdup["direccion"];
	$telefono=$rowdup["telefono"];
	$nombre_real=$rowdup["nombre_real"];

	?>
	<table cellpadding="4" cellspacing="4" class="databox_color" width="500px">
	<?php 
	if ($view_mode == 0) 
		echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&sec2=operation/users/user_edit&ver='.$id_usuario.'&modificado=1">';
	else 	
		echo '<form name="user_mod" method="post" action="">';
	?>
	<tr>
	<td class="datos"><?php echo $lang_label["id_user"] ?></td>
	<td class="datos"><input class=input type="text" name="nombre" value="<?php echo $nombre ?>" disabled></td>
	<tr>
	<td class="datos2"><?php echo $lang_label["real_name"] ?></td>
	<td class="datos2">
	<input class=input type="text" name="nombre_real" value="<?php echo $nombre_real ?>"></td>
	<tr><td class="datos"><?php echo $lang_label["password"] ?></td>
	<td class="datos">
	<input class=input type="password" name="pass1" value="<?php echo $password ?>"></td>
	<tr><td class="datos2">
	<?php echo $lang_label["password"]; echo " ".$lang_label["confirmation"]?>
	<td class="datos2">
	<input class=input type="password" name="pass2" value="<?php echo $password ?>"></td>
	<tr>
	<td class="datos">E-Mail
	<td class="datos">
	<input class=input type="text" name="direccion" size="40" value="<?php echo $direccion ?>">
	<tr>
	<td class="datos2"><?php echo $lang_label["telefono"] ?>
	<td class="datos2"><input class=input type="text" name="telefono" value="<?php echo $telefono ?>">
	<tr><td class="datos" colspan="2"><?php echo $lang_label["comments"] ?>
	<tr><td class="datos2" colspan="2"><textarea name="comentarios" cols="55" rows="4"><?php echo $comentarios ?></textarea>
	</table>
	<table cellpadding="4" cellspacing="4" width="500px">
	
<?php
		// Don't delete this!!
	if ($view_mode ==0){
		echo '<tr><td colspan="3" align="right">';
		echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'></td></tr>";
	}
	echo '</table></form><br>';
	echo '<h3>'.$lang_label["listGroupUser"].'</h3>';
	echo "<table width='500' cellpadding='4' cellspacing='4' class='databox'>";
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
	$result=mysql_query($sql1);
	if (mysql_num_rows($result)){
		echo '<tr>';
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos2";
				$color = 0;
				}
			else {
				$tdcolor = "datos";
				$color = 1;
			}
			echo '<td class="'.$tdcolor.'">';
			echo "<b>".dame_perfil($row["id_perfil"])."</b> / ";
			echo "<b>".dame_grupo($row["id_grupo"])."</b><tr>";	
		}
	} else { 
		echo '<div class="nf">'.$lang_label["no_profile"].'</div>'; 
	}
	echo '</table>';
} // fin pagina

?>
