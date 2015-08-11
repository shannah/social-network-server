<?php

class actions_friends_api {
  function handle($params) {
    require_once 'api.php';
    $query = Dataface_Application::getInstance()->getQuery();
    switch ($query['-do']) {
      case 'login' :
      $token = login($query['username'], $query['password']);
      if (!$token) {
        $this->output(array('code' => 400, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'token' => $token));
      }
      return;
      
      case 'logout' :
      $token = logout($query['token']);
      if (!$token) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200));
      }
      return;
      
      case 'register':
      $res = register($query['username'], $query['password']);
      if (!$res) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200));
      }
      return;
      
      case 'get_friends':
      $friends = get_friends($query['token']);
      if ($friends === false) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'friends' => $friends));
      }
      
      return;
      
      case 'find_users':
      $results = find_users($query['token'], $query['filter']);
      if ($results === false) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'results' => $results));
      }
      
      return;
      
      case 'get_pending_friend_requests':
      $requests = get_pending_friend_requests($query['token']);
      if ($requests === false) {
        $this->output(array('code' => 500, 'message' => 'Failed to get friend requests'));
      } else {
        $this->output(array('code' => 200, 'requests' => $requests));
      }
      
      return;
      
      case 'send_friend_request':
      $res = send_friend_request($query['token'], $query['friend']);
      if (!$res) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200));
      }
      return;
      
      case 'accept_friend_request':
      $res = accept_friend_request($query['token'], $query['friend']);
      if (!$res) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200));
      }
      
      return;
      
      case 'decline_friend_request':
      $res = decline_friend_request($query['token'], $query['friend']);
      if (!$res) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200));
      }
      
      return;
      
      case 'post':
      $file_path =null;
      $file_name = null;
      if (@$_FILES['photo']) {
        $file_path = $_FILES['photo']['tmp_name']; 
        $file_name = $_FILES['photo']['name'];
      }
      $res = post($query['token'], $query['comment'], $file_path, $file_name);
      if (!$res) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'post_id' => $res));
      }
      return;
      
      case 'get_posts':
      $posts = get_posts($query['token'], @$query['username'], @$query['older_than']);
      if ($posts === false) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'posts' => $posts));
      }
      return;
      
      case 'get_feed':
      $posts = get_feed($query['token'], @$query['older_than']);
      if ($posts === false) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'posts' => $posts));
      }
      return;
      
      case 'get_profile':
      $profile = get_profile($query['token'], $query['username']);
      if ($profile === false) {
        $this->output(array('code' => 500, 'message' => error_message()));
      } else {
        $this->output(array('code' => 200, 'profile' => $profile));
      }
      return;
      
      case 'update_profile':
      $vals = array();
      if (@$query['screen_name']) {
        $vals['screen_name'] = $query['screen_name'];
      }
      if (@$_FILES['avatar']) {
        $vals['avatar'] = $_FILES['avatar']['tmp_name'];
      }
      $res = update_profile($query['token'], $vals);
      if ($res !== false) {
        $this->output(array('code' => 200));
      } else {
        $this->output(array('code' => 500, 'message' => error_message()));
      }
      return;
      
    }
  }
  
  function output($content) {
    header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
    echo json_encode($content);
  }
}
?>