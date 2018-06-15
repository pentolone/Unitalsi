load data local infile '../source_data/ricevute_fiscali.csv' INTO TABLE ricevute FIELDS terminated BY ','
OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' IGNORE 1 LINES (@col1,
						@col2,
						@col3,
						@col4,
						@col5,
						@col6,
						@col7,
						@col8,
						@col9,
						@col10,
						@col11
)
      set id_sottosezione=@col1,
      n_ricevuta=@col3,
      data_ricevuta=CONCAT(SUBSTR(@col4,1,4), '-', SUBSTR(@col4,5,2),
                           '-',SUBSTR(@col4,7,2)),

      id_socio=(SELECT id FROM anagrafica where n_socio=@col5),
      n_socio=@col5,
      id_gruppo=@col6,
      id_acconto=@col7,
      id_luogo=@col8,
      data_viaggio=@col9,
      causale=@col10,
      importo=@col11;
