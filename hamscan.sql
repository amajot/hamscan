CREATE TABLE hamScan_swap_qth (
`id` INT NOT NULL PRIMARY KEY,
search_term varchar(100) not null,
title varchar(2000) not null,
img_url varchar(200) null,
description varchar(2000) not null,
category varchar(25) null,
url varchar(200) null,
last_update TIMESTAMP not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
)ENGINE=innodb;
