<?php
require_once 'xf/db/Database.php';
use xf\db\Database;
$error_message = null;

function error_message() {
  global $error_message;
  return $error_message;
}

function login($username, $password) {
  global $error_message;
  $db = new Database(df_db());
  
  // Check password
  $res = $db->getObject("select count(*) as num from users where username=:user and `password`=:password", 
  array('user' => $username, 'password' => $password));
  
  if (intval($res->num) !== 1) {
    $error_message = "Incorrect username or password";
    return false;
  }
  
  
  
  try {
    $res = $db->query("insert into sessions (username, token_id, expires) values (:user, UUID(), :expires)",
    array('user' => $username, 'expires' => time()+3600));
    $o = $db->getObject("select token_id from sessions where username=:user order by expires desc limit 1", array('user'=>$username));
    return $o->token_id;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
}

function logout($token=null) {
  global $error_message;
  $db = new Database(df_db());
  if (!isset($token)) {
    $token = $_POST['token'];
  }
  if (!$token) {
    $error_message = "No token provided";
    return false;
  }
  
  $res = $db->query("delete from sessions where token_id=:token", array('token' => $token));
  return true;
}

function register($username, $password) {
  global $error_message;
  $db = new Database(df_db());
  try {
    $res = $db->insertObject("users", (object)array('username'=>$username, 'password'=>$password));
    $res = $db->insertObject("profiles", (object)array('username'=>$username, 'screen_name'=>$username));
    return true;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
}

function get_user($token=null) {
  $db = new Database(df_db());
  if (!isset($token)) {
    $token = $_POST['token'];
  }
  if (!$token) {
    return null;
  }
  
  $user = $db->getObject("select username from sessions where token_id=:token_id and expires > :now", array('token_id'=>$token, 'now' => time()));
  if ($user) {
    return $user->username;
  }
  return null;
}

function get_friends($token = null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "Could not find user for this token $token";
    return false;
  }
  
  $out = $db->getObjects("select p.username, p.avatar, p.screen_name 
    from profiles p 
    inner join friends f on ((p.username=f.user1 and f.user2=:user) or (p.username=f.user2 and f.user1=:user))",
  array('user' => $user)
  );
  foreach ($out as $o) {
    $o->avatar = get_avatar_url($o);
  }
  return $out;
}

function send_friend_request($token = null, $friend_username = null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!isset($friend_username)) {
    $friend_username = $_POST['friend_username'];
  }
  
  if (!$friend_username) {
    $error_message = "No friend username supplied";
    return false;
  }
  
  if ($friend_username == $user) {
    $error_message = "Cannot friend oneself";
    return false;
  }
  
  $user1 = min($friend_username, $user);
  $user2 = max($friend_username, $user);
  
  $o = $db->getObject("select count(*) as num from friend_requests fr where fr.sender=:sender and fr.receiver=:receiver", array('sender'=>$user, 'receiver'=>$friend_username));
  if ($o->num > 0) {
    $error_message = "There is already a pending friend request for this friend.";
    return false;
  }
  
  $o = $db->getObject("select count(*) as num from friends fr where fr.user1=:user1 and fr.user2=:user2", array('user1'=>$user1, 'user2'=>$user2));
  if ($o->num > 0) {
    $error_message = "You are already friends with this person";
    return false;
  }
  
  try {
    $db->insertObject("friend_requests", (object)array('sender'=>$user, 'receiver'=>$friend_username));
    return true;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
}

function accept_friend_request($token=null, $friend_username=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!$friend_username) {
    $friend_username = $_POST['friend_username'];
  }
  
  if (!$friend_username) {
    $error_message = "No friend username supplied";
    return false;
  }
  
  if ($friend_username == $user) {
    $error_message = "Cannot friend oneself";
    return false;
  }
  
  $user1 = min($friend_username, $user);
  $user2 = max($friend_username, $user);
  
  $request = $db->getObject("select * from friend_requests fr where fr.sender=:sender and fr.receiver=:receiver", array('sender'=>$friend_username, 'receiver'=>$user));
  if ($request) {
    try {
      $db->insertObject("friends", (object)array('user1'=>$user1, 'user2'=>$user2));
      $db->query("delete from friend_requests where sender=:sender and receiver=:receiver", array('sender'=>$friend_username, 'receiver'=>$user));
      return true;
    } catch (Exception $ex) {
      $error_message = $ex->getMessage();
      return false;
    }
  } else {
    $error_message = "No pending request for that friend was found.";
    return false;
  }
  
}

function decline_friend_request($token=null, $friend_username=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!$friend_username) {
    $friend_username = $_POST['friend_username'];
  }
  
  if (!$friend_username) {
    $error_message = "No friend username supplied";
    return false;
  }
  
  if ($friend_username == $user) {
    $error_message = "Cannot friend oneself";
    return false;
  }
  
  $user1 = min($friend_username, $user);
  $user2 = max($friend_username, $user);
  
  $request = $db->getObject("select * from friend_requests fr where fr.sender=:sender and fr.receiver=:receiver", array('sender'=>$friend_username, 'receiver'=>$user));
  if ($request) {
    try {
      $db->query("delete from friend_requests where sender=:sender and receiver=:receiver", array('sender'=>$friend_username, 'receiver'=>$user));
      return true;
    } catch (Exception $ex) {
      $error_message = $ex->getMessage();
      return false;
    }
  } else {
    $error_message = "No pending request for that friend was found.";
    return false;
  }
  
}

