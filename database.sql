create database if not exists thefacebook;
use thefacebook;

create table users (
id int auto_increment primary key ,
email varchar(255) not null unique,
password varchar(100) not null,
first_name varchar(100) not null,
last_name varchar(100) not null,
gender enum('male', 'female') not null,
birthday date,
looking_for enum('Friendship', 'Dating ', 'A Relationship', 'whatever') default 'whatever',
interested_in enum (' Single' , 'In a Relationshiop', ' Married','Its Complicated') DEFAULT NULL,
political_view varchar(100),
intrests text,
favorite_music text,
favouite_book text,
favorite_movies text,
about_me text,
profile_pic varchar(255) default 'default.jpg',
joined_date timestamp default current_timestamp 
);


create table friends(
id int auto_increment primary key,
user_id int not null,
friend_id int not null,
status enum ('pending', ' accepted ') default 'pending',
requested_date timestamp default current_timestamp,
foreign key (user_id )references user(id) on delete cascade,
foreign key (friend_id) references user(id) on delete cascade,
unique key unique_friendship (user_id, friend_id)
);

create table wall_post(
id int auto_increment primary key,
user_id int not null , 
wall_owner_id int not null , 
post_content text not null , 
post_date timestamp default current_timestamp ,
foreign key (user_id) references users(id) on delete cascade ,
foreign key (wall_owner_id) references users(id) on delete cascade
);

INSERT INTO users (email, password, first_name, last_name, gender, birthday) 
VALUES 
('mark@harvard.edu', MD5('password123'), 'Mark', 'Zuckerberg', 'Male', '1984-05-14'),
('eduardo@harvard.edu', MD5('password123'), 'Eduardo', 'Saverin', 'Male', '1982-03-19');users

