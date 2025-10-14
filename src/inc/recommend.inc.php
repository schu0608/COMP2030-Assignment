<?php
function recommend_helpers(PDO $pdo, int $me, int $limit = 12, bool $onlyMyZone = false): array {
  $sql = "
    WITH me_req AS (
      SELECT ss.skill_id
      FROM student_skills ss
      WHERE ss.student_id = :me AND ss.role = 'requested'
    ),
    me_hist AS (
      SELECT DISTINCT t.skill_id
      FROM transactions t
      WHERE t.requester_id = :me OR t.provider_id = :me
    ),
    me_zone AS (
      SELECT zone_id FROM students WHERE student_id = :me
    )
    SELECT
      prov.student_id   AS provider_id,
      prov.full_name    AS provider_name,
      prov.zone_id,
      z.name            AS zone_name,
      s.skill_id,
      s.name            AS skill_name,
      s.category,
      (
        (CASE WHEN mr.skill_id IS NOT NULL THEN 2 ELSE 0 END) +
        (CASE WHEN mh.skill_id IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN prov.zone_id IS NOT NULL AND prov.zone_id = mz.zone_id THEN 1 ELSE 0 END) +
        COALESCE(LOG10(sp.uses + 1), 0)
      ) AS score
    FROM students prov
    JOIN student_skills pss ON pss.student_id = prov.student_id AND pss.role = 'offered'
    JOIN skills s           ON s.skill_id = pss.skill_id
    LEFT JOIN me_req mr     ON mr.skill_id = pss.skill_id
    LEFT JOIN me_hist mh    ON mh.skill_id = pss.skill_id
    LEFT JOIN me_zone mz    ON TRUE
    LEFT JOIN skill_popularity sp ON sp.skill_id = s.skill_id
    LEFT JOIN zones z            ON z.zone_id = prov.zone_id
    WHERE prov.student_id <> :me
      AND prov.active = 1
  ";

  if ($onlyMyZone) {
    $sql .= " AND prov.zone_id = (SELECT zone_id FROM students WHERE student_id = :me) ";
  }

  $sql .= " GROUP BY prov.student_id, s.skill_id
            ORDER BY score DESC, s.category, s.name
            LIMIT :lim";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':me', $me, PDO::PARAM_INT);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