function get_pending_friend_requests($token=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  try {
    $out = $db->getObjects("select fr.sender, fr.receiver, p.avatar, p.screen_name from friend_requests fr left join profiles p on fr.sender=p.username where fr.receiver=:receiver", array('receiver'=>$user));
    foreach ($out as $o) {
      $o->username = $o->sender;
      $o->avatar = get_avatar_url($o);
    }
    return $out;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
  
}

function unfriend($token=null, $friend_username=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user($token);
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!isset($friend_username)) {
    $friend_username = $_POST['friend_username'];
  }
  
  if (!$friend_username) {
    $error_message = "No friend username supplied";
    return false;
  }
  
  if ($friend_username == $user) {
    $error_message = "Cannot unfriend oneself";
    return false;
  }
  
  $user1 = min($friend_username, $user);
  $user2 = max($friend_username, $user);
  
  try {
    $res = $db->query("delete from friends f where f.user1=:user1 and f.user2=:user2", array('user1'=>$user1, 'user2'=>$user2));
    return true;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
  
}

function post($token=null, $comment, $uploaded_image_path) {
  $db = new Database(df_db());
  global $error_message;
  $user = get_user($token);
  if (!$user) {
    $error_message = "Not logged in";
    return false;
  }
  
  $filename = null;
  
  if (@$uploaded_image_path) {
    $imagesDir = 'uploads';
    @mkdir($imagesDir);
    $imagesDir = 'uploads/'.sha1($user);
    @mkdir($imagesDir);
    
    $filename = time().basename($uploaded_image_path).'.png';
    if (!move_uploaded_file($uploaded_image_path, $imagesDir.'/'.$filename)){
      $error_message = "Failed to upload file";
      return false;
    }
  }
  try {
    $res = $db->insertObject("posts", (object)array(
      'username' => $user,
      'date_posted' => time(),
      'photo' => $filename,
      'comment' => $comment
      ));
    return xf_db_insert_id(df_db());
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
  
}

function get_posts($token=null, $username=null, $olderThan=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user();
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!$username) {
    $username = $user;
  }
  
  if (!$olderThan) {
    $olderThan = time();
  }
  $out = getObjects("select post_id, username, date_posted, comment, photo from posts where username=:username and date_posted <= :older_than",
  array('username' => $username, 'older_than' => $olderThan));
  foreach ($out as $r) {
    if ($o->photo) {
      $o->photo = get_post_photo_url($o);
    }
  }
  return $out;
  
  
}

function get_feed($token=null, $olderThan=null) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user();
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  if (!isset($olderThan)) {
    $olderThan = time();
  }
  $out = $db->getObjects("select p.post_id, p.username, p.date_posted, p.comment, p.photo, pf.screen_name, pf.avatar
  from posts p 
  left join friends f on (p.username=f.user1 or p.username=f.user2) 
  left join profiles pf on p.username=pf.username
  where (f.user1=:username or f.user2=:username or p.username=:username) and  date_posted<=:older_than
  order by date_posted desc limit 30", array('username'=>$user, 'older_than'=>$olderThan));
  foreach ($out as $o) {
    if ($o->photo) {
      $o->photo = get_post_photo_url($o);
    }
    if ($o->avatar) {
      $o->avatar = get_avatar_url($o);
    }
  }
  return $out;
}


function find_users($token=null, $search) {
  global $error_message;
  $db = new Database(df_db());
  $user = get_user();
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  $out = $db->getObjects("select p.username, p.avatar, p.screen_name, if(f.user1 IS NULL, 0, 1) as is_friend,
  if(pending_invites.sender IS NULL, 0, 1) as has_pending_invite
    from profiles p 
    left join friends f on ((p.username=f.user1 and f.user2=:user) or (p.username=f.user2 and f.user1=:user)) 
  left join friend_requests pending_invites on (pending_invites.receiver=p.username and pending_invites.sender=:user)
    where (p.username like :query or p.screen_name like :query) and p.username != :user limit 100",
  array('query' => '%'.$search.'%', 'user' => $user)
  );
  
  foreach ($out as $o) {
    $o->avatar = get_avatar_url($o);
  }
  return $out;
}

function get_profile($token = null, $username = null) {
  
  global $error_message;
  $db = new Database(df_db());
  $user = get_user();
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (!$username) {
    $username = $user;
  }
  
  $out = $db->getObject("select p.username, p.screen_name, p.avatar from profiles p where p.username=:user", array('user'=>$username));
  if ($out) {
    $out->avatar = get_avatar_url($out);
  }
  return $out;
}

function get_avatar_url($o) {
  if (!$o->avatar) {
    return null;
  }
  return df_absolute_url('uploads/'.sha1($o->username).'/'.basename($o->avatar));
}
  
function get_post_photo_url($o) {
  if (!$o->photo) {
    return null;
  }
  return df_absolute_url('uploads/'.sha1($o->username).'/'.rawurlencode(basename($o->photo)));
}


function update_profile($token = null, $values) {
   
  global $error_message;
  $db = new Database(df_db());
  $user = get_user();
  if (!$user) {
    $error_message = "You are not logged in";
    return false;
  }
  
  if (@$values['avatar']) {
    $filename = 'avatar.'.time().'.png';
    $imgPath = 'uploads/'.sha1($user).'/'.$filename;
    
    @mkdir('uploads/'.sha1($user));
    @unlink($imgPath);
    move_uploaded_file($values['avatar'], $imgPath);
    $values['avatar'] = $filename;
  }
  
  if (count($values) === 0) {
    $error_message = "There were no values specified to update in the profile.";
    return false;
  }
  
  try {
    $res = $db->updateObject('profiles', (object)$values, array('username'=>$user));
    return true;
  } catch (Exception $ex) {
    $error_message = $ex->getMessage();
    return false;
  }
  
  
  
  
}

?>