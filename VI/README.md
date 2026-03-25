``` mysql
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL);
```

``` mysql
CREATE TABLE tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token_hash VARCHAR(255) NOT NULL,
  type ENUM('reset_password', 'verify_email') NOT NULL,
  userid INT NOT NULL,
  FOREIGN KEY (userid) REFERENCES users(id) ON DELETE CASCADE
);
```