CREATE TABLE typecho_meting (
    id binary(40) NOT NULL,
    value TEXT NOT NULL,
    date int(11) NOT NULL
)ENGINE=MYISAM  DEFAULT CHARSET=utf8;
ALTER TABLE typecho_meting ADD UNIQUE(id);
