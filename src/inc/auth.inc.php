<?php
function current_user_id(): ?int { return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
function require_login(): int { $uid = current_user_id(); if(!$uid){ http_response_code(401); exit('Auth required'); } return $uid; }