create database film_rentals;
use film_rentals;

create table gatunki(id int primary key auto_increment, nazwa varchar(200))
create table filmy(id int primary key auto_increment, tytul varchar(100), id_gatunku int, data_dodania date, status enum('dostepny', 'wypozyczony'), ilosc_ocen int, srednia_ocen float);
alter table filmy add constraint fk_gatunek foreign key (id_gatunku) references gatunki(id);

insert into gatunki (nazwa) values ('horror');
insert into gatunki (nazwa) values ('obyczajowy');
insert into gatunki (nazwa) values (concat('sci-', 'fi'));
insert into gatunki (nazwa) values ('animacja');

insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Interstellar', '3', '2016-10-10', 'wypozyczony', '2000', '8.4');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Oppenheimer', '3', curdate(), 'dostepny', '1376', '8.1');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Incepcja', '3', '2018-3-25', 'dostepny', '863', '7.4');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Barbie', '3', '2024-10-04', 'wypozyczony', '423', '7.7');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Kler', '2', '2019-02-16', 'wypozyczony', '509', '7.3');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Wesele', '3', '2001', 'dostepny', '125', '6.4');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Obecność 2', '1', '2019-12-09', 'dostepny', '423', '6.1');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('To', '1', '2017-06-01', 'wypozyczony', '327', '6.9');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Gru, Dru i miniionki', '4', '2016-11-22', 'wypozyczony', '972', '7.3');
insert into filmy (tytul, id_gatunku, data_dodania, status, ilosc_ocen, srednia_ocen) values ('Jak ukraść ksiezyc', '4', '2019-06-01', 'dostepny', '633', '7.2');