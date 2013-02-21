/*
Pandora FMS - http://pandorafms.com

==================================================
Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
Please see http://pandorafms.org for full contribution list

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation; version 2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

/**
 * Activity of help view.
 * 
 * @author Miguel de Dios Matías
 * 
 */

package pandorafms.pandorafmsandroidconsole;

import android.app.Activity;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.view.View;
import android.widget.CheckBox;
import android.widget.TextView;

public class Help extends Activity {
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		setContentView(R.layout.help);
		
		TextView text = (TextView) findViewById(R.id.url_pandora);
		text.setText(Html
				.fromHtml("<a href='http://pandorafms.org/'>PandoraFMS.org</a>"));
		text.setMovementMethod(LinkMovementMethod.getInstance());
	}
}
