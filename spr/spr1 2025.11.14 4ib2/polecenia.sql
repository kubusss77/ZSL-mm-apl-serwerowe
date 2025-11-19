create database rezerwacje;
use rezerwacje;

create table sale(id int primary key auto_increment, nazwa varchar(30), pojemnosc int);
create table rezerwacje (id int primary key auto_increment, id_sali int, osoba_rezerwujaca varchar(100), data_rezerwacji date, opis varchar(200), status enum('aktywny', 'anulowany', 'zakonczony'));
alter table rezerwacje add foreign key (id_sali) references sale(id);

insert into sale (nazwa, pojemnosc) values ('Sala chemiczna', 38);
insert into sale (nazwa, pojemnosc) values ('Sala informatyczna', 18);
insert into sale (nazwa, pojemnosc) values ('Świetlica', 60);
insert into sale (nazwa, pojemnosc) values (concat('Sala ', '309'), 36);

insert into rezerwacje (id_sali, osoba_rezerwujaca, data_rezerwacji, opis, status) values ('4', 'Jakub Szczepa', curdate(), 'Sprawdzian z aplikacji serwerowych', 'aktywny');
insert into rezerwacje (id_sali, osoba_rezerwujaca, data_rezerwacji, opis, status) values ('1', 'Jan Kowalski', curdate(), 'Zajęcia z chemii', 'aktywny');
insert into rezerwacje (id_sali, osoba_rezerwujaca, data_rezerwacji, opis, status) values ('3', 'Karol Nowak', '2025-11-15', 'Wykład', 'zakonczony');

