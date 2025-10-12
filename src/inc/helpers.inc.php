<?php
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function json_out($data, int $code=200): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); }
function redirect(string $path): never { header('Location: '.$path); exit; }