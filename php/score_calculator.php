<?php

class ScoreCalculator 
{
    private $db;

    public function __construct($dbConnection) 
    {
        $this->db = $dbConnection;
    }

    /**
     * Recalculates scores. Returns execution summary and logs.
     */
    public function recalculate($userId = null): array 
    {
        $logs = [];

        if ($userId) {
            $result = $this->computeUserScore($userId);
            $stmt = $this->db->prepare("UPDATE users SET score = ? WHERE id = ?");
            $stmt->execute([$result['score'], $userId]);
            $logs[$result['username']] = $result;
        } else {
            $stmt = $this->db->query("SELECT id, username FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updateStmt = $this->db->prepare("UPDATE users SET score = ? WHERE id = ?");

            foreach ($users as $user) {
                $result = $this->computeUserScore((int)$user['id'], $user['username']);
                $updateStmt->execute([$result['score'], $user['id']]);
                $logs[$user['username']] = $result;
            }
        }

        return [
            'success' => true,
            'summary' => $logs
        ];
    }

    /**
     * Calculates total score and builds a detailed log breakdown for a given user.
     */
    public function computeUserScore(int $userId, string $username = null): array 
    {
        if (!$username) {
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $username = $stmt->fetchColumn();
            
            if (!$username) return ['score' => 0, 'username' => 'Unknown', 'breakdown' => []];
        }

        $totalScore = 0;
        $breakdown = [];

        // --- RULE 1: Finale Participation Points ---
        $finaleStmt = $this->db->prepare("
            SELECT sp.series_id, sp.glyph_name 
            FROM series_participants sp
            JOIN GLYPHREG g ON sp.glyph_name = g.BATTLE_NAME
            WHERE g.OWNER = ? 
              AND sp.phase_id = 3
        ");
        $finaleStmt->execute([$username]);
        $finaleEntries = $finaleStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($finaleEntries as $entry) {
            $totalScore += 1;
            $breakdown[] = [
                'rule' => 'FINALE_QUALIFICATION',
                'points' => 1,
                'series_id' => $entry['series_id'],
                'glyph_name' => $entry['glyph_name'],
                'message' => "Awarded 1 point for Glyph '{$entry['glyph_name']}' qualifying for the finale in Series #{$entry['series_id']}."
            ];
        }

        // --- RULE 2: Placement Medal Bonuses ---
        $medalsStmt = $this->db->prepare("
            WITH match_scores AS (
                SELECT 
                    m.id AS match_id,
                    m.tournament_id,
                    m.match_designation,
                    m.p1_glyph_name,
                    m.p2_glyph_name,
                    SUM(mr.p1_final_score) AS p1_total,
                    SUM(mr.p2_final_score) AS p2_total
                FROM matches m
                JOIN match_rounds mr ON m.id = mr.match_id
                GROUP BY m.id, m.tournament_id, m.match_designation, m.p1_glyph_name, m.p2_glyph_name
            ),
            placements AS (
                -- Gold
                SELECT 
                    ms.tournament_id,
                    CASE WHEN ms.p1_total > ms.p2_total THEN ms.p1_glyph_name ELSE ms.p2_glyph_name END AS glyph_name, 
                    3 AS points,
                    'Gold Medal' AS medal
                FROM match_scores ms WHERE ms.match_designation LIKE '%MATCH 1/1'

                UNION ALL

                -- Silver
                SELECT 
                    ms.tournament_id,
                    CASE WHEN ms.p1_total < ms.p2_total THEN ms.p1_glyph_name ELSE ms.p2_glyph_name END AS glyph_name, 
                    2 AS points,
                    'Silver Medal' AS medal
                FROM match_scores ms WHERE ms.match_designation LIKE '%MATCH 1/1'

                UNION ALL

                -- Bronze
                SELECT 
                    ms.tournament_id,
                    CASE WHEN ms.p1_total < ms.p2_total THEN ms.p1_glyph_name ELSE ms.p2_glyph_name END AS glyph_name, 
                    1 AS points,
                    'Bronze Medal' AS medal
                FROM match_scores ms WHERE ms.match_designation LIKE '%MATCH 1/2' OR ms.match_designation LIKE '%MATCH 2/2'
            )
            SELECT p.glyph_name, p.points, p.medal, sp.series_id
            FROM placements p
            JOIN series_participants sp ON p.tournament_id = sp.tournament_id AND p.glyph_name = sp.glyph_name
            JOIN GLYPHREG g ON p.glyph_name = g.BATTLE_NAME
            WHERE sp.phase_id = 3
              AND g.OWNER = ?
        ");
        
        $medalsStmt->execute([$username]);
        $medalEntries = $medalsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($medalEntries as $entry) {
            $pts = (int)$entry['points'];
            $totalScore += $pts;
            $breakdown[] = [
                'rule' => 'MEDAL_BONUS',
                'points' => $pts,
                'series_id' => $entry['series_id'],
                'glyph_name' => $entry['glyph_name'],
                'message' => "Awarded {$pts} bonus point(s) ({$entry['medal']}) for Glyph '{$entry['glyph_name']}' in Series #{$entry['series_id']}."
            ];
        }

        return [
            'username' => $username,
            'score' => $totalScore,
            'breakdown' => $breakdown
        ];
    }
}