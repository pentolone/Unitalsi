load data local infile '../source_data/categoria.csv' INTO TABLE categoria FIELDS terminated BY ',' LINES TERMINATED BY '\n' (@col1, @col2) set id=@col1, descrizione=@col2;
