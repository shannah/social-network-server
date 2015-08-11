<?php
class conf_ApplicationDelegate {
  public function getPermissions(Dataface_Record $record = null) {
    return Dataface_PermissionsTool::NO_ACCESS();
  }  
  
  public function beforeHandleRequest() {
    $query =& Dataface_Application::getInstance()->getQuery();
    if ($query['-action'] !== 'friends_api') {
      $query['-action'] = 'install_app';
    }
  }
}?>