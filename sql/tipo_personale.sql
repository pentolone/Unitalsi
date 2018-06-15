load data local infile '../source_data/tipo_personale.csv' INTO TABLE tipo_personale FIELDS terminated BY ',' LINES TERMINATED BY '\n' (@col1, @col2) set id=@col1, descrizione=@col2;
