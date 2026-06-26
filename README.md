HOW TO RUN A WEEKLY TOURNAMENT

■ https://lifehashes.net/hashwar-scheduler/weekly-overview.php

	1. Retrieve NIST Pulse
	2. Apply Glyph filters (optional)
	3. Click "RND DRAW" on Sunday

	This will set up a new series with a unique id in the database

:: PHASE 1 (GROUP ROUND ROBINS) ::

■ https://lifehashes.net/adversarial-conway/

	1. Execute with the unique series id for phase 1 and groups A-D
	   initSeriesTourney([ID], "round-robin", 1, "A")

■ https://lifehashes.net/hashwar-scheduler/weekly-overview.php?series_id=[ID]

	1. Refresh until Phase I is completed
	2. Execute
	   commitToPhase2()

:: PHASE 2 (REDEMPTION DAY KNOCK-OUT) ::

■ https://lifehashes.net/adversarial-conway/

	1. Execute with the unique series id for phase 2 and groups A-D
	   initSeriesTourney([ID], "knock-out", 2, "A")
	   
	   !! WARNING !! Never execute this multiple times (it will create entries every time)

:: PHASE III (FINALE) ::

■ https://lifehashes.net/hashwar-scheduler/weekly-workbench.php?series_id=[ID]

	1. After Phase II is completed and populated, 
	   a. first fetch the NIST Randomness Beacon pulse, 
	   b. then execute
	      compileFinalTourneyParticipants()

■ https://lifehashes.net/adversarial-conway/

	1. Execute with the unique series id for phase 3
	   initSeriesTourney([ID], "knock-out", 3, "f")
