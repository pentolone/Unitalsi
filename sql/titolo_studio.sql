load data local infile '../source_data/titolo_studio.csv' INTO TABLE titolo_studio FIELDS terminated BY ',' LINES TERMINATED BY '\n' (@col1, @col2) set id=@col1, descrizione=@col2;
