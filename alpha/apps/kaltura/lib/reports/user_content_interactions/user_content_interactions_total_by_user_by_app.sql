SELECT COUNT(DISTINCT user_id) unique_known_users,
	SUM(count_plays) count_plays,
	SUM(count_edit) count_edit,
	SUM(count_viral) count_viral,
	SUM(count_download) count_download,
	SUM(count_report) count_report
	( SUM(count_plays_100) / SUM(count_plays) ) play_through_ratio
FROM 
	dwh_hourly_events_entry_user_app ev, dwh_dim_pusres us, dwh_dim_application ap
WHERE 	{OBJ_ID_CLAUSE}
	AND ev.partner_id =  {PARTNER_ID} # PARTNER_ID
	AND ev.partner_id = us.partner_id
	AND us.name = {PUSER_ID}
	AND ap.application_id = ev.application_id
	AND name = {APPLICATION_NAME} 
	AND date_id BETWEEN IF({TIME_SHIFT}>0,(DATE({FROM_DATE_ID}) - INTERVAL 1 DAY)*1, {FROM_DATE_ID})  
    			AND     IF({TIME_SHIFT}<=0,(DATE({TO_DATE_ID}) + INTERVAL 1 DAY)*1, {TO_DATE_ID})
			AND hour_id >= IF (date_id = IF({TIME_SHIFT}>0,(DATE({FROM_DATE_ID}) - INTERVAL 1 DAY)*1, {FROM_DATE_ID}), IF({TIME_SHIFT}>0, 24 - {TIME_SHIFT}, ABS({TIME_SHIFT})), 0)
			AND hour_id < IF (date_id = IF({TIME_SHIFT}<=0,(DATE({TO_DATE_ID}) + INTERVAL 1 DAY)*1, {TO_DATE_ID}), IF({TIME_SHIFT}>0, 24 - {TIME_SHIFT}, ABS({TIME_SHIFT})), 24)
	AND 
		( count_plays > 0 OR
		  count_edit > 0 OR
		  count_viral > 0 OR
		  count_download > 0 OR
		  count_report > 0 )