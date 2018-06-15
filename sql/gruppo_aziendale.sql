load data local infile '../source_data/gruppo_aziendale.csv' INTO TABLE gruppo FIELDS terminated BY ',' LINES TERMINATED BY '\n' (@col1, @col2) set id=@col1, id_sottosezione=17, descrizione=@col2;
