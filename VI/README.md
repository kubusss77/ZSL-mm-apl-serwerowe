```mysql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  is_verified TINYINT(1) DEFAULT 0
);
```

```mysql
CREATE TABLE tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token_hash VARCHAR(255) NOT NULL,
  type ENUM('reset_password', 'verify_email') NOT NULL,
  userid INT NOT NULL,
  is_verified BOOLEAN NOT NULL,
  FOREIGN KEY (userid) REFERENCES users(id) ON DELETE CASCADE
);
```

```mysql
CREATE TABLE entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userid INT NOT NULL,
  entry_date DATE,
  content TEXT,
  FOREIGN KEY (userid) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY user_date_idx (userid, entry_date)
);
```
