-- NexShelfy final customer/admin session and test customer fix
-- Safe to run more than once.
INSERT INTO users (name,email,password_hash,role,status,created_at,updated_at)
VALUES ('User','user@user.com','$2y$12$7C8C6hYsQLXbfr8gSik6SeoUWZfX.tVeBA/NwO7NAU0FpqTTW8b2m','customer','active',NOW(),NOW())
ON DUPLICATE KEY UPDATE
  name=VALUES(name),
  password_hash=VALUES(password_hash),
  role='customer',
  status='active',
  updated_at=NOW();
