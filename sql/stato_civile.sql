load data local infile '../source_data/stato_civile.csv' INTO TABLE stato_civile FIELDS terminated BY ',' LINES TERMINATED BY '\n' (@col1, @col2) set id=@col1, descrizione=@col2;
