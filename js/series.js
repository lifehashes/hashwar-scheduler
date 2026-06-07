class Series{

    constructor(seriesId, seriesDesignation){

        this.seriesId = seriesId;
        this.seriesDesignation = seriesDesignation;

    }

    /**
     * 1. Selection & Grouping
     * Takes an array of Glyphs, selects n, and splits into m groups.
     */
    assignGroups(glyphPool, n, m){

        // select n Glyphs from the pool and assign them to m sets of equal size

    }

    /**
     * 2. Tournament Instantiation
     * Triggers the creation of a tournament and links it to the series phase.
     */
    async initiatePhase(phaseId, tournamentType, groupLabel){

        // POST to save_tournament.php
        // Record the resulting tournament_id in the 'series' table        

    }

    /**
     * 3. Advancement Logic
     * Query results for the current phase, determine who meets the criteria.
     */
    async evaluateAdvancement(phaseId) {
        // Query 'matches' table joined with 'series' participant records
        // Update 'series' table status from 'active' to 'advanced'
    }

    /**
     * 4. Series Conclusion
     * Final evaluation of the 'series' records to declare the winner.
     */
    async declareWinner() {
        // Filter for phase_id = Max (Finale)
        // Identify top performer
    }

}