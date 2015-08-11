<?php
class conf_Installer {
  function update_1() {
    $q[] = "create table users (
      username varchar(32) primary key not null,
      password varchar(64)
      )";
    $q[] = "create table sessions (
      username varchar(32),
      token_id varchar(64) primary key not null,
      expires INT(11)
      )";
      
    $q[] = "create table friends (
      user1 varchar(32),
      user2 varchar(32),
      primary key (user1, user2 )
      )";
      
    $q[] = "create table friend_requests (
      sender varchar(32),
      receiver varchar(32),
      primary key (sender, receiver)
      )";
      
    $q[] = "create table profiles (
      username varchar(32) primary key not null,
      screen_name varchar(100),
      avatar varchar(100),
      avatar_mimetype varchar(100)
      )";
    
    $q[] = "create table posts (
      post_id int(11) not null auto_increment primary key,
      username varchar(32),
      date_posted INT(11),
      photo varchar(255),
      photo_mimetype varchar(100),
      comment text
      )";
    
    df_q($q);
  }  
}
?>