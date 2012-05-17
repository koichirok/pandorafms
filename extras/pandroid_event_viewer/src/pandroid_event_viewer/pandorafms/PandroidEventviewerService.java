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
package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.IBinder;
import android.util.Log;
/**
 * This service will launch AlarmReceiver periodically.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class PandroidEventviewerService extends Service {
	private static String TAG = "PandroidEventviewerService";
	public AlarmManager alarmM;
	PendingIntent pendingI;

	@Override
	public IBinder onBind(Intent intent) {
		return null;
	}

	public void onCreate() {
		alarmM = (AlarmManager) getSystemService(Context.ALARM_SERVICE);

		Intent intentAlarm = new Intent(this, AlarmReceiver.class);
		this.pendingI = PendingIntent.getBroadcast(this, 0, intentAlarm, 0);

		int sleepTimeAlarm = convertRefreshTimeKeyToTime();

		Log.i(TAG, "sleepTimeAlarm = " + sleepTimeAlarm);

		alarmM.setRepeating(AlarmManager.RTC_WAKEUP,
				System.currentTimeMillis(), sleepTimeAlarm, this.pendingI);
	}
	/**
	 * Converts chosen time from spinner to seconds (either are seconds or not)
	 * @return
	 */
	private int convertRefreshTimeKeyToTime() {
		int returnvar = 60 * 10;

		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		int refreshTimeKey = preferences.getInt("refreshTimeKey", 3);

		switch (refreshTimeKey) {
		case 0:
			returnvar = 30; // 30 seconds
			break;
		case 1:
			returnvar = 60; // 1 minute
			break;
		case 2:
			returnvar = 60 * 5; // 5 minutes
			break;
		case 3:
			returnvar = 60 * 10; // 10 minutes
			break;
		case 4:
			returnvar = 60 * 15; // 15 minutes
			break;
		case 5:
			returnvar = 60 * 30; // 30 minutes
			break;
		case 6:
			returnvar = 60 * 45; // 45 minutes
			break;
		case 7:
			returnvar = 3600; // 1 hour
			break;
		case 8:
			returnvar = 3600 + (60 * 30); // 1 hour and 30 minutes
			break;
		case 9:
			returnvar = 3600 * 2; // 2 hours
			break;
		case 10:
			returnvar = 3600 * 3; // 3 hours
			break;
		case 11:
			returnvar = 3600 * 4; // 4 hours
			break;
		case 12:
			returnvar = 3600 * 6; // 6 hours
			break;
		case 13:
			returnvar = 3600 * 8; // 8 hours
			break;
		case 14:
			returnvar = 3600 * 10; // 10 hours
			break;
		case 15:
			returnvar = 3600 * 12; // 12 hours
			break;
		case 16:
			returnvar = 3600 * 24; // 24 hours
			break;
		case 17:
			returnvar = 3600 * 36; // 36 hours
			break;
		case 18:
			returnvar = 3600 * 48; // 48 hours
			break;
		}

		return returnvar * 1000;
	}

	public void onDestroy() {
		alarmM.cancel(this.pendingI);
	}
}
