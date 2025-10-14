<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';

@ini_set('display_errors', '0');
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

try {
  $q        = trim($_GET['q'] ?? '');
  $category = trim($_GET['category'] ?? '');
  $year     = trim($_GET['year'] ?? '');
  $sort     = ($_GET['sort'] ?? '') === 'rating' ? 'rating' : 'new';

  $filters = [];
  $params  = [];

  if ($q !== '')        { $filters[] = '(s.name LIKE :q OR s.description LIKE :q OR st.full_name LIKE :q OR ss.details LIKE :q)'; $params[':q'] = "%$q%"; }
  if ($category !== '') { $filters[] = 's.category = :c';  $params[':c'] = $category; }
  if ($year !== '')     { $filters[] = 'st.academic_year = :y'; $params[':y'] = $year; }

  $extra = $filters ? ' AND '.implode(' AND ', $filters) : '';

  $BASE =
    ' FROM student_skills ss
      JOIN skills   s  ON s.skill_id    = ss.skill_id
      JOIN students st ON st.student_id = ss.student_id ';

  $sqlOffWithRatings =
      'SELECT
         ss.id AS offer_id, s.skill_id, s.name, s.category, s.description AS skill_desc,
         st.student_id AS provider_id, st.full_name, st.academic_year, ss.details,
         COALESCE((SELECT AVG(stars) FROM reviews r WHERE r.reviewee_id = st.student_id),0) AS avg_rating,
         COALESCE((SELECT COUNT(*)   FROM reviews r WHERE r.reviewee_id = st.student_id),0) AS rating_count'
      .$BASE.' WHERE ss.role = "offered"'.$extra.' ORDER BY '
      .($sort==='rating' ? 'avg_rating DESC, rating_count DESC, ss.id DESC' : 'ss.id DESC')
      .' LIMIT 100';

  $offered = [];
  try {
    $st = db()->prepare($sqlOffWithRatings);
    $st->execute($params);
    $offered = $st->fetchAll();
  } catch (PDOException $e) {
    if ($e->getCode() !== '42S02') { 
      throw $e;
    }
   
    $sqlOffNoRatings =
        'SELECT
           ss.id AS offer_id, s.skill_id, s.name, s.category, s.description AS skill_desc,
           st.student_id AS provider_id, st.full_name, st.academic_year, ss.details,
           0 AS avg_rating, 0 AS rating_count'
        .$BASE.' WHERE ss.role = "offered"'.$extra.' ORDER BY ss.id DESC LIMIT 100';
    $st = db()->prepare($sqlOffNoRatings);
    $st->execute($params);
    $offered = $st->fetchAll();
  }

  $sqlReq =
      'SELECT
         ss.id AS request_id, s.skill_id, s.name, s.category, s.description AS skill_desc,
         st.student_id AS requester_id, st.full_name, st.academic_year, ss.details'
      .$BASE.' WHERE ss.role = "requested"'.$extra.' ORDER BY ss.id DESC LIMIT 100';
  $st = db()->prepare($sqlReq);
  $st->execute($params);
  $requested = $st->fetchAll();

  echo json_encode(['offered' => $offered, 'requested' => $requested], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(200); 
  echo json_encode(['error' => true, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
