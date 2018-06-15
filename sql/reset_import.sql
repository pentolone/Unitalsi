delete from attivita_detail where history=1;
delete from pellegrinaggi where history=1;
delete from attivita_m where history=1;
update BOVIAG set history=0 where history=1;
